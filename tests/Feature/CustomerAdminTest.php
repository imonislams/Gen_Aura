<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): self
    {
        $admin = Admin::create(['username' => 'admin', 'password' => 'admin']);

        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_customers_are_created_from_orders_and_aggregated(): void
    {
        $this->admin();

        Order::create([
            'name' => 'Rahim',
            'phone' => '01711111111',
            'address' => 'Dhaka',
            'subtotal' => 1000,
            'ship' => 60,
            'total' => 1060,
            'status' => 'Pending',
        ]);

        Order::create([
            'name' => 'Rahim',
            'phone' => '01711111111',
            'address' => 'Dhaka',
            'subtotal' => 500,
            'ship' => 60,
            'total' => 560,
            'status' => 'Confirmed',
        ]);

        $customer = Customer::firstOrFail();
        $this->assertSame(2, $customer->total_orders);
        $this->assertSame(1620, $customer->total_purchase);

        $this->get('/admin/customers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers.data', 1));

        $this->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Show')
                ->where('customer.total_orders', 2)
                ->has('customer.orders', 2));
    }

    public function test_cancelled_orders_are_excluded_from_customer_totals(): void
    {
        Order::create([
            'name' => 'Rahim',
            'phone' => '01711111111',
            'address' => 'Dhaka',
            'subtotal' => 1000,
            'ship' => 60,
            'total' => 1060,
            'status' => 'Pending',
        ]);

        Order::firstOrFail()->update(['status' => 'Cancelled']);

        $customer = Customer::firstOrFail();
        $this->assertSame(0, $customer->total_orders);
        $this->assertSame(0, $customer->total_purchase);
    }
}
