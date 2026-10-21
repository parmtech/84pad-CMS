<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_shows_foundation_notice(): void
    {
        $this->get('/')->assertOk()->assertSee('84PAD');
    }
}
