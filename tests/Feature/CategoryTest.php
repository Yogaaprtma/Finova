<?php

namespace Tests\Feature;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_categories_index(): void
    {
        Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Beverage',
            'type' => CategoryType::EXPENSE,
            'icon' => 'utensils',
            'color' => '#ef4444',
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->get('/categories');

        $response->assertOk();
    }

    public function test_user_can_create_a_category(): void
    {
        $response = $this->actingAs($this->user)->post('/categories', [
            'name' => 'Entertainment',
            'type' => CategoryType::EXPENSE->value,
            'icon' => 'film',
            'color' => '#8b5cf6',
        ]);

        $response->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Entertainment',
            'type' => CategoryType::EXPENSE->value,
            'icon' => 'film',
            'color' => '#8b5cf6',
            'is_system' => false,
        ]);
    }

    public function test_user_can_create_a_subcategory(): void
    {
        $parent = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Transportation',
            'type' => CategoryType::EXPENSE,
            'icon' => 'car',
            'color' => '#3b82f6',
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->user)->post('/categories', [
            'parent_id' => $parent->id,
            'name' => 'Gasoline',
            'type' => CategoryType::EXPENSE->value,
            'icon' => 'fuel',
            'color' => '#3b82f6',
        ]);

        $response->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'name' => 'Gasoline',
        ]);
    }

    public function test_user_cannot_delete_system_category(): void
    {
        $systemCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'System Salary',
            'type' => CategoryType::INCOME,
            'is_system' => true,
        ]);

        $response = $this->actingAs($this->user)->delete("/categories/{$systemCategory->id}");

        $response->assertRedirect('/categories');
        $response->assertSessionHas('error', 'Cannot delete system categories.');
        $this->assertDatabaseHas('categories', [
            'id' => $systemCategory->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_delete_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Category',
            'type' => CategoryType::EXPENSE,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->user)->delete("/categories/{$otherCategory->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', [
            'id' => $otherCategory->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_delete_own_category(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Personal Hobby',
            'type' => CategoryType::EXPENSE,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->user)->delete("/categories/{$category->id}");

        $response->assertRedirect('/categories');
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_can_reorder_categories(): void
    {
        $cat1 = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Cat 1',
            'type' => CategoryType::EXPENSE,
            'sort_order' => 1,
        ]);

        $cat2 = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Cat 2',
            'type' => CategoryType::EXPENSE,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->user)->post('/categories/reorder', [
            'categories' => [
                ['id' => $cat1->id, 'sort_order' => 2],
                ['id' => $cat2->id, 'sort_order' => 1],
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, $cat1->refresh()->sort_order);
        $this->assertEquals(1, $cat2->refresh()->sort_order);
    }
}
