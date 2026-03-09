<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

class DatabaseSeeder extends Seeder
{
    /**
     * Tables that should never be truncated by this seeder
     */
    protected array $protectedTables = [
        'migrations', // keep migration history intact
    ];

    public function run()
    {
        // Wrap the seeding process in a DB transaction
        DB::beginTransaction();

        try {
            // Disable foreign key checks for truncation
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $tables = DB::select('SHOW TABLES');
            $database = config('database.connections.mysql.database');
            $key = "Tables_in_{$database}";

            foreach ($tables as $table) {
                $tableName = $table->$key;

                if (in_array($tableName, $this->protectedTables)) {
                    $this->command->info("Skipping protected table: $tableName");
                    continue;
                }

                $this->command->info("Truncating table: $tableName");
                DB::table($tableName)->truncate();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Seed JSON data
            $jsonPath = database_path('seeders/data');
            $files = File::exists($jsonPath) ? File::files($jsonPath) : [];

            foreach ($files as $file) {
                $tableName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $this->command->info("Seeding table: $tableName from {$file->getFilename()}");

                $json = File::get($file->getRealPath());
                $data = json_decode($json, true);

                if (empty($data)) {
                    $this->command->warn("No data found in {$file->getFilename()}, skipping.");
                    continue;
                }

                DB::table($tableName)->insert(array_map(function ($row) {
                    $row['created_at'] = $row['created_at'] ?? now();
                    $row['updated_at'] = $row['updated_at'] ?? now();
                    return $row;
                }, $data));
            }

            // Seed ITC data
            $this->call([ItcSeeder::class]);

            // Commit the transaction
            DB::commit();
            $this->command->info('All seeders finished successfully.');
        } catch (Exception $e) {
            // Rollback everything if something fails
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}