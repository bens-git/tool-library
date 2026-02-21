<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\Archetype;
use Illuminate\Support\Facades\DB;

class MigrateItemCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:migrate-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing item codes to the new shorter format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting item code migration...');
        $this->info('New format: {PREFIX}-{SEQ} (e.g., AG-01)');

        // Get all items with their archetypes
        $items = Item::with('archetype')->get();

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $migratedCount = 0;
        $errorCount = 0;

        foreach ($items as $item) {
            try {
                // Get archetype name
                $archetypeName = $item->archetype?->name ?? 'ITEM';

                // Generate new code (date parameter is ignored now, kept for compatibility)
                $newCode = $this->generateItemCode($archetypeName, null);

                // Update the item
                $item->code = $newCode;
                $item->save();

                $migratedCount++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Error migrating item ID {$item->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete!");
        $this->info("Migrated: {$migratedCount} items");

        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount} items");
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a unique item code in the format: {PREFIX}-{SEQ}
     * Example: AG-01 (Angle Grinder - 01)
     */
    private function generateItemCode(string $archetypeName, ?string $dateString = null): string
    {
        $prefix = $this->getArchetypePrefix($archetypeName);

        // Check for existing codes with this prefix
        $existingCodes = DB::table('items')
            ->where('code', 'like', $prefix . '%')
            ->pluck('code')
            ->toArray();

        if (empty($existingCodes)) {
            return $prefix . '-01';
        }

        // Find the highest sequence number for this prefix
        $maxSeq = 0;
        foreach ($existingCodes as $code) {
            // Extract sequence number after the dash (e.g., AG-01 -> 1)
            if (preg_match('/^' . $prefix . '-(\d+)$/', $code, $matches)) {
                $seq = (int)$matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        $newSeq = str_pad((string)($maxSeq + 1), 2, '0', STR_PAD_LEFT);
        return $prefix . '-' . $newSeq;
    }

    /**
     * Generate a short prefix from archetype name
     */
    private function getArchetypePrefix(string $archetypeName): string
    {
        $words = preg_split('/[\s\-_]+/', $archetypeName);

        $filterWords = ['the', 'a', 'an', 'and', 'or', 'of', 'for', 'in', 'to', 'with'];
        $filteredWords = array_filter($words, function ($word) use ($filterWords) {
            return !empty($word) && !in_array(strtolower($word), $filterWords);
        });

        $filteredWords = array_values($filteredWords);

        if (empty($filteredWords)) {
            return strtoupper(substr($archetypeName, 0, 3));
        }

        if (count($filteredWords) === 1) {
            return strtoupper(substr($filteredWords[0], 0, 4));
        }

        $prefix = '';
        $count = min(count($filteredWords), 3);
        for ($i = 0; $i < $count; $i++) {
            $prefix .= strtoupper(substr($filteredWords[$i], 0, 1));
        }

        return $prefix;
    }
}
