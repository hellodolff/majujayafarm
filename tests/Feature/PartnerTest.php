<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_page_is_readable_and_admin_can_create_partner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('partners.index'))->assertOk();

        $this->actingAs($admin)->post(route('partners.store'), [
            'type' => 'supplier',
            'name' => 'Pemasok Ayam Jaya',
            'phone' => '081234567890',
            'address' => 'Jakarta',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers_suppliers', [
            'type' => 'supplier',
            'name' => 'Pemasok Ayam Jaya',
        ]);
    }

    public function test_non_admin_cannot_modify_partner(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('partners.store'), [
            'type' => 'customer',
            'name' => 'Pembeli Baru',
        ])->assertForbidden();
    }

    public function test_partner_can_be_updated_and_deleted_when_unused(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $partner = Partner::create(['type' => 'customer', 'name' => 'Pembeli Lama']);

        $this->actingAs($admin)->put(route('partners.update', $partner), [
            'type' => 'customer',
            'name' => 'Pembeli Baru',
            'phone' => '081299999999',
            'address' => 'Bandung',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers_suppliers', ['id' => $partner->id, 'name' => 'Pembeli Baru']);

        $this->actingAs($admin)->delete(route('partners.destroy', $partner))->assertRedirect();
        $this->assertSoftDeleted('customers_suppliers', ['id' => $partner->id]);
    }
}
