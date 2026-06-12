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
        Schema::create('hasil_optimasiss', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('model_prediksis')->onDelete('cascade');
            $table->string('metode');
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
        Schema::dropIfExists('hasil_optimasiss');
    }
};
