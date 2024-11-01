<!DOCTYPE html>
<html>

<head>
    <title>Validate your email address</title>
</head>

<body>
    <p>Hello {{ $user->name }},</p>
    <p>Please click the link below to validate your email address:</p>
    <a href="{{ config('app.url') . '/email/validate/' . $user->email_verification_token }}">Validate Email</a>
    <p>Thank you!</p>
</body>

</html>