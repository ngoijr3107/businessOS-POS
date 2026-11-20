<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Access\AccessManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'demo-company'],
            ['name' => 'Demo Company', 'currency' => 'TZS']
        );

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ'],
            ['name' => 'Head Office']
        );

        app(AccessManager::class)->ensureDefaults();

        $user = User::firstOrCreate(
            ['email' => 'admin@mauzohub.test'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->update([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->syncRoles(['Administrator']);

        // Direct permissions make the seeded administrator resilient to stale role caches,
        // while the Administrator role itself is also fully permissioned by AccessManager.
        $user->syncPermissions(\App\Models\Permission::where('guard_name', 'web')->get());

        $retail = ProductCategory::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'General']
        );

        Client::firstOrCreate(
            ['company_id' => $company->id, 'client_number' => 'CLI-000001'],
            ['branch_id' => $branch->id, 'name' => 'Walk-in Customer', 'phone' => null]
        );

        foreach ([
            ['SKU-001', 'Premium Service', 'service', 0, 150000],
            ['SKU-002', 'Office Chair', 'product', 80000, 120000],
            ['SKU-003', 'Laptop', 'product', 900000, 1250000],
        ] as [$sku, $name, $type, $cost, $price]) {
            Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $sku],
                [
                    'category_id' => $retail->id,
                    'name' => $name,
                    'type' => $type,
                    'unit' => 'pcs',
                    'cost_price' => $cost,
                    'selling_price' => $price,
                    'track_stock' => $type === 'product',
                    'stock_quantity' => $type === 'product' ? 25 : 0,
                ]
            );
        }
    }
}
