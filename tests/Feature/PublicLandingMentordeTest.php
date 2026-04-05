<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingMentordeTest extends TestCase
{
    public function test_public_landing_page_is_accessible_and_has_apply_links(): void
    {
        $response = $this->get('/landing/mentorde?utm_source=google&utm_medium=cpc&utm_campaign=de_winter_2026');

        $response->assertOk()
            ->assertSee('MentorDE')
            ->assertSee('data-apply-link', false)
            ->assertSee('/apply', false)
            ->assertSee('landing-utm', false);
    }
}
