<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The system stays out of the search engines (the church is found by its site).
 */
class IndexingTest extends TestCase
{
    public function test_the_pages_ask_not_to_be_indexed(): void
    {
        $this->get('/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('Igreja Cidade de David');
    }
}
