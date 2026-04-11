<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: user_two_factor.secret VARCHAR(255) overflow.
 *
 * Laravel's encrypt() produces a base64-wrapped JSON payload
 * (cipher + IV + MAC + tag) that can reach ~350-400 characters
 * for typical TOTP secrets. The original migration used the
 * default string() (VARCHAR 255), causing:
 *
 *   SQLSTATE[22001]: Data too long for column 'secret' at row 1
 *
 * Widening to TEXT (65535 chars) gives plenty of headroom and
 * doesn't affect local SQLite (it ignores length anyway).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_two_factor')) {
            return;
        }

        Schema::table('user_two_factor', function (Blueprint $table): void {
            $table->text('secret')->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_two_factor')) {
            return;
        }

        Schema::table('user_two_factor', function (Blueprint $table): void {
            $table->string('secret')->change();
        });
    }
};
