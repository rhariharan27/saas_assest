<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Shift;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetCategory;

class SampleAssetsAndEmployeesSeeder extends Seeder
{
  public function run(): void
  {
    $this->command->info('Seeding sample assets and employees...');

    Tenant::all()->runForEach(function () {

      $shift = Shift::where('is_default', true)->first();
      $team = Team::first();
      $designation = Designation::first();
      $adminUser = User::where('code', 'DEMO-001')->first();
      $tenantId = tenancy()->tenant->getTenantKey();

      // ─── Create New Employees ──────────────────────────

      $this->command->info('Creating employees...');

      $emp1 = User::factory()->create([
        'first_name' => 'Rajesh',
        'last_name' => 'Kumar',
        'email' => 'rajesh.kumar@opencorehr.com',
        'phone' => '9876543210',
        'phone_verified_at' => now(),
        'password' => bcrypt('123456'),
        'code' => 'EMP-101',
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'shift_id' => $shift?->id,
        'team_id' => $team?->id,

        'designation_id' => $designation?->id,
        'reporting_to_id' => $adminUser?->id,
        'base_salary' => 3500,
        'tenant_id' => $tenantId,
      ]);
      $emp1->assignRole('office_employee');

      $emp2 = User::factory()->create([
        'first_name' => 'Priya',
        'last_name' => 'Sharma',
        'email' => 'priya.sharma@opencorehr.com',
        'phone' => '9876543211',
        'phone_verified_at' => now(),
        'password' => bcrypt('123456'),
        'code' => 'EMP-102',
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'shift_id' => $shift?->id,
        'team_id' => $team?->id,

        'designation_id' => $designation?->id,
        'reporting_to_id' => $adminUser?->id,
        'base_salary' => 3000,
        'tenant_id' => $tenantId,
      ]);
      $emp2->assignRole('field_employee');

      $emp3 = User::factory()->create([
        'first_name' => 'Amit',
        'last_name' => 'Patel',
        'email' => 'amit.patel@opencorehr.com',
        'phone' => '9876543212',
        'phone_verified_at' => now(),
        'password' => bcrypt('123456'),
        'code' => 'EMP-103',
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'shift_id' => $shift?->id,
        'team_id' => $team?->id,

        'designation_id' => $designation?->id,
        'reporting_to_id' => $adminUser?->id,
        'base_salary' => 4000,
        'tenant_id' => $tenantId,
      ]);
      $emp3->assignRole('office_employee');

      $emp4 = User::factory()->create([
        'first_name' => 'Sneha',
        'last_name' => 'Reddy',
        'email' => 'sneha.reddy@opencorehr.com',
        'phone' => '9876543213',
        'phone_verified_at' => now(),
        'password' => bcrypt('123456'),
        'code' => 'EMP-104',
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'shift_id' => $shift?->id,
        'team_id' => $team?->id,

        'designation_id' => $designation?->id,
        'reporting_to_id' => $adminUser?->id,
        'base_salary' => 2800,
        'tenant_id' => $tenantId,
      ]);
      $emp4->assignRole('office_employee');

      $emp5 = User::factory()->create([
        'first_name' => 'Vikram',
        'last_name' => 'Singh',
        'email' => 'vikram.singh@opencorehr.com',
        'phone' => '9876543214',
        'phone_verified_at' => now(),
        'password' => bcrypt('123456'),
        'code' => 'EMP-105',
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'shift_id' => $shift?->id,
        'team_id' => $team?->id,

        'designation_id' => $designation?->id,
        'reporting_to_id' => $adminUser?->id,
        'base_salary' => 3200,
        'tenant_id' => $tenantId,
      ]);
      $emp5->assignRole('field_employee');

      $this->command->info('5 employees created!');

      // ─── Create Asset Categories ──────────────────────

      $this->command->info('Creating asset categories...');

      $laptopCat = AssetCategory::create([
        'name' => 'Laptops',
        'description' => 'Company laptops and notebooks',
        'tenant_id' => $tenantId,
      ]);

      $mobileCat = AssetCategory::create([
        'name' => 'Mobile Phones',
        'description' => 'Company-issued mobile phones',
        'tenant_id' => $tenantId,
      ]);

      $furnitureCat = AssetCategory::create([
        'name' => 'Furniture',
        'description' => 'Office desks, chairs, and cabinets',
        'tenant_id' => $tenantId,
      ]);

      $vehicleCat = AssetCategory::create([
        'name' => 'Vehicles',
        'description' => 'Company vehicles for field operations',
        'tenant_id' => $tenantId,
      ]);

      $peripheralCat = AssetCategory::create([
        'name' => 'Peripherals',
        'description' => 'Monitors, keyboards, mice, headsets',
        'tenant_id' => $tenantId,
      ]);

      $this->command->info('5 asset categories created!');

      // ─── Create Sample Assets ─────────────────────────

      $this->command->info('Creating sample assets...');

      // Laptops
      Asset::create([
        'name' => 'MacBook Pro 16" M3',
        'asset_tag' => 'LAP-001',
        'asset_category_id' => $laptopCat->id,
        'manufacturer' => 'Apple',
        'model' => 'MacBook Pro 16-inch M3 Pro',
        'serial_number' => 'C02ZN1ABCD01',
        'purchase_date' => '2024-06-15',
        'purchase_cost' => 2499.00,
        'supplier' => 'Apple Store',
        'warranty_expiry_date' => '2027-06-15',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'notes' => '16GB RAM, 512GB SSD',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Dell XPS 15',
        'asset_tag' => 'LAP-002',
        'asset_category_id' => $laptopCat->id,
        'manufacturer' => 'Dell',
        'model' => 'XPS 15 9530',
        'serial_number' => 'DELL9530AB0023',
        'purchase_date' => '2024-03-10',
        'purchase_cost' => 1899.00,
        'supplier' => 'Dell Direct',
        'warranty_expiry_date' => '2027-03-10',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'notes' => '32GB RAM, 1TB SSD, Windows 11 Pro',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'ThinkPad X1 Carbon',
        'asset_tag' => 'LAP-003',
        'asset_category_id' => $laptopCat->id,
        'manufacturer' => 'Lenovo',
        'model' => 'ThinkPad X1 Carbon Gen 11',
        'serial_number' => 'LNV-X1C-0045',
        'purchase_date' => '2023-11-20',
        'purchase_cost' => 1650.00,
        'supplier' => 'Lenovo Business',
        'warranty_expiry_date' => '2026-11-20',
        'status' => 'available',
        'condition' => 'good',
        'location' => 'IT Store Room',
        'notes' => '16GB RAM, 512GB SSD, i7-1365U',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'HP EliteBook 840',
        'asset_tag' => 'LAP-004',
        'asset_category_id' => $laptopCat->id,
        'manufacturer' => 'HP',
        'model' => 'EliteBook 840 G10',
        'serial_number' => 'HP840G10-0089',
        'purchase_date' => '2024-01-05',
        'purchase_cost' => 1400.00,
        'supplier' => 'HP Store',
        'warranty_expiry_date' => '2027-01-05',
        'status' => 'in_repair',
        'condition' => 'fair',
        'location' => 'Service Center',
        'notes' => 'Screen replacement pending',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      // Mobile Phones
      Asset::create([
        'name' => 'iPhone 15 Pro',
        'asset_tag' => 'MOB-001',
        'asset_category_id' => $mobileCat->id,
        'manufacturer' => 'Apple',
        'model' => 'iPhone 15 Pro 256GB',
        'serial_number' => 'APPL-IP15P-0012',
        'purchase_date' => '2024-09-25',
        'purchase_cost' => 1199.00,
        'supplier' => 'Apple Store',
        'warranty_expiry_date' => '2025-09-25',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'notes' => 'For field sales team',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Samsung Galaxy S24 Ultra',
        'asset_tag' => 'MOB-002',
        'asset_category_id' => $mobileCat->id,
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S24 Ultra 512GB',
        'serial_number' => 'SAM-S24U-0034',
        'purchase_date' => '2024-02-14',
        'purchase_cost' => 1099.00,
        'supplier' => 'Samsung Business',
        'warranty_expiry_date' => '2026-02-14',
        'status' => 'available',
        'condition' => 'good',
        'location' => 'IT Store Room',
        'notes' => 'For management use',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'OnePlus 12',
        'asset_tag' => 'MOB-003',
        'asset_category_id' => $mobileCat->id,
        'manufacturer' => 'OnePlus',
        'model' => 'OnePlus 12 256GB',
        'serial_number' => 'OP12-0056',
        'purchase_date' => '2024-04-10',
        'purchase_cost' => 799.00,
        'supplier' => 'OnePlus Store',
        'warranty_expiry_date' => '2025-04-10',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      // Furniture
      Asset::create([
        'name' => 'Ergonomic Standing Desk',
        'asset_tag' => 'FUR-001',
        'asset_category_id' => $furnitureCat->id,
        'manufacturer' => 'Featherlite',
        'model' => 'Optima Height Adjustable',
        'serial_number' => 'FL-OPT-0078',
        'purchase_date' => '2024-07-01',
        'purchase_cost' => 450.00,
        'supplier' => 'Featherlite Direct',
        'warranty_expiry_date' => '2029-07-01',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'Floor 2 - Storage',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Herman Miller Aeron Chair',
        'asset_tag' => 'FUR-002',
        'asset_category_id' => $furnitureCat->id,
        'manufacturer' => 'Herman Miller',
        'model' => 'Aeron Size B',
        'serial_number' => 'HM-AERON-0112',
        'purchase_date' => '2023-05-15',
        'purchase_cost' => 1395.00,
        'supplier' => 'Herman Miller',
        'warranty_expiry_date' => '2035-05-15',
        'status' => 'available',
        'condition' => 'good',
        'location' => 'Floor 2 - Storage',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      // Vehicles
      Asset::create([
        'name' => 'Toyota Innova Crysta',
        'asset_tag' => 'VEH-001',
        'asset_category_id' => $vehicleCat->id,
        'manufacturer' => 'Toyota',
        'model' => 'Innova Crysta GX',
        'serial_number' => 'TN-09-AB-1234',
        'purchase_date' => '2023-08-20',
        'purchase_cost' => 22000.00,
        'supplier' => 'Toyota Dealership Chennai',
        'warranty_expiry_date' => '2026-08-20',
        'status' => 'available',
        'condition' => 'good',
        'location' => 'Company Parking - Slot A3',
        'notes' => 'For field team visits',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Maruti Suzuki Swift',
        'asset_tag' => 'VEH-002',
        'asset_category_id' => $vehicleCat->id,
        'manufacturer' => 'Maruti Suzuki',
        'model' => 'Swift ZXi',
        'serial_number' => 'TN-09-CD-5678',
        'purchase_date' => '2024-01-15',
        'purchase_cost' => 9500.00,
        'supplier' => 'Maruti Dealership',
        'warranty_expiry_date' => '2027-01-15',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'Company Parking - Slot B1',
        'notes' => 'Sales executive use',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      // Peripherals
      Asset::create([
        'name' => 'Dell UltraSharp 27" 4K Monitor',
        'asset_tag' => 'PER-001',
        'asset_category_id' => $peripheralCat->id,
        'manufacturer' => 'Dell',
        'model' => 'U2723QE',
        'serial_number' => 'DELL-MON-0091',
        'purchase_date' => '2024-05-10',
        'purchase_cost' => 620.00,
        'supplier' => 'Dell Direct',
        'warranty_expiry_date' => '2027-05-10',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Logitech MX Master 3S Mouse',
        'asset_tag' => 'PER-002',
        'asset_category_id' => $peripheralCat->id,
        'manufacturer' => 'Logitech',
        'model' => 'MX Master 3S',
        'serial_number' => 'LOG-MX3S-0145',
        'purchase_date' => '2024-04-01',
        'purchase_cost' => 99.00,
        'supplier' => 'Amazon',
        'warranty_expiry_date' => '2026-04-01',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Jabra Evolve2 75 Headset',
        'asset_tag' => 'PER-003',
        'asset_category_id' => $peripheralCat->id,
        'manufacturer' => 'Jabra',
        'model' => 'Evolve2 75 UC',
        'serial_number' => 'JAB-E275-0067',
        'purchase_date' => '2024-03-22',
        'purchase_cost' => 299.00,
        'supplier' => 'Jabra Direct',
        'warranty_expiry_date' => '2026-03-22',
        'status' => 'available',
        'condition' => 'new',
        'location' => 'IT Store Room',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      Asset::create([
        'name' => 'Apple Magic Keyboard',
        'asset_tag' => 'PER-004',
        'asset_category_id' => $peripheralCat->id,
        'manufacturer' => 'Apple',
        'model' => 'Magic Keyboard with Touch ID',
        'serial_number' => 'APPL-KB-0023',
        'purchase_date' => '2024-06-18',
        'purchase_cost' => 199.00,
        'supplier' => 'Apple Store',
        'warranty_expiry_date' => '2025-06-18',
        'status' => 'damaged',
        'condition' => 'poor',
        'location' => 'IT Store Room',
        'notes' => 'Spill damage - keys not working',
        'tenant_id' => $tenantId,
        'created_by_id' => $adminUser?->id,
      ]);

      $this->command->info('16 assets created across 5 categories!');
    });

    $this->command->info('Sample assets and employees seeding complete!');
  }
}
