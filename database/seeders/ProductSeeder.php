<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'AMD Ryzen 7 7800X3D Processor',
                'brand' => 'AMD',
                'sku' => 'CPU-AMD-7800X3D',
                'description' => '8-core AM5 gaming processor with 3D V-Cache.',
                'category' => 'CPU',
                'quantity' => 24,
                'reorder_level' => 8,
                'unit_price' => 359.99,
                'supplier' => 'Brgy. Dadiangas East Supply',
            ],
            [
                'name' => 'Intel Core i5-14600K Processor',
                'brand' => 'Intel',
                'sku' => 'CPU-INT-14600K',
                'description' => '14-core LGA1700 unlocked desktop processor.',
                'category' => 'CPU',
                'quantity' => 6,
                'reorder_level' => 10,
                'unit_price' => 289.50,
                'supplier' => 'Purok 3, Brgy. Dadiangas North',
            ],
            [
                'name' => 'NVIDIA GeForce RTX 4070 Super',
                'brand' => 'NVIDIA',
                'sku' => 'GPU-NV-4070S',
                'description' => '12GB GDDR6X graphics card for 1440p gaming.',
                'category' => 'Graphics Card',
                'quantity' => 12,
                'reorder_level' => 5,
                'unit_price' => 599.00,
                'supplier' => 'Labangal Parts Depot',
            ],
            [
                'name' => 'ASUS Radeon RX 7800 XT',
                'brand' => 'ASUS',
                'sku' => 'GPU-AS-7800XT',
                'description' => '16GB GDDR6 graphics card with dual-fan cooling.',
                'category' => 'Graphics Card',
                'quantity' => 0,
                'reorder_level' => 4,
                'unit_price' => 499.00,
                'supplier' => 'Purok 6, Brgy. City Heights',
            ],
            [
                'name' => 'MSI B650 Tomahawk WiFi',
                'brand' => 'MSI',
                'sku' => 'MB-MSI-B650',
                'description' => 'ATX AM5 motherboard with WiFi 6E and DDR5 support.',
                'category' => 'Motherboard',
                'quantity' => 18,
                'reorder_level' => 6,
                'unit_price' => 219.00,
                'supplier' => 'Purok 2, Brgy. Lagao',
            ],
            [
                'name' => 'Gigabyte Z790 Aorus Elite AX',
                'brand' => 'Gigabyte',
                'sku' => 'MB-GB-Z790',
                'description' => 'ATX LGA1700 motherboard with DDR5 and PCIe 5.0.',
                'category' => 'Motherboard',
                'quantity' => 3,
                'reorder_level' => 6,
                'unit_price' => 249.00,
                'supplier' => 'Brgy. San Isidro Supply',
            ],
            [
                'name' => 'Corsair Vengeance 32GB DDR5-6000',
                'brand' => 'Corsair',
                'sku' => 'RAM-COR-32D5',
                'description' => '2x16GB DDR5 memory kit, 6000MHz CL30.',
                'category' => 'Memory (RAM)',
                'quantity' => 45,
                'reorder_level' => 12,
                'unit_price' => 119.00,
                'supplier' => 'Purok 5, Brgy. Calumpang',
            ],
            [
                'name' => 'G.Skill Ripjaws 16GB DDR4-3200',
                'brand' => 'G.Skill',
                'sku' => 'RAM-GS-16D4',
                'description' => '2x8GB DDR4 memory kit for budget builds.',
                'category' => 'Memory (RAM)',
                'quantity' => 7,
                'reorder_level' => 10,
                'unit_price' => 59.00,
                'supplier' => 'Purok 1, Brgy. Apopong',
            ],
            [
                'name' => 'Samsung 990 PRO 2TB NVMe SSD',
                'brand' => 'Samsung',
                'sku' => 'SSD-SAM-990-2T',
                'description' => 'PCIe 4.0 NVMe SSD with 7450MB/s read speed.',
                'category' => 'Storage',
                'quantity' => 30,
                'reorder_level' => 8,
                'unit_price' => 169.00,
                'supplier' => 'Brgy. Fatima Electronics',
            ],
            [
                'name' => 'WD Blue 1TB SATA SSD',
                'brand' => 'WD',
                'sku' => 'SSD-WD-BLUE-1T',
                'description' => '2.5-inch SATA SSD for everyday computing.',
                'category' => 'Storage',
                'quantity' => 2,
                'reorder_level' => 8,
                'unit_price' => 69.00,
                'supplier' => 'Purok 4, Brgy. Mabuhay',
            ],
            [
                'name' => 'Seagate Barracuda 4TB HDD',
                'brand' => 'Seagate',
                'sku' => 'HDD-SEA-4T',
                'description' => '3.5-inch 5400RPM hard drive for bulk storage.',
                'category' => 'Storage',
                'quantity' => 15,
                'reorder_level' => 5,
                'unit_price' => 89.00,
                'supplier' => 'Purok 7, Brgy. Tambler',
            ],
            [
                'name' => 'Corsair RM750e 750W Gold',
                'brand' => 'Corsair',
                'sku' => 'PSU-COR-RM750',
                'description' => 'Fully modular 80 Plus Gold ATX 3.0 power supply.',
                'category' => 'Power Supply',
                'quantity' => 20,
                'reorder_level' => 6,
                'unit_price' => 109.00,
                'supplier' => 'Purok 5, Brgy. Calumpang',
            ],
            [
                'name' => 'be quiet! Pure Power 12 850W',
                'brand' => 'be quiet!',
                'sku' => 'PSU-BQ-PP12-850',
                'description' => '80 Plus Gold power supply with ATX 3.1 support.',
                'category' => 'Power Supply',
                'quantity' => 0,
                'reorder_level' => 4,
                'unit_price' => 139.00,
                'supplier' => 'Purok 2, Brgy. Conel',
            ],
            [
                'name' => 'NZXT H5 Flow ATX Case',
                'brand' => 'NZXT',
                'sku' => 'CASE-NZXT-H5',
                'description' => 'Mid-tower ATX case with high airflow mesh front.',
                'category' => 'PC Case',
                'quantity' => 14,
                'reorder_level' => 5,
                'unit_price' => 94.00,
                'supplier' => 'Brgy. Bula Components',
            ],
            [
                'name' => 'Lian Li O11 Dynamic Mini',
                'brand' => 'Lian Li',
                'sku' => 'CASE-LI-O11M',
                'description' => 'Compact mATX case with tempered glass panels.',
                'category' => 'PC Case',
                'quantity' => 4,
                'reorder_level' => 5,
                'unit_price' => 129.00,
                'supplier' => 'Purok 8, Brgy. Labangal',
            ],
            [
                'name' => 'Noctua NH-D15 CPU Cooler',
                'brand' => 'Noctua',
                'sku' => 'COOL-NOC-NHD15',
                'description' => 'Dual-tower air cooler for high-end processors.',
                'category' => 'Cooling',
                'quantity' => 22,
                'reorder_level' => 6,
                'unit_price' => 99.00,
                'supplier' => 'Purok 5, Brgy. Upper Labay',
            ],
            [
                'name' => 'Arctic Liquid Freezer III 360',
                'brand' => 'Arctic',
                'sku' => 'COOL-ARC-LF3-360',
                'description' => '360mm AIO liquid cooler with PWM fans.',
                'category' => 'Cooling',
                'quantity' => 5,
                'reorder_level' => 6,
                'unit_price' => 119.00,
                'supplier' => 'Purok 3, Brgy. Sinawal',
            ],
            [
                'name' => 'Logitech MX Master 3S Mouse',
                'brand' => 'Logitech',
                'sku' => 'PER-LOG-MX3S',
                'description' => 'Ergonomic wireless performance mouse.',
                'category' => 'Peripheral',
                'quantity' => 38,
                'reorder_level' => 10,
                'unit_price' => 99.00,
                'supplier' => 'Purok 6, Brgy. San Jose',
            ],
            [
                'name' => 'Keychron K8 Pro Mechanical Keyboard',
                'brand' => 'Keychron',
                'sku' => 'PER-KEY-K8P',
                'description' => 'Tenkeyless hot-swappable wireless keyboard.',
                'category' => 'Peripheral',
                'quantity' => 9,
                'reorder_level' => 8,
                'unit_price' => 109.00,
                'supplier' => 'Purok 4, Brgy. Siguel',
            ],
            [
                'name' => 'TP-Link Archer AX3000 Router',
                'brand' => 'TP-Link',
                'sku' => 'NET-TP-AX3000',
                'description' => 'WiFi 6 dual-band gigabit router.',
                'category' => 'Networking',
                'quantity' => 11,
                'reorder_level' => 5,
                'unit_price' => 89.00,
                'supplier' => 'Purok 9, Brgy. Apopong',
            ],
        ];

        $categoryIds = Category::query()->pluck('id', 'name');
        $brandIds = Brand::query()->pluck('id', 'name');
        $supplierIds = Supplier::query()->pluck('id', 'company_name');

        foreach ($products as $product) {
            $categoryName = $product['category'];
            $brandName = $product['brand'];
            $supplierName = $product['supplier'];
            unset($product['category'], $product['brand']);

            $product['category_id'] = $categoryIds->get($categoryName);
            $product['brand_id'] = $brandIds->get($brandName);
            $product['supplier_id'] = $supplierIds->get($supplierName);

            $supplier = $supplierIds->has($supplierName)
                ? Supplier::find($supplierIds->get($supplierName))
                : null;

            // A 30% gross margin is assumed for demo costing data.
            $product['cost_price'] = round((float) $product['unit_price'] * 0.70, 2);
            $product['lead_time_days'] = $supplier?->lead_time_days ?? 7;
            $product['safety_stock'] = (int) $product['reorder_level'];
            $product['package_size'] = 1;
            $product['min_order_qty'] = 1;

            $record = Product::updateOrCreate(['sku' => $product['sku']], $product);

            if ($supplier !== null) {
                $record->suppliers()->syncWithoutDetaching([
                    $supplier->id => [
                        'unit_cost' => $product['cost_price'],
                        'lead_time_days' => $supplier->lead_time_days,
                        'minimum_order_qty' => 1,
                        'is_primary' => true,
                    ],
                ]);
            }
        }
    }
}
