<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>UBNHS-LMS Email Verification</h2>
    <p>Hello {{ $user->fullName() }},</p>
    <p>Use the 6-digit One-Time Password (OTP) below to verify your email address and activate your Library Member QR Code:</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 6px; margin: 20px 0;">{{ $code }}</p>
    <p>This code expires in {{ $expiresInMinutes }} minutes. If you did not create an account, you can ignore this email.</p>
</body>
</html>
