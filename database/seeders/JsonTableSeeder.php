<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

abstract class JsonTableSeeder extends Seeder
{
    abstract protected function getFileName(): string;
    abstract protected function getTableName(): string;

    protected function transformRow(array $row): array
    {
        // Override in child class if needed
        return $row;
    }

    public function run()
    {
        $path = database_path('seeders/data/' . $this->getFileName());

        if (!File::exists($path)) {
            $this->command->error("File not found: {$path}");
            return;
        }

        $json = File::get($path);
        $data = json_decode($json, true);

        DB::table($this->getTableName())->truncate();

        foreach ($data as $row) {
            DB::table($this->getTableName())->insert(
                $this->transformRow($row)
            );
        }

        $this->command->info($this->getTableName() . ' seeded!');
    }
}
