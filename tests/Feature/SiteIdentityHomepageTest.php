<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\Settings\CoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteIdentityHomepageTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => 'a-long-and-unique-test-password',
            'role' => $role,
        ]);
    }

    private function page(User $author, string $status = 'published'): Page
    {
        return Page::query()->create([
            'title' => 'My Test Homepage',
            'slug' => 'my-test-homepage',
            'body' => 'Unique homepage content from a saved page.',
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
            'created_by' => $author->id,
        ]);
    }

    private function siteForm(array $overrides = []): array
    {
        return array_merge([
            'site_title' => 'My Example Site',
            'canonical_url' => 'https://cms.example.test',
            'locale' => 'en-AU',
            'timezone' => 'Australia/Sydney',
            'homepage_page_id' => '',
        ], $overrides);
    }

    public function test_owner_can_select_a_published_page_as_homepage(): void
    {
        $owner = $this->account('owner');
        $page = $this->page($owner);

        $this->actingAs($owner)
            ->post(route('settings.site'), $this->siteForm(['homepage_page_id' => $page->id]))
            ->assertRedirect(route('settings.show'))
            ->assertSessionHasNoErrors();

        $site = app(CoreSettings::class)->siteSettings();
        $this->assertSame('My Example Site', $site['title']);
        $this->assertSame('en-AU', $site['locale']);
        $this->assertSame('Australia/Sydney', $site['timezone']);
        $this->assertSame((string) $page->id, $site['homepage_page_id']);

        $this->get('/')
            ->assertOk()
            ->assertSee('My Test Homepage')
            ->assertSee('Unique homepage content from a saved page.')
            ->assertSee('<html lang="en-AU">', false)
            ->assertSee('rel="canonical" href="https://cms.example.test"', false);

        $this->get('/p/my-test-homepage')
            ->assertOk()
            ->assertSee('rel="canonical" href="https://cms.example.test/p/my-test-homepage"', false);
    }

    public function test_draft_cannot_be_selected_as_homepage(): void
    {
        $owner = $this->account('owner');
        $draft = $this->page($owner, 'draft');

        $this->actingAs($owner)
            ->post(route('settings.site'), $this->siteForm(['homepage_page_id' => $draft->id]))
            ->assertSessionHasErrors('homepage_page_id');

        $this->assertNull(app(CoreSettings::class)->homepagePage());
        $this->assertDatabaseMissing('core_settings', ['key' => 'site.homepage_page_id']);
        $this->get('/p/my-test-homepage')->assertNotFound();
    }

    public function test_unpublishing_the_selected_homepage_falls_back_safely(): void
    {
        $owner = $this->account('owner');
        $page = $this->page($owner);

        $this->actingAs($owner)
            ->post(route('settings.site'), $this->siteForm(['homepage_page_id' => $page->id]))
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->patch(route('pages.publish', $page))
            ->assertRedirect(route('pages.edit', $page));

        $this->get('/')
            ->assertOk()
            ->assertSee('84PAD')
            ->assertDontSee('Unique homepage content from a saved page.');
        $this->get('/p/my-test-homepage')->assertNotFound();
    }

    public function test_owner_can_clear_the_homepage_selection(): void
    {
        $owner = $this->account('owner');
        $page = $this->page($owner);

        $this->actingAs($owner)
            ->post(route('settings.site'), $this->siteForm(['homepage_page_id' => $page->id]))
            ->assertSessionHasNoErrors();

        $this->post(route('settings.site'), $this->siteForm())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('core_settings', ['key' => 'site.homepage_page_id']);
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Unique homepage content from a saved page.');
        $this->get('/p/my-test-homepage')->assertOk();
    }

    public function test_only_owner_can_change_site_identity(): void
    {
        $admin = $this->account('admin');

        $this->actingAs($admin)
            ->post(route('settings.site'), $this->siteForm())
            ->assertForbidden();

        $this->assertDatabaseMissing('core_settings', ['key' => 'site.title']);
    }

    public function test_canonical_url_rejects_credentials_query_and_fragment(): void
    {
        $owner = $this->account('owner');
        $this->actingAs($owner);

        foreach ([
            'https://user:secret@cms.example.test',
            'https://cms.example.test/?tracking=yes',
            'https://cms.example.test/#private',
        ] as $invalid) {
            $this->post(route('settings.site'), $this->siteForm(['canonical_url' => $invalid]))
                ->assertSessionHasErrors('canonical_url');
        }

        $this->assertDatabaseMissing('core_settings', ['key' => 'site.canonical_url']);
    }
}
