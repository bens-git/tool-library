<!DOCTYPE html>
<html>

<head>
    <title>Item loaned</title>
</head>

<body>
    <p>Hello {{ $owner->discord_username }},</p>
    <p>You have loaned the following item: {{ $item->item_name }}</p>
    <p>Pickup location:
        {{ $rental->location->city }},
        {{ $rental->location->state }},
        {{ $rental->location->country }}
    </p>
    <p><b> Contact the renter, {{ $item->renter->discord_username}}, on <a href='https://discord.gg/sFqqtaVu'>Discord</a> to arrange the exact location!</b></p>
    <p>Pickup time: {{ $rental->starts_at }}</p>
    <p>Return time: {{ $rental->ends_at }}</p>
    <p>Thank you!</p>
</body>

</html>