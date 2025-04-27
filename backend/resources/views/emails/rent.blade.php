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
    <p><b> Please contact the item's owner to arrange the exact location!</b></p>

    <p> {{ $item->owner->discord_username?$item->owner->discord_username:$item->owner->name}}:

        @if( $item->owner->discord_username)
    <p>Discord username: {{ $item->owner->discord_username}}</p>
    @endif
    <p>Email {{ $item->owner->email}}</p>


    <p>Pickup time: {{ $rental->starts_at }}</p>
    <p>Return time: {{ $rental->ends_at }}</p>
    <p>Thank you!</p>
</body>

</html>