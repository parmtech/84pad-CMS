<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageTrashTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => 'a-unique-safe-password-for-testing',
            'role' => $role,
        ]);
    }

    private function publishedPage(User $owner): Page
    {
        return Page::query()->create([
            'title' => 'Important Homepage',
            'slug' => 'important-homepage',
            'body' => 'Home content that must disappear after trashing.',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $owner->id,
        ]);
    }

    public function test_trash_hides_page_clears_homepage_and_restore_returns_private_draft(): void
    {
        $owner = $this->account('owner');
        $page = $this->publishedPage($owner);

        $this->actingAs($owner)->post(route('settings.site'), [
            'site_title' => '84PAD Test',
            'canonical_url' => 'https://cms.example.test',
            'locale' => 'en-AU',
            'timezone' => 'Australia/Sydney',
            'homepage_page_id' => $page->id,
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('Home content that must disappear after trashing.');

        $this->delete(route('pages.destroy', $page))
            ->assertRedirect(route('pages.index'));

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('core_settings', ['key' => 'site.homepage_page_id']);
        $this->get('/p/important-homepage')->assertNotFound();
        $this->get(route('pages.edit', $page))->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('Home content that must disappear after trashing.');

        $this->get(route('pages.trash'))->assertOk()->assertSee('Important Homepage');

        $this->post(route('pages.restore', $page->id))
            ->assertRedirect(route('pages.edit', $page));

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => 'draft',
            'published_at' => null,
            'deleted_at' => null,
        ]);
        $this->get('/p/important-homepage')->assertNotFound();

        $this->patch(route('pages.publish', $page))
            ->assertRedirect(route('pages.edit', $page));
        $this->get('/p/important-homepage')->assertOk();
        // Restoring or republishing must never silently reassign the homepage.
        $this->assertDatabaseMissing('core_settings', ['key' => 'site.homepage_page_id']);
    }

    public function test_viewer_and_guest_cannot_trash_or_restore_pages(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $page = $this->publishedPage($owner);

        $this->delete(route('pages.destroy', $page))->assertRedirect('/login');
        $this->get(route('pages.trash'))->assertRedirect('/login');

        $this->actingAs($viewer)
            ->delete(route('pages.destroy', $page))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('pages.restore', $page->id))
            ->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'deleted_at' => null]);
        $this->get('/p/important-homepage')->assertOk();
    }

    public function test_only_trashed_pages_can_be_restored(): void
    {
        $owner = $this->account('owner');
        $page = $this->publishedPage($owner);

        $this->actingAs($owner)
            ->post(route('pages.restore', $page->id))
            ->assertNotFound();

        $this->post('/dashboard/pages/trash/not-a-number/restore')->assertNotFound();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => 'published',
            'deleted_at' => null,
        ]);
    }
}
