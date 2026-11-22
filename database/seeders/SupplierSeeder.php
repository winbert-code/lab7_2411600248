<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    /**
     * Every supplier used by the legacy product catalogue becomes a first-class
     * supplier record. The company_name must match the product's `supplier`
     * string so ProductSeeder can link them without hard-coded IDs.
     *
     * @var array<int, array{name: string, contact: string, city: string, terms: string, lead: int}>
     */
    private array $suppliers = [
        ['name' => 'Brgy. Dadiangas East Supply', 'contact' => 'Marilou Reyes', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 5],
        ['name' => 'Purok 3, Brgy. Dadiangas North', 'contact' => 'Ronaldo Cayabyab', 'city' => 'General Santos', 'terms' => 'Net 15', 'lead' => 4],
        ['name' => 'Labangal Parts Depot', 'contact' => 'Jocelyn Flores', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 7],
        ['name' => 'Purok 6, Brgy. City Heights', 'contact' => 'Ernesto Villanueva', 'city' => 'General Santos', 'terms' => 'COD', 'lead' => 3],
        ['name' => 'Purok 2, Brgy. Lagao', 'contact' => 'Lourdes Magbanua', 'city' => 'General Santos', 'terms' => 'Net 15', 'lead' => 6],
        ['name' => 'Brgy. San Isidro Supply', 'contact' => 'Pedro Ablanida', 'city' => 'General Santos', 'terms' => 'Net 45', 'lead' => 10],
        ['name' => 'Purok 5, Brgy. Calumpang', 'contact' => 'Rosemarie Dela Cruz', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 5],
        ['name' => 'Purok 1, Brgy. Apopong', 'contact' => 'Danilo Ocampo', 'city' => 'General Santos', 'terms' => 'COD', 'lead' => 2],
        ['name' => 'Brgy. Fatima Electronics', 'contact' => 'Analiza Santiago', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 8],
        ['name' => 'Purok 4, Brgy. Mabuhay', 'contact' => 'Wilfredo Padilla', 'city' => 'General Santos', 'terms' => 'Net 15', 'lead' => 6],
        ['name' => 'Purok 7, Brgy. Tambler', 'contact' => 'Erlinda Bautista', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 9],
        ['name' => 'Purok 2, Brgy. Conel', 'contact' => 'Ferdinand Luna', 'city' => 'General Santos', 'terms' => 'Net 45', 'lead' => 12],
        ['name' => 'Brgy. Bula Components', 'contact' => 'Imelda Ramos', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 7],
        ['name' => 'Purok 8, Brgy. Labangal', 'contact' => 'Rogelio Mercado', 'city' => 'General Santos', 'terms' => 'COD', 'lead' => 3],
        ['name' => 'Purok 5, Brgy. Upper Labay', 'contact' => 'Cecilia Ventura', 'city' => 'General Santos', 'terms' => 'Net 15', 'lead' => 5],
        ['name' => 'Purok 3, Brgy. Sinawal', 'contact' => 'Alfonso Estrada', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 8],
        ['name' => 'Purok 6, Brgy. San Jose', 'contact' => 'Nenita Corpuz', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 6],
        ['name' => 'Purok 4, Brgy. Siguel', 'contact' => 'Mario Trinidad', 'city' => 'General Santos', 'terms' => 'Net 15', 'lead' => 4],
        ['name' => 'Purok 9, Brgy. Apopong', 'contact' => 'Lucia Fernando', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 7],
        ['name' => 'Dadiangas West Components', 'contact' => 'Rodrigo Salazar', 'city' => 'General Santos', 'terms' => 'Net 30', 'lead' => 6],
        ['name' => 'Calumpang Computer Supply', 'contact' => 'Ma. Cristina Yap', 'city' => 'General Santos', 'terms' => 'Net 45', 'lead' => 9],
    ];

    public function run(): void
    {
        foreach ($this->suppliers as $index => $data) {
            $code = 'SUP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $slug = Str::slug(Str::before($data['name'], ','), '');

            Supplier::updateOrCreate(
                ['code' => $code],
                [
                    'company_name' => $data['name'],
                    'contact_person' => $data['contact'],
                    'email' => strtolower($slug).'.'.$code.'@bytestock.test',
                    'phone' => '09'.str_pad((string) (1712345000 + $index), 9, '0', STR_PAD_LEFT),
                    'address' => 'Purok / Barangay '.Str::before($data['name'], ','),
                    'city' => $data['city'],
                    'payment_terms' => $data['terms'],
                    'lead_time_days' => $data['lead'],
                    'minimum_order_amount' => 5000 + ($index * 500),
                    'status' => Supplier::STATUS_ACTIVE,
                    'notes' => 'Seeded supplier record for the automated replenishment laboratory exercise.',
                ],
            );
        }
    }
}
