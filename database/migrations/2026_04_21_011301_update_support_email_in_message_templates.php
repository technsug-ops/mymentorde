<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_templates', 'reply_to')) {
            DB::table('email_templates')
                ->where('reply_to', 'destek@mentorde.com')
                ->update(['reply_to' => 'support@mentorde.com']);
        }

        // Bazı ortamlarda message_templates de reply_to tutuyor olabilir (ileride eklenirse)
        if (Schema::hasTable('message_templates') && Schema::hasColumn('message_templates', 'reply_to')) {
            DB::table('message_templates')
                ->where('reply_to', 'destek@mentorde.com')
                ->update(['reply_to' => 'support@mentorde.com']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_templates', 'reply_to')) {
            DB::table('email_templates')
                ->where('reply_to', 'support@mentorde.com')
                ->update(['reply_to' => 'destek@mentorde.com']);
        }
        if (Schema::hasTable('message_templates') && Schema::hasColumn('message_templates', 'reply_to')) {
            DB::table('message_templates')
                ->where('reply_to', 'support@mentorde.com')
                ->update(['reply_to' => 'destek@mentorde.com']);
        }
    }
};
