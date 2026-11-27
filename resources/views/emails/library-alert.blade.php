<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:24px 12px; background:#f1f4f8; color:#203047; font:16px/1.55 Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px; margin:0 auto; border:1px solid #d7e0eb; background:#ffffff;">
        <tr>
            <td style="padding:24px; background:#173456; color:#ffffff;">
                <p style="margin:0; font-size:21px; font-weight:bold;">UBNHS Library</p>
                <p style="margin:4px 0 0; font-size:14px;">Upper Bicutan National High School · {{ $section }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; overflow-wrap:anywhere;">
                <h1 style="margin:0 0 20px; font-size:24px; line-height:1.25;">{{ $title }}</h1>
                <p>Dear {{ $memberName }},</p>
                <p>{{ $details }}</p>
                <dl style="margin:20px 0; padding:14px 18px; border-left:3px solid #244a76; background:#f1f5fa;">
                    @foreach ($fields as $label => $value)
                        <dt style="font-size:13px; font-weight:bold;">{{ $label }}</dt>
                        <dd style="margin:0 0 9px;">{{ $value }}</dd>
                    @endforeach
                </dl>
                <p>{{ $instructions }}</p>
                <p style="margin-bottom:0;">Sincerely,<br><strong>UBNHS Library</strong></p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 24px; border-top:1px solid #d7e0eb; color:#43536a; font-size:13px;">
                This is an automated notice from the UBNHS Library Management System.
            </td>
        </tr>
    </table>
</body>
</html>
