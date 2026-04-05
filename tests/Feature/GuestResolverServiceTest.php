<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\User;
use App\Services\GuestResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * GuestResolverService — merkezi guest arama mantığı için testler.
 *
 * Kapsam:
 *  - E-posta eşleştirme (büyük/küçük harf bağımsız)
 *  - company_id filtresi
 *  - Birden fazla kayıt varsa en yeni seçilir
 *  - Eşleşme yoksa null döner
 *  - Kullanıcısız request → null döner
 */
class GuestResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private GuestResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GuestResolverService::class);
    }

    // ── Yardımcı ─────────────────────────────────────────────────────────────

    private function makeUser(string $email): User
    {
        return User::query()->create([
            'name'      => 'Test User',
            'email'     => $email,
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_GUEST,
            'is_active' => true,
        ]);
    }

    private function makeGuest(string $email, array $extra = []): GuestApplication
    {
        $attrs = array_merge([
            'tracking_token'   => 'TOK-' . strtoupper(substr(md5($email . microtime()), 0, 8)),
            'email'            => $email,
            'first_name'       => 'Test',
            'last_name'        => 'Guest',
            'application_type' => 'bachelor',
            'kvkk_consent'     => true,
        ], $extra);

        // forceFill — test setup iç kodu; $fillable dışı alanları da atayabilir.
        $guest = new GuestApplication;
        $guest->forceFill($attrs);
        $guest->save();
        return $guest;
    }

    /** Request nesnesine kullanıcı ata. */
    private function requestFor(User $user): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    // ── Testler ───────────────────────────────────────────────────────────────

    public function test_resolve_returns_null_when_request_has_no_user(): void
    {
        $this->makeGuest('guest@test.local');

        $request = Request::create('/');
        $request->setUserResolver(fn () => null);

        $this->assertNull($this->service->resolve($request));
    }

    public function test_resolve_returns_null_when_no_matching_guest(): void
    {
        $user = $this->makeUser('nomatching@test.local');
        // guest oluşturulmadı — eşleşme olmamalı

        $this->assertNull($this->service->resolve($this->requestFor($user)));
    }

    public function test_resolve_finds_guest_by_email(): void
    {
        $user  = $this->makeUser('resolver@test.local');
        $guest = $this->makeGuest('resolver@test.local');

        $found = $this->service->resolve($this->requestFor($user));

        $this->assertNotNull($found);
        $this->assertSame((int) $guest->id, (int) $found->id);
    }

    public function test_resolve_is_case_insensitive_on_email(): void
    {
        $user  = $this->makeUser('UPPER@test.local');
        $guest = $this->makeGuest('upper@test.local'); // küçük harfle kayıt

        // GuestResolverService strtolower uygular → eşleşmeli
        $found = $this->service->resolve($this->requestFor($user));

        $this->assertNotNull($found);
        $this->assertSame((int) $guest->id, (int) $found->id);
    }

    public function test_resolve_returns_latest_guest_when_multiple_records_exist(): void
    {
        $user   = $this->makeUser('multi@test.local');
        $older  = $this->makeGuest('multi@test.local');
        $newer  = $this->makeGuest('multi@test.local'); // aynı e-posta, daha yeni ID

        $found = $this->service->resolve($this->requestFor($user));

        $this->assertNotNull($found);
        $this->assertSame((int) $newer->id, (int) $found->id, 'En yeni kayıt dönmeli (latest id).');
    }

    public function test_resolve_filters_by_company_id_when_bound(): void
    {
        $user         = $this->makeUser('company@test.local');
        $guestComp1   = $this->makeGuest('company@test.local', ['company_id' => 1]);
        $guestComp2   = $this->makeGuest('company@test.local', ['company_id' => 2]);

        // Şirketi 2 olarak bağla
        app()->instance('current_company_id', 2);

        $found = $this->service->resolve($this->requestFor($user));

        // Şirket 2'ye ait en yeni kayıt dönmeli
        $this->assertNotNull($found);
        $this->assertSame((int) $guestComp2->id, (int) $found->id);

        // Temizle
        app()->forgetInstance('current_company_id');
    }

    public function test_resolve_returns_null_when_company_id_does_not_match(): void
    {
        $user = $this->makeUser('nomatch_company@test.local');
        $this->makeGuest('nomatch_company@test.local', ['company_id' => 5]);

        // Farklı şirketi bağla
        app()->instance('current_company_id', 99);

        $found = $this->service->resolve($this->requestFor($user));

        $this->assertNull($found, 'Farklı company_id için guest bulunmamalı.');

        app()->forgetInstance('current_company_id');
    }

    public function test_resolve_returns_any_company_guest_when_company_id_is_zero(): void
    {
        $user  = $this->makeUser('zero_company@test.local');
        $guest = $this->makeGuest('zero_company@test.local', ['company_id' => 7]);

        // company_id bağlı değil → herhangi bir şirketteki kaydı döner
        $found = $this->service->resolve($this->requestFor($user));

        $this->assertNotNull($found);
        $this->assertSame((int) $guest->id, (int) $found->id);
    }
}
