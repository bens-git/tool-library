<!DOCTYPE html>
<html>

<head>
    <title>Item rented</title>
</head>

<body>
    <p>Hello {{ $user->name }},</p>
    <p>You have rented the following item: {{ $item->item_name }}</p>
    <p>Pickup location:
        {{ $rental->location->city }},
        {{ $rental->location->state }},
        {{ $rental->location->country }}
    </p>
    <p><b> Contact the owner, {{ $item->owner->discord_username}}, on <a href='https://discord.gg/sFqqtaVu'>Discord</a> to arrange the exact location!</b></p>
    <p>Pickup time: {{ $rental->starts_at }}</p>
    <p>Return time: {{ $rental->ends_at }}</p>
    <p>Thank you!</p>
</body>

</html>