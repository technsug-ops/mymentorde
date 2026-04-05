<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // guest_applications: company_id filtresi her istekte kullanılıyor (Manager portal, CSV export)
        if (Schema::hasTable('guest_applications')) {
            Schema::table('guest_applications', function (Blueprint $table) {
                if (!$this->indexExists('guest_applications', 'guest_applications_company_id_index')) {
                    $table->index('company_id', 'guest_applications_company_id_index');
                }
                if (!$this->indexExists('guest_applications', 'guest_applications_company_id_lead_status_index')) {
                    $table->index(['company_id', 'lead_status'], 'guest_applications_company_id_lead_status_index');
                }
            });
        }

        // student_assignments: company_id + senior_email filtrelemesi sık kullanılıyor
        if (Schema::hasTable('student_assignments') && Schema::hasColumn('student_assignments', 'company_id')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                if (!$this->indexExists('student_assignments', 'student_assignments_company_id_index')) {
                    $table->index('company_id', 'student_assignments_company_id_index');
                }
            });
        }

        // student_revenues: hiç index yok, dashboard aggregation'ları yavaş
        if (Schema::hasTable('student_revenues')) {
            Schema::table('student_revenues', function (Blueprint $table) {
                if (!$this->indexExists('student_revenues', 'student_revenues_student_id_index')) {
                    $table->index('student_id', 'student_revenues_student_id_index');
                }
                if (Schema::hasColumn('student_revenues', 'senior_email') && !$this->indexExists('student_revenues', 'student_revenues_senior_email_index')) {
                    $table->index('senior_email', 'student_revenues_senior_email_index');
                }
            });
        }

        // notification_dispatches: (status, sent_at) ile 24h count sorgusu kullanılıyor
        if (Schema::hasTable('notification_dispatches')) {
            Schema::table('notification_dispatches', function (Blueprint $table) {
                if (!$this->indexExists('notification_dispatches', 'notification_dispatches_status_sent_at_index')) {
                    $table->index(['status', 'sent_at'], 'notification_dispatches_status_sent_at_index');
                }
            });
        }

        // dm_messages: is_read flagleri üzerinde unread count sorguları var
        if (Schema::hasTable('dm_messages')) {
            Schema::table('dm_messages', function (Blueprint $table) {
                if (!$this->indexExists('dm_messages', 'dm_messages_thread_id_advisor_index')) {
                    $table->index(['thread_id', 'is_read_by_advisor'], 'dm_messages_thread_id_advisor_index');
                }
                if (!$this->indexExists('dm_messages', 'dm_messages_thread_id_participant_index')) {
                    $table->index(['thread_id', 'is_read_by_participant'], 'dm_messages_thread_id_participant_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guest_applications')) {
            Schema::table('guest_applications', function (Blueprint $table) {
                $table->dropIndexIfExists('guest_applications_company_id_index');
                $table->dropIndexIfExists('guest_applications_company_id_lead_status_index');
            });
        }

        if (Schema::hasTable('student_assignments')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                $table->dropIndexIfExists('student_assignments_company_id_index');
            });
        }

        if (Schema::hasTable('student_revenues')) {
            Schema::table('student_revenues', function (Blueprint $table) {
                $table->dropIndexIfExists('student_revenues_student_id_index');
                $table->dropIndexIfExists('student_revenues_senior_email_index');
            });
        }

        if (Schema::hasTable('notification_dispatches')) {
            Schema::table('notification_dispatches', function (Blueprint $table) {
                $table->dropIndexIfExists('notification_dispatches_status_sent_at_index');
            });
        }

        if (Schema::hasTable('dm_messages')) {
            Schema::table('dm_messages', function (Blueprint $table) {
                $table->dropIndexIfExists('dm_messages_thread_id_advisor_index');
                $table->dropIndexIfExists('dm_messages_thread_id_participant_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $driver = \DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $result = \DB::select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?",
                    [$table, $indexName]
                );
            } else {
                $result = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            }
            return count($result) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
