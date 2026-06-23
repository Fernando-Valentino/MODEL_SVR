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
        Schema::create('model_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_run_id')->constrained('model_runs')->onDelete('cascade');
            $table->double('mae');
            $table->double('rmse');
            $table->double('mape');
            $table->double('r2_score');
            $table->double('accuracy');
            $table->string('dataset_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_metrics');
    }
};
