<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $systemSettings,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('settings.view');

        $managed = $this->systemSettings->all();

        return view('settings.index', [
            'managed' => $managed,
            'health' => $this->systemHealth(),
            'activeNav' => 'settings',
        ]);
    }

    /**
     * Read-only operational snapshot for the System Health panel.
     *
     * @return array<string, mixed>
     */
    private function systemHealth(): array
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $dbOk = false;
        }

        return [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'db_ok' => $dbOk,
            'queue_connection' => (string) config('queue.default'),
            'documents_count' => Document::query()->count(),
            'users_count' => User::query()->count(),
            'audit_logs_count' => AuditLog::query()->count(),
            'oldest_audit_at' => AuditLog::query()->min('created_at'),
            'storage_bytes' => (int) Document::query()->sum('file_size'),
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();

        $section = (string) $request->input('section');
        abort_unless(in_array($section, ['notifications', 'audit', 'watermark', 'rag'], true), 422, __('Section invalide.'));

        $payload = match ($section) {
            'notifications' => $request->validate([
                'document_published_enabled' => ['nullable', 'boolean'],
                'document_updated_enabled' => ['nullable', 'boolean'],
                'support_contact_email' => ['required', 'email', 'max:190'],
            ]),
            'audit' => $request->validate([
                'retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
                'export_max_days' => ['required', 'integer', 'min:1', 'max:365'],
            ]),
            'watermark' => $request->validate([
                'visible_fields' => ['required', 'array', 'min:1'],
                'visible_fields.*' => [
                    'string',
                    'in:full_name,institution,timestamp,uuid,recipient_name,recipient_institution,recipient_email,downloaded_at,download_uuid,document_title,document_reference',
                ],
                'metadata_fields' => ['required', 'array', 'min:1'],
                'metadata_fields.*' => [
                    'string',
                    'in:full_name,institution,email,timestamp,uuid,recipient_name,recipient_institution,recipient_email,downloaded_at,download_uuid,document_title,document_reference',
                ],
            ]),
            'rag' => $request->validate([
                'llm_model' => ['required', 'string', 'max:190'],
                'min_confidence' => ['required', 'numeric', 'between:0,1'],
                'candidate_pool' => ['required', 'integer', 'min:1', 'max:200'],
                'top_n' => ['required', 'integer', 'min:1', 'max:50'],
                'reranking_enabled' => ['nullable', 'boolean'],
                'hybrid_enabled' => ['nullable', 'boolean'],
            ]),
            default => [],
        };

        if ($section === 'notifications') {
            $payload['document_published_enabled'] = $request->boolean('document_published_enabled');
            $payload['document_updated_enabled'] = $request->boolean('document_updated_enabled');
        }
        if ($section === 'watermark') {
            $payload['visible_fields'] = array_values(array_unique(array_map('strval', $payload['visible_fields'] ?? [])));
            $payload['metadata_fields'] = array_values(array_unique(array_map('strval', $payload['metadata_fields'] ?? [])));
        }
        if ($section === 'rag') {
            $payload['reranking_enabled'] = $request->boolean('reranking_enabled');
            $payload['hybrid_enabled'] = $request->boolean('hybrid_enabled');
            $payload['min_confidence'] = (float) $payload['min_confidence'];
            $payload['candidate_pool'] = (int) $payload['candidate_pool'];
            $payload['top_n'] = (int) $payload['top_n'];
        }

        $before = array_intersect_key($this->systemSettings->all()[$section] ?? [], $payload);

        $this->systemSettings->updateSection($section, $payload, (int) $user->id);

        $this->audit->record(
            eventType: 'settings.updated',
            user: $user,
            resourceType: 'settings',
            metadata: [
                'section' => $section,
                'changed_keys' => array_keys($payload),
                'before' => $before,
                'after' => $payload,
            ],
            request: $request,
        );

        return redirect()->route('settings.index')->with('success', __('Paramètres enregistrés.'));
    }
}
