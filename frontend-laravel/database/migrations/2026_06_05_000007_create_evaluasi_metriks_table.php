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
        Schema::create('evaluasi_metriks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('model_prediksis')->onDelete('cascade');
            $table->double('mae');
            $table->double('rmse');
            $table->double('mape');
            $table->double('r2_score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_metriks');
    }
};
