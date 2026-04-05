<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\LeadSourceDatum;
use App\Models\StudentRevenue;
use App\Models\StudentType;
use App\Models\User;

class StudentSearchService
{
    public function search(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $idsFromAssignments = StudentAssignment::query()
            ->where('student_id', 'like', "%{$q}%")
            ->orWhere('senior_email', 'like', "%{$q}%")
            ->limit(30)
            ->pluck('student_id');

        $idsFromRevenue = StudentRevenue::query()
            ->where('student_id', 'like', "%{$q}%")
            ->limit(30)
            ->pluck('student_id');

        $guestMatches = GuestApplication::query()
            ->where(function ($qr) use ($q): void {
                $qr->where('converted_student_id', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhereRaw("first_name || ' ' || last_name like ?", ["%{$q}%"]);
            })
            ->whereNotNull('converted_student_id')
            ->latest('id')
            ->limit(40)
            ->get(['converted_student_id']);

        $idsFromUsers = User::query()
            ->where(function ($qr) use ($q): void {
                $qr->where('student_id', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->whereNotNull('student_id')
            ->limit(30)
            ->pluck('student_id');

        $studentIds = collect()
            ->concat($idsFromAssignments)
            ->concat($idsFromRevenue)
            ->concat($guestMatches->pluck('converted_student_id'))
            ->concat($idsFromUsers)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->take(40);

        if ($studentIds->isEmpty()) {
            return [];
        }

        $revenues = StudentRevenue::query()
            ->whereIn('student_id', $studentIds->all())
            ->get()
            ->keyBy('student_id');

        $assignments = StudentAssignment::query()
            ->whereIn('student_id', $studentIds->all())
            ->get()
            ->keyBy('student_id');

        $guests = GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds->all())
            ->latest('id')
            ->get()
            ->unique('converted_student_id')
            ->keyBy('converted_student_id');

        $guestIds = $guests
            ->pluck('id')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();
        $leadByGuestId = $guestIds->isEmpty()
            ? collect()
            : LeadSourceDatum::query()
                ->whereIn('guest_id', $guestIds->all())
                ->get()
                ->keyBy(fn (LeadSourceDatum $row) => (string) $row->guest_id);
        $legacyLeadByStudentId = LeadSourceDatum::query()
            ->whereIn('guest_id', $studentIds->all())
            ->get()
            ->keyBy(fn (LeadSourceDatum $row) => (string) $row->guest_id);

        $users = User::query()
            ->whereIn('student_id', $studentIds->all())
            ->get()
            ->keyBy('student_id');

        $studentTypes = StudentType::query()
            ->get(['id_prefix', 'name_tr'])
            ->keyBy(fn (StudentType $t) => strtoupper((string) $t->id_prefix));

        $result = [];

        foreach ($studentIds as $studentId) {
            $row = $revenues->get($studentId);
            $assignment = $assignments->get($studentId);
            $guest = $guests->get($studentId);
            $user = $users->get($studentId);

            $lead = null;
            if ($guest && $guest->id) {
                $lead = $leadByGuestId->get((string) $guest->id);
            }
            if (!$lead) {
                $lead = $legacyLeadByStudentId->get((string) $studentId);
            }
            $prefix = strtoupper(substr($studentId, 0, 3));
            $type = $studentTypes->get($prefix);
            $displayName = trim((string) (($guest?->first_name ?? '').' '.($guest?->last_name ?? '')));
            if ($displayName === '') {
                $displayName = trim((string) ($user?->name ?? ''));
            }
            if ($displayName === '') {
                $displayName = $studentId;
            }

            $result[] = [
                'student_id' => $studentId,
                'display_name' => $displayName,
                'email' => $guest?->email ?? $user?->email,
                'student_type' => $type?->name_tr,
                'package_id' => $row?->package_id,
                'package_total_price' => $row?->package_total_price,
                'package_currency' => $row?->package_currency,
                'total_earned' => $row?->total_earned,
                'total_pending' => $row?->total_pending,
                'total_remaining' => $row?->total_remaining,
                'lead_source' => $lead?->initial_source,
                'verified_source' => $lead?->verified_source,
                'last_updated' => optional($row?->updated_at ?? $assignment?->updated_at ?? $guest?->updated_at)?->toDateTimeString(),
                'senior_email' => $assignment?->senior_email,
                'branch' => $assignment?->branch,
                'risk_level' => $assignment?->risk_level,
            ];
        }

        return collect($result)
            ->unique(fn (array $r) => (string) ($r['student_id'] ?? ''))
            ->values()
            ->all();
    }
}
