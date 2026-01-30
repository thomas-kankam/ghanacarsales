<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Account Credentials</title>
</head>
<body>
    <h2>Welcome to Ghana Car Sales Admin Panel</h2>
    <p>Your admin account has been created successfully.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please login and change your password immediately.</p>
    <p>Login URL: {{ config('app.url') }}/admin/login</p>
</body>
</html>
