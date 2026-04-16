<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }
        DB::table('guest_registration_fields')
            ->where('section_key', 'spouse_info')
            ->update(['section_order' => 15]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }
        DB::table('guest_registration_fields')
            ->where('section_key', 'spouse_info')
            ->update(['section_order' => 65]);
    }
};
