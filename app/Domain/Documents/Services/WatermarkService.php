<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use setasign\Fpdi\Fpdi;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use Illuminate\Support\Facades\Storage;

class WatermarkService
{
    /**
     * Applies visible header + footer watermarks and embeds PDF info metadata.
     * Generates a temporary local PDF file and returns its path.
     *
     * Caller must eager-load the downloadLog user and institution before calling:
     *   $downloadLog->load('user.institution')
     */
    public function generateWatermarkedPdf(Document $document, DownloadLog $downloadLog): string
    {
        $disk = (string) config('filesystems.documents_disk', 'local');
        $fileContents = Storage::disk($disk)->get($document->file_path);

        if (!$fileContents) {
            throw new \RuntimeException('Document file could not be read from storage.');
        }

        $tempOriginalFile = tempnam(sys_get_temp_dir(), 'sikds_in_');
        file_put_contents($tempOriginalFile, $fileContents);

        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($tempOriginalFile);

        // Resolve watermark payload fields
        $user            = $downloadLog->user;
        $userName        = $user->full_name ?: ($user->username ?? 'Unknown');
        $institution     = $user->institution;
        $institutionName = $institution?->name ?? ('Institution #' . ($user->institution_id ?? 'N/A'));
        $institutionCode = $institution?->code ?? 'N/A';
        $downloadedAt    = $downloadLog->downloaded_at;
        $uuid            = $downloadLog->watermark_uuid;
        $shortUuid       = 'WM-' . strtoupper(substr(str_replace('-', '', $uuid), 0, 8));

        // FPDF uses ISO-8859-1. Convert UTF-8 for standard Latin characters.
        $encode = fn (string $s): string => (string) iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);

        $headerText = $encode("{$userName} | {$institutionName}");
        $footerText = $encode("Telechargé le " . $downloadedAt->format('Y-m-d') . " à " . $downloadedAt->format('H:i:s') . " | UUID: {$shortUuid}");

        // --- PDF Info Dictionary Metadata (readable with pdfinfo / exiftool) ---
        $metadataPayload = json_encode([
            'userId'            => 'USR-' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
            'userName'          => $userName,
            'userEmail'         => $user->email ?? '',
            'institutionCode'   => $institutionCode,
            'institutionName'   => $institutionName,
            'documentId'        => 'DOC-' . str_pad((string) $document->id, 7, '0', STR_PAD_LEFT),
            'documentVersion'   => $document->version_number,
            'downloadId'        => 'DL-' . str_pad((string) $downloadLog->id, 7, '0', STR_PAD_LEFT),
            'downloadTimestamp' => $downloadedAt->toIso8601String(),
            'watermarkUUID'     => $uuid,
            'ipAddress'         => $downloadLog->ip_address ?? '',
        ]);

        $fpdi->SetTitle($document->title . ' [SIKDS-SECURED]');
        $fpdi->SetAuthor($userName . ' | ' . $institutionName);
        $fpdi->SetCreator('SIKDS v1.0');
        $fpdi->SetSubject('SIKDS-UUID:' . $uuid);
        $fpdi->SetKeywords((string) $metadataPayload);

        // --- Apply header + footer watermarks on every page ---
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $fpdi->importPage($pageNo);
            $size       = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);

            $fpdi->SetFont('Arial', '', 8);
            $fpdi->SetTextColor(102, 102, 102); // #666666

            // Header: name | institution (top of page, 5 mm from top)
            $fpdi->SetXY(0, 5);
            $fpdi->Cell($size['width'], 5, $headerText, 0, 0, 'C');

            // Footer: download date + short UUID (10 mm from bottom)
            $fpdi->SetXY(0, $size['height'] - 10);
            $fpdi->Cell($size['width'], 5, $footerText, 0, 0, 'C');
        }

        $outputFile = tempnam(sys_get_temp_dir(), 'sikds_out_') . '.pdf';
        $fpdi->Output('F', $outputFile);

        @unlink($tempOriginalFile);

        return $outputFile;
    }
}

