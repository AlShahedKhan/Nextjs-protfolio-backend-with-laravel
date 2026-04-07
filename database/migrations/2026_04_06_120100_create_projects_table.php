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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->json('technologies');
            $table->string('live_url')->nullable();
            $table->string('github_url')->nullable();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('role')->nullable();
            $table->string('client_region')->nullable();
            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            $table->text('outcome')->nullable();
            $table->boolean('confidential')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
