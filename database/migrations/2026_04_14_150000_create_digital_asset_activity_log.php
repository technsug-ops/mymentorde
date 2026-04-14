<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAM2 — Aktivite log: kim ne zaman ne yaptı?
 *
 * Action types (enforced in code, not DB): upload, download, update, delete,
 * move, rename, restore, share, favorite_add, favorite_remove, folder_create,
 * folder_rename, folder_move, folder_delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_asset_activity_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 150)->nullable(); // user silinse bile görünür kalsın
            $table->string('action', 40); // upload, download, update, ...
            $table->string('target_type', 40); // asset | folder
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 255)->nullable();
            $table->json('meta')->nullable(); // ek bilgi (eski ad, yeni klasör id vb)
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index(['action', 'created_at']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_asset_activity_log');
    }
};
