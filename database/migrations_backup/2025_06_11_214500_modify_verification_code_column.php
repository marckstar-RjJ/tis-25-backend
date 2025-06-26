<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Primero, actualizar los registros existentes que tengan verification_code NULL
        DB::table('colegios')
            ->whereNull('verification_code')
            ->update([
                'verification_code' => DB::raw("LPAD(FLOOR(random() * 10000)::text, 4, '0')")
            ]);

        // Luego, modificar la columna para que no sea nullable
        Schema::table('colegios', function (Blueprint $table) {
            $table->string('verification_code', 4)->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('colegios', function (Blueprint $table) {
            $table->string('verification_code', 4)->nullable()->change();
        });
    }
}; 