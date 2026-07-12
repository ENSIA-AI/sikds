<?php

declare(strict_types=1);

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('chains every new audit row to the previous one', function (): void {
    $audit = app(AuditService::class);

    $first = $audit->record(eventType: 'test.event.one', metadata: ['n' => 1]);
    $second = $audit->record(eventType: 'test.event.two', metadata: ['n' => 2]);
    $third = $audit->record(eventType: 'test.event.three', metadata: ['n' => 3]);

    expect($first->previous_hash)->toBe(AuditService::GENESIS_HASH)
        ->and($first->row_hash)->toHaveLength(64)
        ->and($second->previous_hash)->toBe($first->row_hash)
        ->and($third->previous_hash)->toBe($second->row_hash);
});

it('verifies an intact chain via audit:verify-chain', function (): void {
    $audit = app(AuditService::class);
    $audit->record(eventType: 'test.event.one', metadata: ['n' => 1]);
    $audit->record(eventType: 'test.event.two', metadata: ['nested' => ['b' => 2, 'a' => 1]]);

    artisan('audit:verify-chain')->assertExitCode(0);
});

it('detects a tampered row via audit:verify-chain', function (): void {
    // On PostgreSQL the immutability triggers make this UPDATE impossible
    // (covered by AuditImmutabilityPostgresTest); the chain verifier is the
    // detection layer for storage that lacks triggers or for out-of-band edits.
    if (DB::getDriverName() === 'pgsql') {
        $this->markTestSkipped('UPDATE on audit_logs is blocked by triggers on PostgreSQL.');
    }

    $audit = app(AuditService::class);
    $audit->record(eventType: 'test.event.one', metadata: ['n' => 1]);
    $victim = $audit->record(eventType: 'test.event.two', metadata: ['n' => 2]);
    $audit->record(eventType: 'test.event.three', metadata: ['n' => 3]);

    DB::table('audit_logs')->where('id', $victim->id)->update(['result' => 'failed']);

    artisan('audit:verify-chain')->assertExitCode(1);
});

it('detects a deleted row via audit:verify-chain', function (): void {
    if (DB::getDriverName() === 'pgsql') {
        $this->markTestSkipped('DELETE on audit_logs is blocked by triggers on PostgreSQL.');
    }

    $audit = app(AuditService::class);
    $audit->record(eventType: 'test.event.one', metadata: ['n' => 1]);
    $victim = $audit->record(eventType: 'test.event.two', metadata: ['n' => 2]);
    $audit->record(eventType: 'test.event.three', metadata: ['n' => 3]);

    DB::table('audit_logs')->where('id', $victim->id)->delete();

    artisan('audit:verify-chain')->assertExitCode(1);
});

it('skips legacy rows written before the chain existed', function (): void {
    // Simulate a pre-chain row (row_hash NULL) followed by chained rows.
    AuditLog::query()->create([
        'event_type' => 'legacy.event',
        'result' => 'success',
        'metadata' => [],
        'created_at' => now(),
    ]);

    app(AuditService::class)->record(eventType: 'test.event.one', metadata: ['n' => 1]);

    artisan('audit:verify-chain')->assertExitCode(0);
});
