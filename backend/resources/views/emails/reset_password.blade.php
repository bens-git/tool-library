<!DOCTYPE html>
<html>

<head>
    <title>Reset Your Password</title>
</head>

<body>
    <p>Hello {{ $user->name }},</p>
    <p>Please click the link below to reset your password:</p>
    <a href="{{ config('app.front_end_url') . '/reset-password?password_reset_token=' . $user->password_reset_token }}">Reset Password</a>
    <p>Thank you!</p>
</body>

</html>