<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

class DatabaseSeeder extends Seeder
{
    /**
     * Tables that should never be truncated
     */
    protected array $protectedTables = [
        'migrations', // preserve migration history
    ];

    public function run()
    {
        $this->command->info('Starting database seeding...');

        // 1️⃣ Disable foreign key checks for truncation and seeding
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


        // 2️⃣ Transactional JSON seeding
        $jsonPath = database_path('seeders/data');
        $files = File::exists($jsonPath) ? File::files($jsonPath) : [];

        if (!empty($files)) {
            DB::beginTransaction();
            try {
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

                DB::commit();
                $this->command->info('JSON seeders completed successfully.');
            } catch (Exception $e) {
                DB::rollBack();
                $this->command->error('JSON seeding failed: ' . $e->getMessage());
                throw $e;
            }
        } else {
            $this->command->warn('No JSON files found to seed.');
        }

        // 3️⃣ Call ITC seeder outside transaction
        $this->command->info('Starting ITC seeding...');
        $this->call([ItcSeeder::class]);
        $this->command->info('ITC seeding completed.');

        $this->command->info('Database seeding finished successfully!');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
