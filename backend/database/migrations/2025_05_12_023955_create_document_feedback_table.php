<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->string('feedback_type'); // 'like', 'dislike', 'comment' など
            $table->text('content')->nullable();
            $table->string('feedbacker_ip');
            $table->string('feedbacker_user_agent')->nullable();
            $table->json('feedback_metadata')->nullable();
            $table->timestamps();

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_feedback');
    }
};
