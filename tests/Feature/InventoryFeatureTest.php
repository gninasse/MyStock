<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\Article;
use Modules\Inventory\Models\Category;
use Modules\Inventory\Models\Store;
use Tests\TestCase;

class InventoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_store(): void
    {
        $response = $this->post(route('inventory.stores.store'), [
            'code' => 'MAG-TEST',
            'name' => 'Store Test',
            'location' => 'Zone B',
            'manager_name' => 'John Doe',
            'phone' => '123456',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('stores', [
            'code' => 'MAG-TEST',
            'name' => 'Store Test',
        ]);
    }

    public function test_can_create_article(): void
    {
        $category = Category::create([
            'code' => 'CAT-TEST',
            'name' => 'Category Test',
        ]);

        $response = $this->post(route('inventory.articles.store'), [
            'code' => 'ART-TEST',
            'designation' => 'Article Test',
            'category_id' => $category->id,
            'unit' => 'Pièce',
            'min_stock' => 5,
        ]);

        $response->assertRedirect(route('inventory.articles.index'));
        $this->assertDatabaseHas('articles', [
            'code' => 'ART-TEST',
            'designation' => 'Article Test',
        ]);
    }

    public function test_can_create_stock_entry_draft_and_validate(): void
    {
        $store = Store::create([
            'code' => 'MAG-01',
            'name' => 'Magasin Central',
        ]);

        $category = Category::create([
            'code' => 'CAT-01',
            'name' => 'Category Test',
        ]);

        $article = Article::create([
            'code' => 'ART-001',
            'designation' => 'Ordinateur',
            'category_id' => $category->id,
            'unit' => 'Pièce',
            'min_stock' => 1,
        ]);

        // 1. Save draft
        $response = $this->post(route('inventory.entries.draft'), [
            'store_id' => $store->id,
            'comment' => 'Test comment',
            'lines' => [
                [
                    'article_id' => $article->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $draftId = $response->json('draft_id');
        $this->assertNotNull($draftId);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $draftId,
            'status' => 'draft',
            'store_id' => $store->id,
        ]);

        $this->assertDatabaseHas('stock_movement_lines', [
            'stock_movement_id' => $draftId,
            'article_id' => $article->id,
            'quantity' => 10,
        ]);

        // 2. Validate entry
        $validateResponse = $this->post("/inventory/entries/{$draftId}/validate");
        $validateResponse->assertStatus(200);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $draftId,
            'status' => 'validated',
        ]);

        // 3. Verify stock balance updated
        $this->assertDatabaseHas('stock_balances', [
            'store_id' => $store->id,
            'article_id' => $article->id,
            'quantity' => 10,
        ]);
    }

    public function test_can_get_frequent_items(): void
    {
        $store = Store::create([
            'code' => 'MAG-FREQ',
            'name' => 'Magasin Frequent',
        ]);

        $response = $this->get("/inventory/stores/{$store->id}/frequent");

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_can_update_frequent_items(): void
    {
        $store = Store::create([
            'code' => 'MAG-FREQ-2',
            'name' => 'Magasin Frequent 2',
        ]);

        $category = Category::create([
            'code' => 'CAT-FREQ',
            'name' => 'Category Frequent',
        ]);

        $article = Article::create([
            'code' => 'ART-FREQ',
            'designation' => 'Article Frequent',
            'category_id' => $category->id,
            'unit' => 'Unité',
            'min_stock' => 1,
        ]);

        $response = $this->post("/inventory/stores/{$store->id}/frequent", [
            'article_ids' => [$article->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('store_frequent_items', [
            'store_id' => $store->id,
            'article_id' => $article->id,
            'sort_order' => 0,
        ]);
    }
}
