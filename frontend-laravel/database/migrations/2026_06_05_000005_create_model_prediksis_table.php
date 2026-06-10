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
        Schema::create('model_prediksis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_generate');
            $table->string('metode_optimasi');
            $table->double('parameter_c');
            $table->double('parameter_epsilon');
            $table->double('parameter_gamma');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_prediksis');
    }
};
