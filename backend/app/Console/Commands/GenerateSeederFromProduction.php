<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class GenerateSeederFromProduction extends Command
{
    protected $signature = 'generate:seeder';
    protected $description = 'Fetch all production data and create a seeder for the development server';

    public function handle()
    {
        $this->info("Connecting to production database...");

        // Set production database connection
        Config::set([
            'database.connections.production' => [
                'driver'   => env('DB_CONNECTION'),
                'host'     => env('PROD_DB_HOST'),
                'port'     => env('PROD_DB_PORT', '3306'),
                'database' => env('PROD_DB_DATABASE'),
                'username' => env('PROD_DB_USERNAME'),
                'password' => env('PROD_DB_PASSWORD'),
            ]
        ]);

        $productionDb = DB::connection('production');
        $tables = $productionDb->select("SHOW TABLES");

        if (!$tables) {
            $this->error("No tables found in the production database.");
            return;
        }

        // Extract table names
        $database = env('PROD_DB_DATABASE');
        $key = "Tables_in_{$database}";
        $tableNames = array_column($tables, $key);

         // Exclude 'migrations' table from the list of tables
         $tableNames = array_filter($tableNames, function ($table) {
            return $table !== 'migrations';
        });

        $seederContent = "<?php\n\n";
        $seederContent.= "namespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\nuse Illuminate\Support\Facades\DB;\n\n";
        $seederContent .= "class ProductionDataSeeder extends Seeder\n{\n    public function run()\n    {\n";

        foreach ($tableNames as $table) {
            $this->info("Fetching data from $table...");
            $rows = $productionDb->table($table)->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $seederContent .= "        DB::table('$table')->insert([\n";

            foreach ($rows as $row) {
                $seederContent .= "            " . var_export((array)$row, true) . ",\n";
            }

            $seederContent .= "        ]);\n\n";
        }

        $seederContent .= "    }\n}\n";

        $seederPath = database_path('seeders/ProductionDataSeeder.php');

        File::put($seederPath, $seederContent);

        $this->info("Seeder created successfully: database/seeders/ProductionDataSeeder.php");
    }
}
