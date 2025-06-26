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
        // Primero, actualizar los registros existentes que tienen verification_code nulo
        $colegios = DB::table('colegios')->whereNull('verification_code')->get();
        foreach ($colegios as $colegio) {
            $codigo = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            DB::table('colegios')
                ->where('id', $colegio->id)
                ->update(['verification_code' => $codigo]);
        }

        // Luego, hacer que la columna no sea nullable
        Schema::table('colegios', function (Blueprint $table) {
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