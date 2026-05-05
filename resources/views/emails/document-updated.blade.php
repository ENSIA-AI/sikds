<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document mis à jour</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Inter,Arial,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border:1px solid rgba(0,0,0,.08);border-radius:16px;overflow:hidden;">
            <div style="padding:18px 20px;background:#0b1a3a;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">Ministère de l'Enseignement Supérieur et de la Recherche Scientifique</div>
                <div style="font-size:14px;font-weight:600;margin-top:6px;opacity:.9;">SIKDS</div>
                <div style="font-size:18px;font-weight:700;margin-top:4px;">Document mis à jour</div>
            </div>
            <div style="padding:20px;">
                <p style="margin:0 0 12px 0;font-size:14px;">Bonjour {{ $recipient->full_name ?: $recipient->email }},</p>
                <p style="margin:0 0 14px 0;font-size:14px;">
                    Une nouvelle version d'un document existant est disponible.
                </p>

                <div style="border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:14px;">
                    <div style="font-size:12px;color:#64748b;">Référence</div>
                    <div style="font-size:15px;font-weight:700;margin-top:2px;">{{ $document->reference_number }}</div>

                    <div style="height:10px"></div>
                    <div style="font-size:12px;color:#64748b;">Titre</div>
                    <div style="font-size:14px;font-weight:600;margin-top:2px;">{{ $document->title }}</div>

                    <div style="height:10px"></div>
                    <div style="font-size:12px;color:#64748b;">Version</div>
                    <div style="font-size:14px;margin-top:2px;">
                        <span style="color:#64748b;text-decoration:line-through;">v{{ $previousVersion }}</span>
                        <span style="margin:0 6px;color:#94a3b8;">→</span>
                        <span style="font-weight:700;color:#0b1a3a;">v{{ $newVersion }}</span>
                    </div>

                    @if (!empty($changeSummary))
                        <div style="height:12px"></div>
                        <div style="font-size:12px;color:#64748b;">Résumé des modifications</div>
                        <div style="font-size:13px;margin-top:4px;line-height:1.55;color:#0f172a;background:#f8fafc;border:1px solid rgba(0,0,0,.05);border-radius:10px;padding:10px;">
                            {{ $changeSummary }}
                        </div>
                    @endif

                    @if (!empty($documentTags))
                        <div style="height:12px"></div>
                        <div style="font-size:12px;color:#64748b;">Tags</div>
                        <div style="margin-top:6px;">
                            @foreach ($documentTags as $tag)
                                <span style="display:inline-block;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:600;background:{{ $tag['color'] ?? '#eef2ff' }};color:#0f172a;border:1px solid rgba(0,0,0,.06);margin:0 4px 4px 0;">
                                    @if (!empty($tag['category']))
                                        <span style="opacity:.65;font-weight:500;">{{ $tag['category'] }}:</span>
                                    @endif
                                    {{ $tag['name'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div style="margin-top:16px;">
                    <a href="{{ url('/documents/'.$document->id) }}"
                       style="display:inline-block;background:#44517A;color:#fff;text-decoration:none;padding:10px 14px;border-radius:12px;font-size:14px;font-weight:700;">
                        Voir la nouvelle version
                    </a>
                </div>
            </div>

            <div style="padding:14px 20px;border-top:1px solid rgba(0,0,0,.06);background:#f9fafb;font-size:11px;color:#64748b;line-height:1.55;">
                Cet email a été envoyé automatiquement par <strong>SIKDS</strong> — Système d'Information de Knowledge & Document Sharing.<br>
                Pour toute question : <a href="mailto:{{ $supportContact }}" style="color:#44517A;text-decoration:none;">{{ $supportContact }}</a>.
            </div>
        </div>
    </div>
</body>
</html>
