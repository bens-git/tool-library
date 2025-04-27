<!DOCTYPE html>
<html>

<head>
    <title>Item loaned</title>
</head>

<body>
    <p>Hello {{ $item->owner->name }},</p>
    <p>You have loaned the following item: {{ $item->item_name }}</p>
    <p>Pickup location:
        {{ $rental->location->city }},
        {{ $rental->location->state }},
        {{ $rental->location->country }}
    </p>
    <p><b> Please contact the renter to arrange the exact location!</b></p>
    <p> {{ $rental->renter->discord_username?$rental->renter->discord_username:$rental->renter->name}}:

        @if( $rental->renter->discord_username)
    <p>Discord username: {{ $rental->renter->discord_username}}</p>
    @endif
    <p>Email {{ $rental->renter->email}}</p>

    <p>Pickup time: {{ $rental->starts_at }}</p>
    <p>Return time: {{ $rental->ends_at }}</p>
    <p>Thank you!</p>
</body>

</html>