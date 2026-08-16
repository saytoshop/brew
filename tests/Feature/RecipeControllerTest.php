<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Category;
use App\Models\Unit;
use App\Models\RecipeIngredient;
use App\Models\Brew;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые данные
        $this->category = Category::create(['name' => 'Хмель']);
        $this->unit = Unit::create(['name' => 'г']);
        $this->ingredient = Ingredient::create([
            'name' => 'Citra',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_can_get_recipes_list(): void
    {
        $recipe = Recipe::create([
            'name' => 'Test IPA',
            'description' => 'Test Description',
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 50,
            'add_time_minutes' => 15,
        ]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Test IPA']);
    }

    public function test_can_get_single_recipe(): void
    {
        $recipe = Recipe::create([
            'name' => 'Test Lager',
            'description' => 'Lager Description',
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 30,
            'add_time_minutes' => 60,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Lager')
            ->assertJsonPath('ingredients.0.quantity', 30);
    }

    public function test_can_create_recipe(): void
    {
        $data = [
            'name' => 'New Stout',
            'description' => 'Rich stout recipe',
            'ingredients' => [
                [
                    'ingredient_id' => $this->ingredient->id,
                    'quantity' => 100,
                    'add_time_minutes' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/recipes', $data);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'New Stout');

        $this->assertDatabaseHas('recipes', ['name' => 'New Stout']);
        $this->assertDatabaseHas('recipe_ingredients', [
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 100,
        ]);
    }

    public function test_can_update_recipe(): void
    {
        $recipe = Recipe::create([
            'name' => 'Old Pale Ale',
            'description' => 'Old Description',
        ]);

        $updateData = [
            'name' => 'Updated Pale Ale',
            'description' => 'Updated Description',
            'ingredients' => [
                [
                    'ingredient_id' => $this->ingredient->id,
                    'quantity' => 75,
                    'add_time_minutes' => 30,
                ],
            ],
        ];

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Pale Ale');

        $this->assertDatabaseHas('recipes', ['name' => 'Updated Pale Ale']);
        $this->assertDatabaseMissing('recipe_ingredients', ['recipe_id' => $recipe->id, 'quantity' => 50]);
    }

    public function test_can_delete_recipe(): void
    {
        $recipe = Recipe::create([
            'name' => 'ToDelete Recipe',
            'description' => 'Will be deleted',
        ]);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_recipe_with_brews_count(): void
    {
        $recipe = Recipe::create([
            'name' => 'Brewed Recipe',
            'description' => 'Has brews',
        ]);

        Brew::create([
            'recipe_id' => $recipe->id,
            'volume_actual' => 20,
            'cost_per_liter' => 1.5,
        ]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertStatus(200)
            ->assertJsonFragment(['brews_count' => 1]);
    }

    public function test_recipe_with_empty_ingredients(): void
    {
        $recipe = Recipe::create([
            'name' => 'Empty Recipe',
            'description' => 'No ingredients yet',
        ]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertStatus(200)
            ->assertJsonFragment(['ingredients_count' => 0]);
    }
}
