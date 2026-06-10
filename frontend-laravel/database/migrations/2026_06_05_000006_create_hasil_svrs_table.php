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
        Schema::create('hasil_svrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('model_prediksis')->onDelete('cascade');
            $table->date('tanggal');
            $table->double('nilai_aktual');
            $table->double('nilai_prediksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_svrs');
    }
};
