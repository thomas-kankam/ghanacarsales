<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Payment Pending Approval</title>
</head>
<body>
    <p>Hello Admin,</p>

    <p>A dealer payment has been completed and listing(s) are now pending approval.</p>

    <pre style="white-space: pre-wrap; font-family: Arial, sans-serif;">{{ $body }}</pre>

    <p>Recipient: {{ $adminEmail }}</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

