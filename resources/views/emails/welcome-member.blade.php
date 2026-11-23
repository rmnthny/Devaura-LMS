<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your UBNHS Library account</title>
</head>
<body style="margin:0;background:#f1f5f9;color:#17263b;font:16px/1.55 Arial,sans-serif">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9">
        <tr>
            <td align="center" style="padding:24px 12px">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fff;border:1px solid #d8e1eb;border-radius:12px">
                    <tr>
                        <td style="padding:22px 24px;background:#17365d;color:#fff;border-radius:11px 11px 0 0">
                            <p style="margin:0;font-size:14px">Upper Bicutan National High School Library</p>
                            <h1 style="margin:4px 0 0;font-size:25px;line-height:1.25">Your library account is ready</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px">
                            <p style="margin:0 0 16px">Hi {{ $name }},</p>
                            <p style="margin:0 0 18px">Sign in to the library system using the email address and temporary password below.</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7fb;border-radius:8px">
                                <tr>
                                    <td style="padding:14px 16px;overflow-wrap:anywhere">
                                        <strong>Login email</strong><br>{{ $email }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px 14px;border-top:1px solid #d8e1eb">
                                        <strong>Temporary password</strong><br>
                                        <code style="font-size:16px">{{ $temporaryPassword }}</code>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 24px">After signing in, go to <strong>My Profile → Change Password</strong> to set your own password.</p>
                            <h2 style="margin:0 0 8px;font-size:17px">Membership details</h2>
                            <p style="margin:0;font-size:15px">
                                <strong>Member type:</strong> {{ $memberType }}<br>
                                @if ($department)
                                    <strong>Subject department:</strong> {{ $department }}<br>
                                @endif
                                <strong>{{ $memberType === 'Faculty' ? 'DepEd employee number' : 'Student LRN' }}:</strong> {{ $maskedReference }}<br>
                                <strong>Member QR ID:</strong> {{ $qrId }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f8fafc;color:#42546a;font-size:13px;border-radius:0 0 11px 11px">
                            <p style="margin:0 0 4px"><strong>Data privacy notice</strong></p>
                            <p style="margin:0">The library uses your school record to manage your account and library access. Keep this email private. If you received it by mistake, contact the library and delete the message.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
