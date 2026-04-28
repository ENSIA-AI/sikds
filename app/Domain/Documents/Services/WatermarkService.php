<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

/**
 * FPDI subclass that adds diagonal-text rotation and PDF transparency (alpha)
 * via PDF transformation matrices and ExtGState resources.
 */
class RotatableFpdi extends Fpdi
{
    private float $rotAngle = 0.0;

    /** @var array<int, array{parms: array, n?: int}> */
    protected array $extgstates = [];

    /**
     * Begin a rotation of $angle degrees around the point ($x, $y) in user units.
     * Positive = counter-clockwise visually; negative = clockwise.
     */
    public function rotate(float $angle, float $x, float $y): void
    {
        if ($this->rotAngle !== 0.0) {
            $this->_out('Q');
        }
        $this->rotAngle = $angle;
        if ($angle !== 0.0) {
            $rad = $angle * M_PI / 180.0;
            $c   = cos($rad);
            $s   = sin($rad);
            $cx  = $x * $this->k;
            $cy  = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm',
                $c, $s, -$s, $c,
                $cx - $c * $cx + $s * $cy,
                $cy - $s * $cx - $c * $cy
            ));
        }
    }

    /** Close the current rotation graphics state. */
    public function endRotate(): void
    {
        if ($this->rotAngle !== 0.0) {
            $this->_out('Q');
            $this->rotAngle = 0.0;
        }
    }

    /**
     * Set transparency for both stroking and non-stroking operations.
     *
     * @param float $alpha 0.0 (invisible) … 1.0 (fully opaque)
     */
    public function setAlpha(float $alpha): void
    {
        $gs = $this->addExtGState(['ca' => $alpha, 'CA' => $alpha, 'BM' => '/Normal']);
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    protected function addExtGState(array $parms): int
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n] = ['parms' => $parms];
        return $n;
    }

    /** Ensure PDF version is at least 1.4 when transparency is used. */
    protected function _enddoc(): void
    {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }
        parent::_enddoc();
    }

    /** Write ExtGState objects into the PDF. */
    protected function _putextgstates(): void
    {
        foreach ($this->extgstates as $i => $gs) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $this->_put(sprintf('/ca %.3F', $gs['parms']['ca']));
            $this->_put(sprintf('/CA %.3F', $gs['parms']['CA']));
            $this->_put('/BM ' . $gs['parms']['BM']);
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        if (!empty($this->extgstates)) {
            $this->_put('/ExtGState <<');
            foreach ($this->extgstates as $k => $gs) {
                $this->_put('/GS' . $k . ' ' . $gs['n'] . ' 0 R');
            }
            $this->_put('>>');
        }
    }

    protected function _putresources(): void
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}

class WatermarkService
{
    /**
     * Canonical watermark field names. Settings stored under
     * legacy keys (full_name, institution, email, timestamp, uuid) are
     * accepted as aliases so existing config keeps working.
     */
    public const VISIBLE_DEFAULTS = [
        'recipient_name',
        'recipient_institution',
        'downloaded_at',
        'download_uuid',
    ];

    public const METADATA_DEFAULTS = [
        'recipient_name',
        'recipient_institution',
        'recipient_email',
        'downloaded_at',
        'download_uuid',
        'document_title',
        'document_reference',
    ];

    private const ALIASES = [
        'full_name'    => 'recipient_name',
        'institution'  => 'recipient_institution',
        'email'        => 'recipient_email',
        'timestamp'    => 'downloaded_at',
        'uuid'         => 'download_uuid',
    ];

    private const KNOWN_FIELDS = [
        'recipient_name',
        'recipient_institution',
        'recipient_email',
        'downloaded_at',
        'download_uuid',
        'document_title',
        'document_reference',
    ];

    public function __construct(
        private readonly ?SystemSettingsService $settings = null,
    ) {}

