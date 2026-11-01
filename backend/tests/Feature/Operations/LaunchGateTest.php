<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LaunchGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_homepage_remains_accessible_when_admissions_are_closed(): void
    {
        config(['stsams_launch.admissions_enabled' => false]);
        $this->get('/')->assertOk();
    }

    public function test_registration_is_closed_even_without_payment_configuration(): void
    {
        config(['stsams_launch.admissions_enabled' => false]);
        $this->get('/admissions/register')->assertStatus(503);
        $this->post('/admissions/register', [])->assertStatus(503);
    }

    public function test_admissions_information_remains_public(): void
    {
        config(['stsams_launch.admissions_enabled' => false]);
        $this->get('/admissions-info')->assertOk();
    }
}
