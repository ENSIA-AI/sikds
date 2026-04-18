<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Documents\Services\WatermarkService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

// ─── Bootstrap ───────────────────────────────────────────────────────────────

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Institution::query()->firstOrCreate(
        ['code' => 'MESRS'],
        ['name' => 'Ministère', 'type' => 'ministry', 'domain' => 'mesrs.dz']
    );
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Returns all PDF fixture files found in tests/Fixtures/pdfs/.
 * Pest datasets receive one file per test run.
 */
function fixtureDataset(): array
{
    $dir   = __DIR__ . '/../../Fixtures/pdfs';
    $files = glob($dir . '/*.pdf') ?: [];

    // Each entry: ['label' => [absolutePath]]
    $dataset = [];
    foreach ($files as $path) {
        $dataset[basename($path)] = [$path];
    }

    return $dataset;
}

function fxtCreateDocument(User $uploader, string $storagePath, string $filename): Document
{
    return Document::query()->create([
        'reference_number' => 'FXT-' . strtoupper(Str::random(6)),
        'title'            => 'Fixture: ' . $filename,
        'description'      => 'Loaded from tests/Fixtures/pdfs/' . $filename,
        'file_path'        => $storagePath,
        'file_hash'        => str_repeat('f', 64),
        'file_size'        => 1024,
        'issue_date'       => now()->toDateString(),
        'effective_date'   => now()->toDateString(),
        'expiration_date'  => now()->addMonth()->toDateString(),
        'status'           => 'active',
        'indexing_status'  => 'pending',
        'target_audience'  => 'all',
        'version_number'   => 1,
        'uploaded_by'      => $uploader->id,
    ]);
}

function fxtCreateLog(User $user, Document $document): DownloadLog
{
    $log = DownloadLog::create([
        'document_id' => $document->id,
        'user_id'     => $user->id,
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'PHPUnit FixtureTest',
    ]);
    $log->load('user.institution');

    return $log;
}

// ─── Guard: skip the whole file if no fixtures are present ───────────────────

if (empty(fixtureDataset())) {
    test('fixture pdf watermarking skipped — no files in tests/Fixtures/pdfs/', function () {
        $this->markTestSkipped('Add PDF files to tests/Fixtures/pdfs/ to enable fixture tests.');
    });

    return; // stop registering the dataset tests below
}

// ─── Dataset tests — one run per file ────────────────────────────────────────

dataset('fixture pdfs', fixtureDataset());

test(
    'fixture pdf can be watermarked without errors',
    function (string $fixturePath) {
        Storage::fake('local');

        $filename    = basename($fixturePath);
        $storagePath = 'documents/fixtures/' . $filename;
        $user        = User::factory()->create();

        // Load real bytes from the fixture file into the faked disk
        Storage::disk('local')->put($storagePath, file_get_contents($fixturePath));

        $document   = fxtCreateDocument($user, $storagePath, $filename);
        $log        = fxtCreateLog($user, $document);
        $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);

        expect($outputPath)->toBeFile(
            "WatermarkService failed to produce an output file for [{$filename}]"
        );
        expect(file_get_contents($outputPath))->toStartWith(
            '%PDF',
            "Output file for [{$filename}] is not a valid PDF"
        );

        @unlink($outputPath);
    }
)->with('fixture pdfs');

test(
    'fixture pdf watermark contains the short uuid',
    function (string $fixturePath) {
        Storage::fake('local');

        $filename    = basename($fixturePath);
        $storagePath = 'documents/fixtures/' . $filename;
        $user        = User::factory()->create();

        Storage::disk('local')->put($storagePath, file_get_contents($fixturePath));

        $document   = fxtCreateDocument($user, $storagePath, $filename);
        $log        = fxtCreateLog($user, $document);
        $shortUuid  = 'WM-' . strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));
        $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);

        expect(file_get_contents($outputPath))->toContain(
            $shortUuid,
            "Short UUID [{$shortUuid}] not found in watermarked output for [{$filename}]"
        );

        @unlink($outputPath);
    }
)->with('fixture pdfs');

test(
    'fixture pdf watermark embeds sikds metadata in pdf info dictionary',
    function (string $fixturePath) {
        Storage::fake('local');

        $filename    = basename($fixturePath);
        $storagePath = 'documents/fixtures/' . $filename;
        $user        = User::factory()->create();

        Storage::disk('local')->put($storagePath, file_get_contents($fixturePath));

        $document   = fxtCreateDocument($user, $storagePath, $filename);
        $log        = fxtCreateLog($user, $document);
        $outputPath = (new WatermarkService())->generateWatermarkedPdf($document, $log);
        $content    = file_get_contents($outputPath);

        expect($content)->toContain(
            'SIKDS-UUID:' . $log->watermark_uuid,
            "SIKDS-UUID metadata missing from output for [{$filename}]"
        );
        expect($content)->toContain(
            '[SIKDS-SECURED]',
            "[SIKDS-SECURED] tag missing from output for [{$filename}]"
        );

        @unlink($outputPath);
    }
)->with('fixture pdfs');
