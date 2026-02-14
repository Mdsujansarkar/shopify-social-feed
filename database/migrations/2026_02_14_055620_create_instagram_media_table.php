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
        Schema::create('instagram_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('instagram_media_id')->unique();
            $table->enum('media_type', ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM']);
            $table->string('media_url');
            $table->text('caption')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_media');
    }
};
