<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => 'testing-password-secure',
            'role' => $role,
        ]);
    }

    private function makePage(User $author, array $overrides = []): Page
    {
        return Page::query()->create(array_merge([
            'title' => 'Sample Page',
            'slug' => 'sample-page',
            'body' => 'Sample body',
            'created_by' => $author->id,
        ], $overrides));
    }

    public function test_guest_cannot_reach_page_management(): void
    {
        $this->get('/dashboard/pages')->assertRedirect('/login');
        $this->post('/dashboard/pages', ['title' => 'Nope'])->assertRedirect('/login');
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_viewer_cannot_manage_pages(): void
    {
        $viewer = $this->userWithRole('viewer');
        $page = $this->makePage($viewer);

        $this->actingAs($viewer)->get('/dashboard/pages')->assertForbidden();
        $this->actingAs($viewer)->post('/dashboard/pages', ['title' => 'Nope'])->assertForbidden();
        $this->actingAs($viewer)->patch(route('pages.publish', $page))->assertForbidden();
        $this->assertDatabaseCount('pages', 1);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'status' => 'draft']);
    }

    public function test_admin_creates_a_draft_with_a_generated_slug(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post('/dashboard/pages', ['title' => 'About Us', 'body' => 'Hello'])
            ->assertSessionHasNoErrors();

        $page = Page::query()->where('slug', 'about-us')->firstOrFail();
        $this->assertSame('draft', $page->fresh()->status);
        $this->assertSame($admin->id, (int) $page->created_by);
        $this->assertNull($page->fresh()->published_at);
    }

    public function test_title_is_required_and_slug_rules_are_enforced(): void
    {
        $admin = $this->userWithRole('admin');
        $this->makePage($admin, ['slug' => 'taken']);

        $this->actingAs($admin)->post('/dashboard/pages', ['body' => 'x'])
            ->assertSessionHasErrors('title');
        $this->actingAs($admin)->post('/dashboard/pages', ['title' => 'Bad', 'slug' => 'Bad Slug!'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post('/dashboard/pages', ['title' => 'Dup', 'slug' => 'taken'])
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('pages', 1);
    }

    public function test_update_may_keep_its_own_slug_but_not_take_another(): void
    {
        $admin = $this->userWithRole('admin');
        $page = $this->makePage($admin, ['slug' => 'about']);
        $this->makePage($admin, ['title' => 'Other', 'slug' => 'other']);

        $this->actingAs($admin)
            ->put(route('pages.update', $page), ['title' => 'About v2', 'slug' => 'about', 'body' => 'x'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'About v2']);

        $this->actingAs($admin)
            ->put(route('pages.update', $page), ['title' => 'About v3', 'slug' => 'other', 'body' => 'x'])
            ->assertSessionHasErrors('slug');
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'About v2', 'slug' => 'about']);
    }

    public function test_update_cannot_publish_through_the_status_field(): void
    {
        $admin = $this->userWithRole('admin');
        $page = $this->makePage($admin);

        $this->actingAs($admin)->put(route('pages.update', $page), [
            'title' => 'Sample Page',
            'slug' => 'sample-page',
            'body' => 'x',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'status' => 'draft']);
        $this->get('/p/sample-page')->assertNotFound();
    }

    public function test_publish_toggle_controls_public_visibility(): void
    {
        $admin = $this->userWithRole('admin');
        $page = $this->makePage($admin, ['title' => 'Launch Notes', 'slug' => 'launch-notes']);

        $this->get('/p/launch-notes')->assertNotFound();
        $this->actingAs($admin)->get('/p/launch-notes')->assertNotFound();

        $this->actingAs($admin)->patch(route('pages.publish', $page))
            ->assertRedirect(route('pages.edit', $page));
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'status' => 'published']);
        $this->assertNotNull($page->fresh()->published_at);
        $this->get('/p/launch-notes')->assertOk()->assertSee('Launch Notes');

        $this->actingAs($admin)->patch(route('pages.publish', $page));
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'status' => 'draft']);
        $this->assertNull($page->fresh()->published_at);
        $this->get('/p/launch-notes')->assertNotFound();
    }

    public function test_public_page_escapes_title_and_body(): void
    {
        $admin = $this->userWithRole('admin');
        $this->makePage($admin, [
            'title' => '<b>Bold</b>',
            'slug' => 'safe',
            'body' => '<script>alert(1)</script>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/p/safe')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<b>Bold</b>', false);
    }

    public function test_preview_is_private_and_not_indexable(): void
    {
        $admin = $this->userWithRole('admin');
        $viewer = $this->userWithRole('viewer');
        $page = $this->makePage($admin);

        $this->get(route('pages.preview', $page))->assertRedirect('/login');
        $this->actingAs($viewer)->get(route('pages.preview', $page))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('pages.preview', $page));
        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Private draft preview');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
