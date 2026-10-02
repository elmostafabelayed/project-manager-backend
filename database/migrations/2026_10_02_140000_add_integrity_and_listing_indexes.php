<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->unique('user_id'));
        Schema::table('proposals', fn (Blueprint $table) => $table->unique(['project_id', 'freelancer_id']));
        Schema::table('contracts', fn (Blueprint $table) => $table->unique('project_id'));
        Schema::table('conversations', fn (Blueprint $table) => $table->unique(['project_id', 'client_id', 'freelancer_id']));
        Schema::table('reviews', fn (Blueprint $table) => $table->unique(['reviewer_id', 'project_id']));
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['status', 'id']);
            $table->index(['category', 'status', 'id']);
        });
        Schema::table('messages', fn (Blueprint $table) => $table->index(['conversation_id', 'id']));
        Schema::table('notifications', fn (Blueprint $table) => $table->index(['user_id', 'read_at', 'created_at']));
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropUnique(['user_id']));
        Schema::table('proposals', fn (Blueprint $table) => $table->dropUnique(['project_id', 'freelancer_id']));
        Schema::table('contracts', fn (Blueprint $table) => $table->dropUnique(['project_id']));
        Schema::table('conversations', fn (Blueprint $table) => $table->dropUnique(['project_id', 'client_id', 'freelancer_id']));
        Schema::table('reviews', fn (Blueprint $table) => $table->dropUnique(['reviewer_id', 'project_id']));
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status', 'id']);
            $table->dropIndex(['category', 'status', 'id']);
        });
        Schema::table('messages', fn (Blueprint $table) => $table->dropIndex(['conversation_id', 'id']));
        Schema::table('notifications', fn (Blueprint $table) => $table->dropIndex(['user_id', 'read_at', 'created_at']));
    }
};
