Upper Bicutan National High School Library
Your library account is ready

Hi {!! $name !!},

Sign in to the library system with these details.
Login email: {!! $email !!}
Temporary password: {!! $temporaryPassword !!}

After signing in, go to My Profile → Change Password to set your own password.

Membership details
Member type: {!! $memberType !!}
@if ($department)
Subject department: {!! $department !!}
@endif
{!! $memberType === 'Faculty' ? 'DepEd employee number' : 'Student LRN' !!}: {!! $maskedReference !!}
Member QR ID: {!! $qrId !!}

Keep this email private. If you received it by mistake, contact the library and delete the message.
