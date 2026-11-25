<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_can_create_feed_item_and_initial_stock_movement(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($warehouse)->post(route('inventory.item.store'), [
            'code' => 'pkn-starter',
            'name' => 'Pakan Starter',
            'category' => 'feed',
            'base_unit' => 'kg',
            'purchase_unit' => 'sak',
            'unit_conversion' => 50,
            'min_stock' => 500,
            'stock' => 100,
            'avg_cost' => 8500,
            'storage_location' => 'Gudang Pakan',
        ])->assertRedirect();

        $item = InventoryItem::where('code', 'PKN-STARTER')->firstOrFail();

        $this->assertSame(100.0, (float) $item->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'direction' => 'in',
            'reason' => 'adjustment',
            'quantity' => 100,
        ]);
    }

    public function test_operator_cannot_create_inventory_master_item(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('inventory.item.store'), [
            'code' => 'OBT-X',
            'name' => 'Obat X',
            'category' => 'medicine',
            'base_unit' => 'botol',
            'unit_conversion' => 1,
            'min_stock' => 10,
        ])->assertForbidden();
    }

    public function test_item_with_movements_is_deactivated_instead_of_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = InventoryItem::create([
            'code' => 'VIT-X', 'name' => 'Vitamin X', 'category' => 'vitamin',
            'base_unit' => 'gram', 'unit_conversion' => 1, 'min_stock' => 100,
            'stock' => 0, 'avg_cost' => 0, 'is_active' => true,
        ]);
        InventoryMovement::create([
            'inventory_item_id' => $item->id, 'direction' => 'in', 'reason' => 'adjustment',
            'quantity' => 10, 'unit_cost' => 100, 'movement_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)->delete(route('inventory.item.destroy', $item))->assertRedirect();

        $this->assertFalse((bool) $item->fresh()->is_active);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'is_active' => 0]);
    }
}
