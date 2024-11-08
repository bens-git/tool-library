<!DOCTYPE html>
<html>

<head>
    <title>Overdue Rental Notification</title>
</head>

<body>
    <h1>Rental Overdue Notice</h1>
    <p>Dear {{ $rental->renter->discord_username }},</p>
    <p>This is a reminder that the following rental item is overdue for return:</p>

    <p><strong>Item:</strong> {{ $itemName }}</p>
    <p><strong>Due Date:</strong> {{ $rental->ends_at }}</p>

    <p>Please return the item as soon as possible. Thank you.</p>

    <p>Regards,</p>
    <p>Your Tool Library</p>
</body>

</html>