<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateItemsTableAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('code')->unique()->nullable();
        });

        // Update existing items with unique code
        DB::table('items')->get()->each(function ($item) {
            $typeName = DB::table('types')->where('id', $item->type_id)->value('name');
            $dateString = Carbon::parse($item->purchased_at)->format('d-m-y');

            $code = $discordUserName . '_' .
                strtolower(str_replace(' ', '', $typeName)) . '_' .
                $dateString;

            $uniqueCode = getUniqueString('items', 'code', $code);



            DB::table('items')->where('id', $item->id)->update(['code' => $uniqueCode]);
        });

        DB::statement("ALTER TABLE items MODIFY COLUMN code VARCHAR(255) NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
}
