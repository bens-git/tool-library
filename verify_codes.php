<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = \App\Models\Item::select('id', 'code', 'archetype_id')->with('archetype:name')->take(20)->get();

echo "Sample of migrated item codes:\n";
echo str_repeat('-', 60) . "\n";

foreach ($items as $item) {
    $archetypeName = $item->archetype?->name ?? 'Unknown';
    echo sprintf("%-3d | %-12s | %s\n", $item->id, $item->code, substr($archetypeName, 0, 30));
}

echo str_repeat('-', 60) . "\n";
echo "Total items: " . \App\Models\Item::count() . "\n";

