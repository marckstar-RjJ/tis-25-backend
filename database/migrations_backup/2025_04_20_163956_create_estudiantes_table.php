<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('cascade');
            $table->date('fecha_nacimiento');
            $table->integer('curso');
            $table->unsignedBigInteger('colegio_id')->nullable();
            $table->string('celular');
            $table->string('nombre_tutor');
            $table->string('apellido_tutor');
            $table->string('email_tutor');
            $table->string('celular_tutor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
