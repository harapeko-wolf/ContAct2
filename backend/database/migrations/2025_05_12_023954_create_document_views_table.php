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
        Schema::create('document_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->string('viewer_ip');
            $table->string('viewer_user_agent')->nullable();
            $table->integer('page_number');
            $table->integer('view_duration')->nullable(); // 秒単位
            $table->timestamp('viewed_at');
            $table->json('viewer_metadata')->nullable();
            $table->timestamps();

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_views');
    }
};
