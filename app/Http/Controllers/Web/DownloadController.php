<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Documents\Services\WatermarkService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{
    public function __construct(
        protected WatermarkService $watermarkService,
    ) {}

    public function download(Request $request, int $id)
    {
        $document = Document::findOrFail($id);

        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();

        // Load user's institution for watermarking and auth checks
        $user->loadMissing('institution');

        // Authorization: isAccessibleBy() handles all cases:
        //   document.view.all  → bypass
        //   target_audience=all → open
        //   specific_institutions / specific_roles → checked via pivot tables
        if (! $document->isAccessibleBy($user)) {
            abort(403, __('Vous n\'avez pas l\'autorisation de télécharger ce document.'));
        }

        // Track the download and generate unique watermark UUID
        $downloadLog = new DownloadLog([
            'document_id' => $document->id,
            'user_id'     => $user->id,
            'ip_address'  => $request->ip() ?? '127.0.0.1',
            'user_agent'  => $request->userAgent() ?? 'Unknown',
        ]);
        $downloadLog->save(); // watermark_uuid + downloaded_at set in model boot()

        // Eager-load relations needed by WatermarkService
        $downloadLog->load('user.institution');

        try {
            $watermarkedPdfPath = $this->watermarkService->generateWatermarkedPdf($document, $downloadLog);
        } catch (\Exception $e) {
            abort(500, __('Erreur lors de la génération du filigrane : :message', ['message' => $e->getMessage()]));
        }

        // Write immutable audit entry
        AuditLog::create([
            'event_type'    => 'document.download',
            'user_id'       => $user->id,
            'user_email'    => $user->email,
            'resource_type' => 'download_log',
            'resource_id'   => $downloadLog->id,
            'metadata'      => [
                'document_id'      => $document->id,
                'reference_number' => $document->reference_number,
                'watermark_uuid'   => $downloadLog->watermark_uuid,
                'institution_id'   => $user->institution_id,
            ],
            'result'     => 'success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $filename = $document->reference_number . '.pdf';

        return response()->download($watermarkedPdfPath, $filename)->deleteFileAfterSend(true);
    }
}

