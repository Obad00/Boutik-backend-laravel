<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopCode;
use App\Models\ShopOwner;
use App\Models\Superadmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $superadmin = Superadmin::create([
                'name' => 'Superadmin Boutik',
                'email' => 'admin@boutik.sn',
                'password_hash' => Hash::make('changeme123'),
            ]);

            $pinHash = Hash::make('1234');
            $ownerPasswordHash = Hash::make('changeme123');

            $shops = [
                [
                    'code' => 'SANDAGA',
                    'name' => 'Boutik Sandaga',
                    'address' => 'Marché Sandaga, Dakar',
                    'phone' => '33 821 00 00',
                    'categories' => ['Boissons', 'Épicerie', 'Ménage', 'Hygiène'],
                    'products' => [
                        ['Coca-Cola 33cl', 300, 250, 'Boissons', 'piece', 48, 12],
                        ['Eau minérale 1.5L', 400, 320, 'Boissons', 'piece', 36, 10],
                        ['Jus Youki 1L', 900, 700, 'Boissons', 'piece', 18, 6],
                        ['Riz brisé parfumé', 450, 380, 'Épicerie', 'kg', 120, 20],
                        ['Huile Diamaraf 1L', 1200, 1050, 'Épicerie', 'litre', 24, 8],
                        ['Sucre en poudre', 600, 520, 'Épicerie', 'kg', 40, 10],
                        ['Savon Palmida', 350, 280, 'Hygiène', 'piece', 30, 8],
                        ['Détergent Omo 1kg', 1500, 1300, 'Ménage', 'piece', 20, 6],
                    ],
                    'customers' => [
                        ['Fatou Diop', '77 123 45 67', 4500],
                        ['Moussa Sarr', '76 987 65 43', 12000],
                        ['Aïda Ndiaye', null, 0],
                    ],
                ],
                [
                    'code' => 'THIES',
                    'name' => 'Boutik Thiès Centre',
                    'address' => 'Avenue Général de Gaulle, Thiès',
                    'phone' => '33 951 00 00',
                    'categories' => ['Boissons', 'Épicerie'],
                    'products' => [
                        ['Fanta Orange 33cl', 300, 250, 'Boissons', 'piece', 30, 10],
                        ['Riz brisé parfumé', 450, 380, 'Épicerie', 'kg', 80, 20],
                        ['Sucre en poudre', 600, 520, 'Épicerie', 'kg', 25, 10],
                    ],
                    'customers' => [
                        ['Ibrahima Fall', '70 111 22 33', 2000],
                    ],
                ],
            ];

            foreach ($shops as $spec) {
                $shop = Shop::create([
                    'name' => $spec['name'],
                    'address' => $spec['address'],
                    'phone' => $spec['phone'],
                    'created_by' => $superadmin->id,
                ]);

                ShopCode::create(['code' => $spec['code'], 'shop_id' => $shop->id]);

                ShopOwner::create([
                    'shop_id' => $shop->id,
                    'name' => 'Propriétaire',
                    'email' => 'owner-'.strtolower($spec['code']).'@boutik.sn',
                    'password_hash' => $ownerPasswordHash,
                ]);

                Setting::create([
                    'shop_id' => $shop->id,
                    'shop_name' => $spec['name'],
                    'address' => $spec['address'],
                    'phone' => $spec['phone'],
                    'receipt_footer' => 'Merci de votre visite !',
                    'admin_pin_hash' => $pinHash,
                ]);

                $categoryIds = [];
                foreach ($spec['categories'] as $categoryName) {
                    $categoryIds[$categoryName] = Category::create([
                        'shop_id' => $shop->id,
                        'name' => $categoryName,
                    ])->id;
                }

                foreach ($spec['products'] as [$name, $sell, $buy, $categoryName, $unit, $qty, $threshold]) {
                    Product::create([
                        'shop_id' => $shop->id,
                        'category_id' => $categoryIds[$categoryName],
                        'name' => $name,
                        'price_sell' => $sell,
                        'price_buy' => $buy,
                        'unit' => $unit,
                        'stock_quantity' => $qty,
                        'stock_alert_threshold' => $threshold,
                    ]);
                }

                foreach ($spec['customers'] as [$name, $phone, $debt]) {
                    Customer::create([
                        'shop_id' => $shop->id,
                        'name' => $name,
                        'phone' => $phone,
                        'current_debt' => $debt,
                    ]);
                }
            }

            $this->command?->info('Seed terminé.');
            $this->command?->info('Superadmin : admin@boutik.sn / changeme123');
            $this->command?->info('Boutiques : SANDAGA, THIES — PIN admin par défaut : 1234');
        });
    }
}
