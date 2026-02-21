<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!function_exists('getEnumValues')) {
    /**
     * Retrieve ENUM values for a specific column in a MySQL table.
     *
     * @param string $table  The name of the table.
     * @param string $column The name of the ENUM column.
     * @return array         The list of ENUM values.
     * @throws Exception     If the column is not found.
     */
    function getEnumValues(string $table, string $column): array
    {
        // Query the information schema for the column definition
        $result = DB::table('information_schema.columns')
            ->select('COLUMN_TYPE')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->first();

        if (!$result) {
            throw new \Exception("Column `$column` not found in table `$table`.");
        }

        // Extract ENUM values using regex
        preg_match('/^enum\((.*)\)$/', $result->COLUMN_TYPE, $matches);

        // Parse and return the values as an array
        return array_map(function ($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
    }
}


if (!function_exists('getUniqueString')) {
    /**
     * check if string exists, count how many times it exists, and append _n where n is the next available number.
     *
     * @param string $table  The name of the table.
     * @param string $column The name of the column.
     * @param string $string The string to check.
     * @return string         The unique string.
     * @throws Exception     
     */

    function getUniqueString($table, $column, $string)
    {
        $originalString = $string;
        $n = 0;

        while (DB::table($table)->where($column, $string)->exists()) {
            $n++;
            $string = $originalString . '_' . $n;
        }

        return $string;
    }
}

if (!function_exists('getArchetypePrefix')) {
    /**
     * Generate a short prefix (2-4 characters) from an archetype name.
     *
     * @param string $archetypeName The name of the archetype.
     * @return string The short prefix in uppercase.
     */
    function getArchetypePrefix(string $archetypeName): string
    {
        // Remove common words and get first letters
        $words = preg_split('/[\s\-_]+/', $archetypeName);
        
        // Filter out empty values and common words
        $filterWords = ['the', 'a', 'an', 'and', 'or', 'of', 'for', 'in', 'to', 'with'];
        $filteredWords = array_filter($words, function($word) use ($filterWords) {
            return !empty($word) && !in_array(strtolower($word), $filterWords);
        });
        
        $filteredWords = array_values($filteredWords);
        
        if (empty($filteredWords)) {
            // Fallback: use first 3 letters of original name
            return strtoupper(substr($archetypeName, 0, 3));
        }
        
        if (count($filteredWords) === 1) {
            // Single word: take first 3-4 characters
            return strtoupper(substr($filteredWords[0], 0, 4));
        }
        
        // Multiple words: get first letter of each (max 3)
        $prefix = '';
        $count = min(count($filteredWords), 3);
        for ($i = 0; $i < $count; $i++) {
            $prefix .= strtoupper(substr($filteredWords[$i], 0, 1));
        }
        
        return $prefix;
    }
}

if (!function_exists('generateItemCode')) {
    /**
     * Generate a unique item code in the format: {PREFIX}-{SEQ}
     * Example: AG-01 (Angle Grinder - 01)
     * Simple and short format for mobile display
     *
     * @param string $archetypeName The name of the archetype.
     * @param string|null $dateString (Deprecated, kept for compatibility)
     * @return string The unique item code.
     */
    function generateItemCode(string $archetypeName, ?string $dateString = null): string
    {
        $prefix = getArchetypePrefix($archetypeName);
        
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
}
