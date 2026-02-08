<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('ftir_files', function (Blueprint $table) {
            $table->string('hash_ftir', 64)->unique()->after('nombre_ftir'); // SHA-256 produce un hash de 64 caracteres
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ftir_files', function (Blueprint $table) {
            //
        });
    }
};
