<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $systemSettings,
    ) {}

    public function index(Request $request): View
    {
        $managed = $this->systemSettings->all();

        return view('settings.index', [
            'managed' => $managed,
            'activeNav' => 'settings',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();

        $section = (string) $request->input('section');
        abort_unless(in_array($section, ['notifications', 'audit', 'watermark'], true), 422, 'Section invalide.');

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

        $this->systemSettings->updateSection($section, $payload, (int) $user->id);

        AuditLog::query()->create([
            'event_type' => 'settings.updated',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'resource_type' => 'settings',
            'resource_id' => null,
            'metadata' => [
                'section' => $section,
                'changed_keys' => array_keys($payload),
            ],
            'result' => 'success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('settings.index')->with('success', 'Paramètres enregistrés.');
    }
}
