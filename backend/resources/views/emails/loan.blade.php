<!DOCTYPE html>
<html>

<head>
    <title>Item loaned</title>
</head>

<body>
    <p>Hello {{ $user->name }},</p>
    <p>You have loaned the following item: {{ $item->item_name }}</p>
    <p>Pickup location:
        {{ $rental->location->city }},
        {{ $rental->location->state }},
        {{ $rental->location->country }}
    </p>
    <p>Pickup time: {{ $rental->starts_at }}</p>
    <p>Return time: {{ $rental->ends_at }}</p>
    <p>Thank you!</p>
</body>

</html>