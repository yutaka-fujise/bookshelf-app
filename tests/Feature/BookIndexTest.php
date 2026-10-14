<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_book_index(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get('/books');

        $response->assertStatus(200);
    }
}