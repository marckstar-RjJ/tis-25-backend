<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('colegios', function (Blueprint $table) {
            // Primero, actualizar los registros existentes que tienen verification_code nulo
            DB::table('colegios')
                ->whereNull('verification_code')
                ->update(['verification_code' => DB::raw("LPAD(FLOOR(RAND() * 10000), 4, '0')")]);

            // Luego, hacer que la columna no sea nullable
            $table->string('verification_code', 4)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colegios', function (Blueprint $table) {
            $table->string('verification_code', 4)->nullable()->change();
        });
    }
}; 