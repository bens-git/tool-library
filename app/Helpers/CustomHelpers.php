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
