<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GuestApplication;
use App\Models\GuestRequiredDocument;
use App\Models\GuestTicket;
use App\Models\KnowledgeBaseArticle;
use App\Models\StudentAppointment;
use App\Models\StudentMaterialRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_student_pages_load_successfully(): void
    {
        [$student] = $this->seedStudentContext();

        $this->actingAs($student);

        $pages = [
            '/student/dashboard',
            '/student/registration',
            '/student/registration/documents',
            '/student/process-tracking',
            '/student/document-builder',
            '/student/appointments',
            '/student/tickets',
            '/student/materials',
            '/student/contract',
            '/student/services',
            '/student/profile',
            '/student/settings',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_student_core_flows_work_end_to_end(): void
    {
        // Smoke test throttle davranışını test etmez; grup+route throttle aynı counter'ı paylaşır
        $this->withoutMiddleware(ThrottleRequests::class);

        Storage::fake('public');
        [$student, $guest] = $this->seedStudentContext();

        $this->actingAs($student);

        $this->post('/student/registration/form/auto-save', [
            'first_name' => 'Student',
            'last_name' => 'Smoke',
            'phone' => '5550000000',
        ])->assertRedirect('/student/registration');

        $category = DocumentCategory::query()->create([
            'code' => 'DOC-SMOKE',
            'name_tr' => 'Smoke Belgesi',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        GuestRequiredDocument::query()->create([
            'application_type' => 'bachelor',
            'document_code' => 'DOC-SMOKE',
            'category_code' => 'DOC-SMOKE',
            'name' => 'Smoke Belgesi',
            'is_required' => true,
            'accepted' => 'pdf,jpg,png',
            'max_mb' => 10,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->post(route('student.registration.documents.upload'), [
            'category_code' => 'DOC-SMOKE',
            'file' => UploadedFile::fake()->create('smoke.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('student.registration.documents'));

        $uploaded = Document::query()->where('student_id', 'BCS-26-02-SMK1')->latest('id')->first();
        $this->assertNotNull($uploaded);

        $this->delete(route('student.registration.documents.delete', $uploaded))->assertRedirect(route('student.registration.documents'));
        $this->assertSoftDeleted('documents', ['id' => $uploaded->id]);

        $this->post(route('student.tickets.store'), [
            'subject' => 'Smoke ticket',
            'message' => 'Test mesaji',
            'priority' => 'normal',
            'department' => 'operations',
        ])->assertRedirect('/student/tickets');

        $ticket = GuestTicket::query()->latest('id')->first();
        $this->assertNotNull($ticket);

        $this->post(route('student.tickets.reply', $ticket), [
            'message' => 'Reply smoke',
        ])->assertRedirect('/student/tickets');

        $this->post(route('student.tickets.close', $ticket))->assertRedirect('/student/tickets');
        $this->post(route('student.tickets.reopen', $ticket))->assertRedirect('/student/tickets');

        $this->post(route('student.appointments.store'), [
            'title' => 'Smoke Randevu',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 30,
            'channel' => 'online',
        ])->assertRedirect('/student/appointments');

        $appointment = StudentAppointment::query()->latest('id')->first();
        $this->assertNotNull($appointment);

        $this->post(route('student.appointments.cancel', $appointment), [
            'reason' => 'Smoke cancel',
        ])->assertRedirect('/student/appointments');

        $article = KnowledgeBaseArticle::query()->create([
            'title_tr' => 'Smoke Materyal',
            'body_tr' => 'Icerik',
            'category' => 'faq',
            'is_published' => true,
        ]);

        $this->post(route('student.materials.read', $article))->assertRedirect('/student/materials');
        $this->assertDatabaseHas('student_material_reads', [
            'student_id' => 'BCS-26-02-SMK1',
            'knowledge_base_article_id' => $article->id,
        ]);

        $this->post(route('student.services.select-package'), [
            'package_code' => 'pkg_plus',
            'package_title' => 'Plus Paket',
            'package_price' => '2490 EUR',
        ])->assertRedirect('/student/services');

        $this->post(route('student.services.add-extra'), [
            'extra_code' => 'vip_meeting',
            'extra_title' => 'VIP Gorusme',
        ])->assertRedirect('/student/services');

        $this->delete(route('student.services.remove-extra', 'vip_meeting'))->assertRedirect('/student/services');

        $guest->refresh();
        $guest->forceFill([
            'contract_status' => 'approved',
            'contract_approved_at' => now(),
        ])->save();

        $this->post(route('student.contract.addendum-request'), [
            'subject' => 'Sozlesme madde ek talebi',
            'message' => 'Odeme takvimi maddesinde guncelleme talep ediyorum.',
            'priority' => 'high',
        ])->assertRedirect('/student/contract');
    }

    /**
     * @return array{0:User,1:GuestApplication}
     */
    private function seedStudentContext(): array
    {
        User::query()->create([
            'name' => 'Manager',
            'email' => 'manager_smoke@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_MANAGER,
            'company_id' => 1,
            'is_active' => true,
        ]);

        $student = User::query()->create([
            'name' => 'Student Smoke',
            'email' => 'student_smoke@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_STUDENT,
            'student_id' => 'BCS-26-02-SMK1',
            'company_id' => 1,
            'is_active' => true,
        ]);

        $guest = GuestApplication::query()->create([
            'tracking_token' => 'TOK-STD-SMOKE-01',
            'first_name' => 'Student',
            'last_name' => 'Smoke',
            'email' => 'student_smoke@test.local',
            'application_type' => 'bachelor',
            'converted_to_student' => true,
            'converted_student_id' => 'BCS-26-02-SMK1',
            'kvkk_consent' => true,
            'docs_ready' => false,
            'contract_status' => 'not_requested',
            'company_id' => 1,
        ]);

        return [$student, $guest];
    }
}
