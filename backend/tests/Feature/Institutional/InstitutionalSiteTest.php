<?php

namespace Tests\Feature\Institutional;

use App\Models\InstitutionPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionalSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // IMPORTANT: RefreshDatabase resets tables. A dedicated MySQL test database is mandatory.
        // Check the raw process environment BEFORE Laravel's TestCase initializes the database.
        $name = getenv('DB_DATABASE') ?: ($_SERVER['DB_DATABASE'] ?? ($_ENV['DB_DATABASE'] ?? ''));
        $driver = getenv('DB_CONNECTION') ?: ($_SERVER['DB_CONNECTION'] ?? ($_ENV['DB_CONNECTION'] ?? ''));
        if ($name !== 'stsams_test' || $driver !== 'mysql') {
            $this->markTestSkipped('Set DB_CONNECTION=mysql and DB_DATABASE=stsams_test for isolated STSAMS website tests.');
        }
        parent::setUp();
    }

    public function test_public_pages_are_accessible_and_link_to_admissions(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('SAINT TITUS COLLEGE OF HEALTH SCIENCES AND TECHNOLOGY')
            ->assertSee('/admissions');
        $this->get('/about')->assertOk();
        $this->get('/academics')->assertOk();
        $this->get('/admissions-info')->assertOk();
        $this->get('/contact')->assertOk();
    }

    public function test_contact_inquiries_are_stored_and_validated(): void
    {
        $this->post('/contact', [
            'name' => 'Website Visitor',
            'email' => 'visitor@example.test',
            'subject' => 'Programme information',
            'message' => 'Please explain how I can find the official programme catalogue.',
            'consent' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('stsams_web_inquiries', ['email' => 'visitor@example.test']);
    }

    public function test_unauthorized_users_cannot_edit_institutional_content(): void
    {
        $this->post('/institution-admin/content', [
            'section' => 'about', 'content_key' => 'accreditation', 'body' => 'False claim',
        ])->assertRedirect('/admissions/login');
        $this->assertDatabaseMissing('stsams_web_contents', ['body' => 'False claim']);
    }

    public function test_admin_can_publish_an_official_news_item(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post('/institution-admin/posts', [
            'category' => 'announcement',
            'title' => 'Test institutional announcement',
            'summary' => 'Testing official information publication.',
            'body' => 'This is a test announcement, not an official institutional claim.',
            'publish' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('stsams_web_posts', [
            'title' => 'Test institutional announcement', 'is_published' => true,
        ]);
        $this->get('/')->assertSee('Test institutional announcement');
    }
}
