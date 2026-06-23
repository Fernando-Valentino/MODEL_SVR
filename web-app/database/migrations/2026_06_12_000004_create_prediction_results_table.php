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
        Schema::create('prediction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_run_id')->constrained('model_runs')->onDelete('cascade');
            $table->date('tanggal');
            $table->foreignId('rayon_id')->nullable()->constrained('rayons')->onDelete('set null');
            $table->string('rayon_name')->nullable();
            $table->double('actual_value');
            $table->double('predicted_value');
            $table->double('error_value');
            $table->double('percentage_error');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_results');
    }
};
