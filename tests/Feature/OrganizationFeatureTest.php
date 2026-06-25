<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Direction;
use Modules\Organization\Models\Service;
use Modules\Organization\Models\Unit;
use Tests\TestCase;

class OrganizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_access_organization_index(): void
    {
        $response = $this->get(route('organization.index'));

        $response->assertStatus(200);
        $response->assertViewIs('organization::index');
    }

    public function test_can_create_direction(): void
    {
        $response = $this->post(route('organization.directions.store'), [
            'code' => 'DIR-TEST',
            'name' => 'Direction Test',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('directions', [
            'code' => 'DIR-TEST',
            'name' => 'Direction Test',
        ]);
    }

    public function test_can_create_service(): void
    {
        $dir = Direction::create([
            'code' => 'DIR-1',
            'name' => 'Direction 1',
        ]);

        $response = $this->post(route('organization.services.store'), [
            'code' => 'SRV-TEST',
            'name' => 'Service Test',
            'direction_id' => $dir->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('services', [
            'code' => 'SRV-TEST',
            'name' => 'Service Test',
            'direction_id' => $dir->id,
        ]);
    }

    public function test_can_create_unit(): void
    {
        $dir = Direction::create([
            'code' => 'DIR-1',
            'name' => 'Direction 1',
        ]);

        $srv = Service::create([
            'code' => 'SRV-1',
            'name' => 'Service 1',
            'direction_id' => $dir->id,
        ]);

        $response = $this->post(route('organization.units.store'), [
            'code' => 'UNT-TEST',
            'name' => 'Unit Test',
            'service_id' => $srv->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('units', [
            'code' => 'UNT-TEST',
            'name' => 'Unit Test',
            'service_id' => $srv->id,
        ]);
    }

    public function test_cannot_delete_direction_with_services(): void
    {
        $dir = Direction::create([
            'code' => 'DIR-1',
            'name' => 'Direction 1',
        ]);

        $srv = Service::create([
            'code' => 'SRV-1',
            'name' => 'Service 1',
            'direction_id' => $dir->id,
        ]);

        $response = $this->delete(route('organization.directions.destroy', $dir->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('directions', ['id' => $dir->id]);
    }

    public function test_cannot_delete_service_with_units(): void
    {
        $dir = Direction::create([
            'code' => 'DIR-1',
            'name' => 'Direction 1',
        ]);

        $srv = Service::create([
            'code' => 'SRV-1',
            'name' => 'Service 1',
            'direction_id' => $dir->id,
        ]);

        $unit = Unit::create([
            'code' => 'UNT-1',
            'name' => 'Unit 1',
            'service_id' => $srv->id,
        ]);

        $response = $this->delete(route('organization.services.destroy', $srv->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('services', ['id' => $srv->id]);
    }
}
