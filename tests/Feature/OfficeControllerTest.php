<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */

    public function itListAllOfficesInPaginateWay(): void
    {
        Office::factory(3)->create();
        $response = $this->get('/api/offices');

        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('data'));
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    /**
     * @test
     */

    public function itOnlyTestOfficesThatAreNotHiddenAndApproved(): void
    {
        Office::factory(5)->create();
        Office::factory(5)->create(['hidden' => true]);
        Office::factory(5)->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        $response->assertOk();
        $response->assertJsonCount(5, 'data');

    }

    /**
     * @test
     */

    public function itFiltersByUserId(): void
    {
        Office::factory(5)->create();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?user_id=' . $host->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);


    }

    /**
     * @test
     */

    public function itFiltersByVisitorId(): void
    {
        Office::factory(3)->create();

        $user = User::factory()->create();
        $office = Office::factory()->create();

        Reservation::factory()->for(Office::factory())->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?visitor_id=' . $user->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);

    }

    /**
     * @test
     */

    public function itIncludesImagesTagsAndUser(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path' => 'image.jpg']);;

        $response = $this->get('/api/offices');

        $response->assertOk();
        $this->assertIsArray($response->json('data')[0]['tags']);
        $this->assertIsArray($response->json('data')[0]['images']);
        $this->assertCount(1, $response->json('data')[0]['images']);
        $this->assertEquals($user->id, $response->json('data')[0]['user']['id']);

    }

    /**
     * @test
     */

    public function itReturnsNumberOfActiveReservations(): void
    {
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELED]);

        $response = $this->get('/api/offices');

        $response->assertOk();

        $this->assertEquals(1, $response->json('data')[0]['reservations_count']);

    }

    /**
     * @test
     */

    public function itOrderByDistanceWhenCoordinatesAreProvided(): void
    {
        // 24.3613168287183, 88.60688992824939

        //23.73977582064981, 90.38269976914796

        $office = Office::factory()->create([
            'latitude' => '24.3613168287183',
            'longitude' => '88.60688992824939',
            'title' => 'Dhaka',
        ]);

        $office2 = Office::factory()->create([
            'latitude' => '23.73977582064981',
            'longitude' => '90.38269976914796',
            'title' => 'Rajshahi',
        ]);

        $response = $this->get('/api/offices?latitude=23.73977582064981&longitude=90.38269976914796');

        $response->assertOk();
        $this->assertEquals('Rajshahi', $response->json('data')[0]['title']);
        $this->assertEquals('Dhaka', $response->json('data')[1]['title']);

        $response = $this->get('/api/offices');

        $this->assertEquals('Dhaka', $response->json('data')[0]['title']);
        $this->assertEquals('Rajshahi', $response->json('data')[1]['title']);
    }

    /**
     * @test
     */

    public function itShowsTheOffice(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $office = Office::factory()->for($user)->create();
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);


        $office->tags()->attach($tag);
        $office->images()->create(['path' => 'image.jpg']);
        $response = $this->get('/api/offices/' . $office->id);

        $this->assertEquals(1, $response->json('data')['reservations_count']);
        $this->assertIsArray($response->json('data')['tags']);
        $this->assertCount(1, $response->json('data')['tags']);

        $this->assertIsArray($response->json('data')['images']);
        $this->assertCount(1, $response->json('data')['images']);

        $this->assertEquals($user->id, $response->json('data')['user']['id']);


    }

    /**
     * @test
     */

    public function itCreatesAnOffice(): void
    {

        $user = User::factory()->createQuietly();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/offices', [
            'title' => 'Office in Dhaka',
            'description' => 'Office in Dhaka Metro',
            'latitude' => '24.3613168287183',
            'longitude' => '88.60688992824939',
            'address_line1' => 'Mirpur DOHS',
            'price_per_day' => 10_00,
            'monthly_discount' => 5,

            'tags' => [
                $tag1->id, $tag2->id
            ]
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Office in Dhaka')
            ->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('offices', [
            'title' => 'Office in Dhaka'
        ]);

    }

    /**
     * @test
     */

    public function itDoesNotAllowCreatingIfScopeIsNotProvided(): void
    {

        $user = User::factory()->createQuietly();

        $token = $user->createToken('test', []);

        $response = $this->postJson('/api/offices',[],[
                'Authorization' => 'Bearer ' .$token->plainTextToken
            ]
        );

//        $response->assertCreated()
//            ->assertJsonPath('data.title', 'Office in Dhaka')
//            ->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING)
//            ->assertJsonPath('data.user.id', $user->id)
//            ->assertJsonCount(2, 'data.tags');
//
//        $this->assertDatabaseHas('offices', [
//            'title' => 'Office in Dhaka'
//        ]);

        $response->assertStatus(403);

    }

}
