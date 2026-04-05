<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_assignments', function (Blueprint $table): void {
            if (!Schema::hasColumn('student_assignments', 'internal_sequence')) {
                $table->unsignedInteger('internal_sequence')->nullable()->after('student_id');
                $table->index('internal_sequence');
            }
        });

        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'internal_sequence')) {
                $table->unsignedInteger('internal_sequence')->nullable()->after('code');
                $table->index('internal_sequence');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'senior_internal_sequence')) {
                $table->unsignedInteger('senior_internal_sequence')->nullable()->after('senior_code');
                $table->index('senior_internal_sequence');
            }
        });

        $studentSeq = 0;
        $studentRows = DB::table('student_assignments')->orderBy('id')->get(['id']);
        foreach ($studentRows as $row) {
            $studentSeq++;
            DB::table('student_assignments')->where('id', $row->id)->update(['internal_sequence' => $studentSeq]);
        }

        $dealerSeq = 0;
        $dealerRows = DB::table('dealers')->orderBy('id')->get(['id']);
        foreach ($dealerRows as $row) {
            $dealerSeq++;
            DB::table('dealers')->where('id', $row->id)->update(['internal_sequence' => $dealerSeq]);
        }

        $seniorSeq = 0;
        $seniorRows = DB::table('users')->where('role', 'senior')->orderBy('id')->get(['id']);
        foreach ($seniorRows as $row) {
            $seniorSeq++;
            DB::table('users')->where('id', $row->id)->update(['senior_internal_sequence' => $seniorSeq]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'senior_internal_sequence')) {
                $table->dropIndex(['senior_internal_sequence']);
                $table->dropColumn('senior_internal_sequence');
            }
        });

        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'internal_sequence')) {
                $table->dropIndex(['internal_sequence']);
                $table->dropColumn('internal_sequence');
            }
        });

        Schema::table('student_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('student_assignments', 'internal_sequence')) {
                $table->dropIndex(['internal_sequence']);
                $table->dropColumn('internal_sequence');
            }
        });
    }
};

