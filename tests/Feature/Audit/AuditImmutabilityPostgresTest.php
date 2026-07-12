<?php

declare(strict_types=1);

use App\Domain\Audit\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Exercises the PostgreSQL-only immutability triggers (trg_audit_no_update /
 * trg_audit_no_delete). The default suite runs on SQLite where the triggers do
 * not exist, so these tests skip themselves there.
 *
 * Run against PostgreSQL with:
 *   php artisan test -c phpunit.pgsql.xml tests/Feature/Audit
 * (see phpunit.pgsql.xml; requires a disposable Postgres database).
 */
beforeEach(function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Audit immutability triggers only exist on PostgreSQL.');
    }
});

it('rejects UPDATE on audit_logs rows', function (): void {
    $log = app(AuditService::class)->record(eventType: 'test.immutability.update');

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['result' => 'failed']))
        ->toThrow(QueryException::class, 'immutable');
});

it('rejects DELETE on audit_logs rows', function (): void {
    $log = app(AuditService::class)->record(eventType: 'test.immutability.delete');

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->delete())
        ->toThrow(QueryException::class, 'immutable');
});