    /**
     * Applies a large diagonal watermark on every page plus a small footer strip,
     * and embeds PDF info-dictionary metadata for forensic traceability.
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

        // Decompress PDF 1.5+ object/xref streams so the free FPDI parser can read them.
        $decompressedFile = tempnam(sys_get_temp_dir(), 'sikds_dec_');
        $exitCode = -1;
        exec(
            'qpdf --decode-level=generalized --object-streams=disable '
            . escapeshellarg($tempOriginalFile) . ' '
            . escapeshellarg($decompressedFile) . ' 2>&1',
            $output,
            $exitCode
        );

        if ($exitCode === 0 && filesize($decompressedFile) > 0) {
            @unlink($tempOriginalFile);
            $tempOriginalFile = $decompressedFile;
        } else {
            // qpdf failed — try the original file as-is (works for PDF ≤ 1.4)
            @unlink($decompressedFile);
        }

        $fpdi = new RotatableFpdi();
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

        $visibleFields  = $this->resolveFields('watermark.visible_fields', self::VISIBLE_DEFAULTS);
        $metadataFields = $this->resolveFields('watermark.metadata_fields', self::METADATA_DEFAULTS);

        // FPDF uses ISO-8859-1. Convert UTF-8 for standard Latin characters.
        $encode = fn (string $s): string => (string) iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);

        // ----- Build visible (overlay) text segments based on the toggle -----
        $line1Parts = [];
        if (in_array('download_uuid', $visibleFields, true)) {
            $line1Parts[] = "UUID: {$shortUuid}";
        }
        if (in_array('downloaded_at', $visibleFields, true)) {
            $line1Parts[] = "{$downloadedAt->format('Y-m-d')} {$downloadedAt->format('H:i')}";
        }
        if (in_array('document_reference', $visibleFields, true)) {
            $line1Parts[] = "Réf: {$document->reference_number}";
        }

        $line2Parts = [];
        if (in_array('recipient_name', $visibleFields, true)) {
            $line2Parts[] = $userName;
        }
        if (in_array('recipient_institution', $visibleFields, true)) {
            $line2Parts[] = $institutionName;
        }
        if (in_array('recipient_email', $visibleFields, true) && ! empty($user->email)) {
            $line2Parts[] = (string) $user->email;
        }
        if (in_array('document_title', $visibleFields, true)) {
            $line2Parts[] = (string) $document->title;
        }

        $diagonalLine1 = $encode(implode(' | ', $line1Parts));
        $diagonalLine2 = $encode(implode(' | ', $line2Parts));

        $footerParts = [];
        if (in_array('download_uuid', $visibleFields, true)) {
            $footerParts[] = $shortUuid;
        }
        if (in_array('recipient_name', $visibleFields, true)) {
            $footerParts[] = $userName;
        }
        if (in_array('recipient_institution', $visibleFields, true)) {
            $footerParts[] = $institutionName;
        }
        if (in_array('downloaded_at', $visibleFields, true)) {
            $footerParts[] = $downloadedAt->format('Y-m-d H:i');
        }
        $footerText = $encode(implode(' | ', $footerParts));

        // --- PDF Info Dictionary Metadata (readable with pdfinfo / exiftool) ---
        $payload = [];
        if (in_array('recipient_name', $metadataFields, true)) {
            $payload['userId']   = 'USR-' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT);
            $payload['userName'] = $userName;
        }
        if (in_array('recipient_email', $metadataFields, true)) {
            $payload['userEmail'] = (string) ($user->email ?? '');
        }
        if (in_array('recipient_institution', $metadataFields, true)) {
            $payload['institutionCode'] = $institutionCode;
            $payload['institutionName'] = $institutionName;
        }
        if (in_array('document_title', $metadataFields, true)) {
            $payload['documentTitle'] = (string) $document->title;
        }
        if (in_array('document_reference', $metadataFields, true)) {
            $payload['documentReference'] = (string) $document->reference_number;
            $payload['documentVersion']   = $document->version_number;
        }
        $payload['documentId'] = 'DOC-' . str_pad((string) $document->id, 7, '0', STR_PAD_LEFT);
        $payload['downloadId'] = 'DL-' . str_pad((string) $downloadLog->id, 7, '0', STR_PAD_LEFT);
        if (in_array('downloaded_at', $metadataFields, true)) {
            $payload['downloadTimestamp'] = $downloadedAt->toIso8601String();
        }
        if (in_array('download_uuid', $metadataFields, true)) {
            $payload['watermarkUUID'] = $uuid;
        }
        $payload['ipAddress'] = (string) ($downloadLog->ip_address ?? '');

        $metadataPayload = json_encode($payload);

        $titleSuffix = in_array('document_title', $metadataFields, true)
            ? $document->title . ' [SIKDS-SECURED]'
            : '[SIKDS-SECURED]';
        $fpdi->SetTitle($titleSuffix);

        $authorParts = [];
        if (in_array('recipient_name', $metadataFields, true)) {
            $authorParts[] = $userName;
        }
        if (in_array('recipient_institution', $metadataFields, true)) {
            $authorParts[] = $institutionName;
        }
        if ($authorParts !== []) {
            $fpdi->SetAuthor(implode(' | ', $authorParts));
        }

        $fpdi->SetCreator('SIKDS v1.0');
        if (in_array('download_uuid', $metadataFields, true)) {
            $fpdi->SetSubject('SIKDS-UUID:' . $uuid);
        }
        $fpdi->SetKeywords((string) $metadataPayload);

        // Helper: compute the largest font size that fits inside maxWidth.
        $fitFontSize = function (RotatableFpdi $pdf, string $text, string $family, string $style, float $maxSize, float $maxWidth) : float {
            $size = $maxSize;
            while ($size > 6) {
                $pdf->SetFont($family, $style, $size);
                if ($pdf->GetStringWidth($text) <= $maxWidth) {
                    return $size;
                }
                $size -= 1;
            }
            return max($size, 6);
        };

        // --- Apply watermarks on every page ---
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $fpdi->importPage($pageNo);
            $size       = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // Disable auto page break so the footer doesn't trigger a new page
            $fpdi->SetAutoPageBreak(false, 0);

            $fpdi->useTemplate($templateId);

            $w  = (float) $size['width'];
            $h  = (float) $size['height'];
            $cx = $w / 2.0;
            $cy = $h / 2.0;

            // ---------------------------------------------------------------
            // Large diagonal watermark — rotated -45° (clockwise) around the
            // page centre so text runs from TOP-LEFT to BOTTOM-RIGHT.
            // Alpha 0.40 — clearly visible for traceability but still allows
            // the underlying content to be read.
            // ---------------------------------------------------------------
            if ($diagonalLine1 !== '' || $diagonalLine2 !== '') {
                $fpdi->setAlpha(0.40);
                $fpdi->SetTextColor(100, 100, 100);

                if ($diagonalLine1 !== '') {
                    $fontSize1 = $fitFontSize($fpdi, $diagonalLine1, 'Arial', 'B', 42, $w);
                    $fpdi->SetFont('Arial', 'B', $fontSize1);
                    $fpdi->rotate(-45.0, $cx, $cy);
                    $fpdi->SetXY(0, $cy - 14);
                    $fpdi->Cell($w, 14, $diagonalLine1, 0, 0, 'C');
                } else {
                    $fpdi->rotate(-45.0, $cx, $cy);
                }

                if ($diagonalLine2 !== '') {
                    $fontSize2 = $fitFontSize($fpdi, $diagonalLine2, 'Arial', 'B', 22, $w);
                    $fpdi->SetFont('Arial', 'B', $fontSize2);
                    $fpdi->SetXY(0, $cy + 2);
                    $fpdi->Cell($w, 10, $diagonalLine2, 0, 0, 'C');
                }

                $fpdi->endRotate();
                $fpdi->setAlpha(1.0);
            }

            // ---------------------------------------------------------------
            // Small footer strip — plain-text trace data at the very bottom.
            // Written with SetXY (no Ln/Cell newline) so it never triggers
            // a page break.
            // ---------------------------------------------------------------
            if ($footerText !== '') {
                $fpdi->setAlpha(0.40);
                $fpdi->SetFont('Arial', '', 7);
                $fpdi->SetTextColor(80, 80, 80);
                $fpdi->SetXY(5, $h - 8);
                $fpdi->Cell($w - 10, 4, $footerText, 0, 0, 'C');
                $fpdi->setAlpha(1.0);
            }
        }

        $outputFile = tempnam(sys_get_temp_dir(), 'sikds_out_') . '.pdf';
        $fpdi->Output('F', $outputFile);

        @unlink($tempOriginalFile);

        return $outputFile;
    }

    /**
     * Read a watermark field list from settings, normalize aliases,
     * drop unknowns, and fall back to defaults if nothing usable is set.
     *
     * @param  array<int, string>  $defaults
     * @return array<int, string>
     */
    private function resolveFields(string $key, array $defaults): array
    {
        $settings = $this->settings ?? app(SystemSettingsService::class);
        $raw = $settings->get($key, $defaults);

        if (! is_array($raw)) {
            $raw = $defaults;
        }

        $resolved = [];
        foreach ($raw as $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }
            $canonical = self::ALIASES[$field] ?? $field;
            if (! in_array($canonical, self::KNOWN_FIELDS, true)) {
                continue;
            }
            if (! in_array($canonical, $resolved, true)) {
                $resolved[] = $canonical;
            }
        }

        return $resolved === [] ? $defaults : $resolved;
    }
}
