<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.  ddf 
     */
    public function up(): void
    {
       Schema::create('ftir_vectors', function (Blueprint $table) {
    $table->id();

    // Debe coincidir con FTIR_FILES.ftir_id (INT)
    $table->integer('ftir_id')->unique();

    $table->integer('grid_start');
    $table->integer('grid_end');
    $table->integer('grid_step');

    $table->longText('y_der1_norm');
    $table->longText('y_norm')->nullable();

    $table->timestamps();

    $table->foreign('ftir_id')
        ->references('ftir_id')
        ->on('ftir_files')
        ->onDelete('cascade');
}); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ftir_vectors');
    }
};
