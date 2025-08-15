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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('document_id')->constrained();
            $table->string('title');
            $table->string('description');
            $table->timestamp('startDate');
            $table->timestamp('endDate');
            $table->integer('priority');
            $table->foreignId('tag_id')->constrained();
            $table->integer('parentTaskId')->nullable();
            $table->integer('status');
            $table->timestamps();
        });
    }
};
