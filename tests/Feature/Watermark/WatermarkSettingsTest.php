<?php

declare(strict_types=1);

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Documents\Services\WatermarkService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Settings\Models\SystemSetting;
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

function wmsSettingsStorePdf(string $path): void
{
    $fpdf = new \FPDF();
    $fpdf->AddPage();
    $fpdf->SetFont('Arial', '', 12);
    $fpdf->Cell(0, 10, 'Settings test PDF');

    $tmp = tempnam(sys_get_temp_dir(), 'wms_set_');
    $fpdf->Output('F', $tmp);
    Storage::disk('local')->put($path, file_get_contents($tmp));
    @unlink($tmp);
}

function wmsSettingsCreateDoc(User $u): Document
{
    return Document::query()->create([
        'reference_number' => '2026-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title'            => 'Settings WM '.Str::random(4),
        'description'      => 'Doc de test',
        'file_path'        => 'documents/settings_'.Str::random(6).'.pdf',
        'file_hash'        => str_repeat('d', 64),
        'file_size'        => 2048,
        'issue_date'       => now()->toDateString(),
        'effective_date'   => now()->toDateString(),
        'expiration_date'  => now()->addMonth()->toDateString(),
        'status'           => 'active',
        'indexing_status'  => 'pending',
        'target_audience'  => 'all',
        'version_number'   => 1,
        'uploaded_by'      => $u->id,
    ]);
}

function wmsSettingsCreateLog(User $u, Document $d): DownloadLog
{
    $log = DownloadLog::create([
        'document_id' => $d->id,
        'user_id'     => $u->id,
        'ip_address'  => '10.0.0.99',
        'user_agent'  => 'PHPUnit WatermarkSettingsTest',
    ]);
    $log->load('user.institution');
    return $log;
}

function decompressPdf(string $path): string
{
    exec('qpdf --version', $verOut, $verExit);
    if ($verExit !== 0) {
        test()->markTestSkipped('qpdf is not installed.');
    }
    $dec = tempnam(sys_get_temp_dir(), 'wm_dec_').'.pdf';
    exec('qpdf --qdf --object-streams=disable '.escapeshellarg($path).' '.escapeshellarg($dec), $o, $e);
    if ($e !== 0) {
        @unlink($dec);
        test()->markTestSkipped('qpdf decompression failed.');
    }
    $content = (string) file_get_contents($dec);
    @unlink($dec);
    return $content;
}

it('omits the recipient email from PDF metadata when recipient_email is not in metadata_fields', function (): void {
    Storage::fake('local');

    SystemSetting::query()->updateOrCreate(
        ['key' => 'watermark.metadata_fields'],
        ['value' => ['value' => ['recipient_name', 'recipient_institution', 'downloaded_at', 'download_uuid']], 'updated_by' => null, 'updated_at' => now()]
    );

    $user = User::factory()->create(['email' => 'leak.detector@example.test']);
    $doc  = wmsSettingsCreateDoc($user);
    wmsSettingsStorePdf($doc->file_path);

    $log = wmsSettingsCreateLog($user, $doc);
    $out = app(WatermarkService::class)->generateWatermarkedPdf($doc, $log);

    $raw = (string) file_get_contents($out);
    expect($raw)->not->toContain('leak.detector@example.test');

    @unlink($out);
});

it('omits the visible recipient name when recipient_name is not in visible_fields', function (): void {
    Storage::fake('local');

    SystemSetting::query()->updateOrCreate(
        ['key' => 'watermark.visible_fields'],
        ['value' => ['value' => ['download_uuid', 'downloaded_at']], 'updated_by' => null, 'updated_at' => now()]
    );
    SystemSetting::query()->updateOrCreate(
        ['key' => 'watermark.metadata_fields'],
        ['value' => ['value' => ['download_uuid', 'downloaded_at']], 'updated_by' => null, 'updated_at' => now()]
    );

    $user = User::factory()->create(['full_name' => 'Salim El-Témoignage']);
    $doc  = wmsSettingsCreateDoc($user);
    wmsSettingsStorePdf($doc->file_path);

    $log = wmsSettingsCreateLog($user, $doc);
    $out = app(WatermarkService::class)->generateWatermarkedPdf($doc, $log);

    $content = decompressPdf($out);
    expect($content)->not->toContain('Salim');

    @unlink($out);
});

it('always embeds the download UUID in metadata when download_uuid is enabled', function (): void {
    Storage::fake('local');

    SystemSetting::query()->updateOrCreate(
        ['key' => 'watermark.metadata_fields'],
        ['value' => ['value' => ['download_uuid']], 'updated_by' => null, 'updated_at' => now()]
    );

    $user = User::factory()->create();
    $doc  = wmsSettingsCreateDoc($user);
    wmsSettingsStorePdf($doc->file_path);

    $log = wmsSettingsCreateLog($user, $doc);
    $out = app(WatermarkService::class)->generateWatermarkedPdf($doc, $log);

    $raw = (string) file_get_contents($out);
    expect($raw)->toContain('SIKDS-UUID:'.$log->watermark_uuid);

    @unlink($out);
});

it('accepts legacy field names as aliases (full_name → recipient_name)', function (): void {
    Storage::fake('local');

    SystemSetting::query()->updateOrCreate(
        ['key' => 'watermark.visible_fields'],
        ['value' => ['value' => ['full_name', 'uuid']], 'updated_by' => null, 'updated_at' => now()]
    );

    $user = User::factory()->create(['full_name' => 'Yamina Aliasette']);
    $doc  = wmsSettingsCreateDoc($user);
    wmsSettingsStorePdf($doc->file_path);

    $log = wmsSettingsCreateLog($user, $doc);
    $out = app(WatermarkService::class)->generateWatermarkedPdf($doc, $log);

    $content = decompressPdf($out);
    expect($content)->toContain('Yamina');

    @unlink($out);
});
