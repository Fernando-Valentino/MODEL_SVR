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
        Schema::create('model_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('model_type');
            $table->string('status');
            $table->integer('total_rows')->nullable();
            $table->integer('train_rows')->nullable();
            $table->integer('test_rows')->nullable();
            $table->string('train_period')->nullable();
            $table->string('test_period')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('created_by')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_runs');
    }
};
