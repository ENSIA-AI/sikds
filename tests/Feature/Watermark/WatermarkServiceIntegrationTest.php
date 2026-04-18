<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Documents\Services\WatermarkService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Institution::query()->firstOrCreate(
        ['code' => 'MESRS'],
        ['name' => 'Ministère', 'type' => 'ministry', 'domain' => 'mesrs.dz']
    );
});

/**
 * Generate a minimal valid PDF file using FPDF and store it in the faked local disk.
 */
function wmsStorePdf(string $storagePath): void
{
    $fpdf = new \FPDF();
    $fpdf->AddPage();
    $fpdf->SetFont('Arial', 'B', 16);
    $fpdf->Cell(0, 10, 'SIKDS Integration Test Document');
    $fpdf->Ln();
    $fpdf->SetFont('Arial', '', 12);
    $fpdf->Cell(0, 10, 'This PDF is used to verify the watermarking pipeline.');

    $tmp = tempnam(sys_get_temp_dir(), 'wms_in_');
    $fpdf->Output('F', $tmp);

    Storage::disk('local')->put($storagePath, file_get_contents($tmp));
    unlink($tmp);
}

function wmsCreateDocument(User $uploader, array $overrides = []): Document
{
    $defaults = [
        'reference_number' => now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title'            => 'WMS Integration '.Str::random(5),
        'description'      => 'Integration test document',
        'file_path'        => 'documents/wms_test.pdf',
        'file_hash'        => str_repeat('b', 64),
        'file_size'        => 2048,
        'issue_date'       => now()->toDateString(),
        'effective_date'   => now()->toDateString(),
        'expiration_date'  => now()->addMonth()->toDateString(),
        'status'           => 'active',
        'indexing_status'  => 'pending',
        'target_audience'  => 'all',
        'version_number'   => 1,
        'uploaded_by'      => $uploader->id,
    ];

    return Document::query()->create(array_merge($defaults, $overrides));
}

function wmsCreateLog(User $user, Document $document): DownloadLog
{
    $log = DownloadLog::create([
        'document_id' => $document->id,
        'user_id'     => $user->id,
        'ip_address'  => '10.0.0.42',
        'user_agent'  => 'PHPUnit WatermarkIntegrationTest',
    ]);
    $log->load('user.institution');

    return $log;
}

// ─── Test 1: Standard PDF 1.4 ────────────────────────────────────────────────

test('watermark service generates a valid pdf from a standard pdf 1.4 file', function () {
    Storage::fake('local');

    $user     = User::factory()->create();
    $document = wmsCreateDocument($user);
    wmsStorePdf($document->file_path);

    $log        = wmsCreateLog($user, $document);
    $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);

    expect($outputPath)->toBeFile();
    expect(file_get_contents($outputPath))->toStartWith('%PDF');

    @unlink($outputPath);
});

// ─── Test 2: Compressed PDF 1.5+ (the previously failing case) ───────────────

test('watermark service processes a pdf 1.5+ compressed file produced by qpdf', function () {
    if (!trim((string) shell_exec('which qpdf'))) {
        $this->markTestSkipped('qpdf is not installed on this system.');
    }

    Storage::fake('local');

    // Build a plain PDF 1.4 with FPDF
    $fpdf = new \FPDF();
    $fpdf->AddPage();
    $fpdf->SetFont('Arial', '', 12);
    $fpdf->Cell(0, 10, 'PDF 1.5 compressed object-stream test content');

    $tmpStd = tempnam(sys_get_temp_dir(), 'wms_std_');
    $tmpCmp = tempnam(sys_get_temp_dir(), 'wms_cmp_') . '.pdf';
    $fpdf->Output('F', $tmpStd);

    // Compress into PDF 1.5+ object-stream format — reproduces the old crash
    exec(
        'qpdf --object-streams=generate ' . escapeshellarg($tmpStd) . ' ' . escapeshellarg($tmpCmp),
        $out,
        $exitCode
    );
    @unlink($tmpStd);

    expect($exitCode)->toBe(0, 'qpdf failed to produce a compressed PDF: ' . implode("\n", $out));
    expect($tmpCmp)->toBeFile();

    // Verify qpdf actually produced a compressed (1.5+) file by checking for /ObjStm
    $rawPdf = file_get_contents($tmpCmp);
    expect($rawPdf)->toContain('/ObjStm', 'Expected a PDF with object streams — test fixture is not compressed.');

    $storagePath = 'documents/compressed_test.pdf';
    Storage::disk('local')->put($storagePath, $rawPdf);
    @unlink($tmpCmp);

    $user     = User::factory()->create();
    $document = wmsCreateDocument($user, ['file_path' => $storagePath]);
    $log      = wmsCreateLog($user, $document);

    // This must NOT throw the "compression technique not supported" exception
    $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);

    expect($outputPath)->toBeFile();
    expect(file_get_contents($outputPath))->toStartWith('%PDF');

    @unlink($outputPath);
});

// ─── Test 3: Watermark content embedded in output ────────────────────────────

test('watermark service embeds the short uuid in the pdf output', function () {
    Storage::fake('local');

    $user     = User::factory()->create();
    $document = wmsCreateDocument($user);
    wmsStorePdf($document->file_path);

    $log        = wmsCreateLog($user, $document);
    $shortUuid  = 'WM-' . strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));

    $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);

    // Watermark text is inside FlateDecode-compressed content streams;
    // decompress with qpdf so the string is searchable in raw bytes.
    $decompressed = tempnam(sys_get_temp_dir(), 'wm_dec_') . '.pdf';
    exec('qpdf --qdf --object-streams=disable ' . escapeshellarg($outputPath) . ' ' . escapeshellarg($decompressed), $qOut, $qExit);
    $content = $qExit === 0 ? file_get_contents($decompressed) : file_get_contents($outputPath);
    @unlink($decompressed);

    expect($content)->toContain($shortUuid);

    @unlink($outputPath);
});

// ─── Test 4: PDF metadata contains downloader identity ───────────────────────

test('watermark service embeds user identity in the pdf info dictionary', function () {
    Storage::fake('local');

    $user     = User::factory()->create();
    $document = wmsCreateDocument($user);
    wmsStorePdf($document->file_path);

    $log        = wmsCreateLog($user, $document);
    $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);
    $content    = file_get_contents($outputPath);

    // The PDF info dictionary encodes these as ISO-8859-1 strings in the raw bytes
    expect($content)->toContain('SIKDS-UUID:' . $log->watermark_uuid);
    expect($content)->toContain('[SIKDS-SECURED]');

    @unlink($outputPath);
});
