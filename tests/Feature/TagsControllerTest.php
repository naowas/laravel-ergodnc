<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Throwable;

class TagsControllerTest extends TestCase
{
    /**
     * @test
     */

    public function itListsAllTags(): void
    {
        $response = $this->get('/api/tags');

        $response->assertStatus(200);

        $this->assertNotNull($response->json('data')[0]['id']);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/api/tags');

        $response->assertStatus(200);
    }
}
