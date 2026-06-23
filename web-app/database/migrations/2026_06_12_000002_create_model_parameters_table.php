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
        Schema::create('model_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_run_id')->constrained('model_runs')->onDelete('cascade');
            $table->string('kernel');
            $table->double('c_value');
            $table->double('epsilon_value');
            $table->string('gamma_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_parameters');
    }
};
