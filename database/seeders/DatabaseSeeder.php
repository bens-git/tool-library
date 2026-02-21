<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');

        $database = config('database.connections.mysql.database');

        $key = "Tables_in_{$database}";

        foreach ($tables as $table) {
            DB::table($table->$key)->truncate();
        }


        // 2️⃣ Automatically seed all JSON files
        $jsonPath = database_path('seeders/data');
        $files = File::files($jsonPath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $tableName = pathinfo($fileName, PATHINFO_FILENAME); // file name without .json

            $this->command->info("Seeding table: $tableName from $fileName");

            $json = File::get($file->getRealPath());
            $data = json_decode($json, true);

            if (!$data) {
                $this->command->warn("No data found in $fileName, skipping.");
                continue;
            }

            DB::table($tableName)->insert(array_map(function ($row) {
                // Automatically add timestamps if missing
                if (!isset($row['created_at'])) $row['created_at'] = now();
                if (!isset($row['updated_at'])) $row['updated_at'] = now();
                return $row;
            }, $data));
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $this->command->info('All JSON seeders finished.');

        // Seed ITC data (balances and item access values)
        $this->call([
            ItcSeeder::class,
        ]);
    }
}
