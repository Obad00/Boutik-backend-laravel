<?php

namespace App\Http\Controllers\Superadmin;

use App\Exceptions\HttpException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopCode;
use App\Models\ShopOwner;
use App\Support\ShopCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::orderByDesc('created_at')->get();

        $withCodes = $shops->map(function (Shop $shop) {
            return [
                ...$shop->toArray(),
                'code' => ShopCode::where('shop_id', $shop->id)->value('code'),
            ];
        });

        return response()->json($withCodes);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $address = trim((string) $request->input('address', ''));
        $phone = trim((string) $request->input('phone', ''));

        if ($name === '' || $address === '' || $phone === '') {
            throw new HttpException(400, 'Nom, adresse et téléphone requis');
        }

        [$shop, $code] = DB::transaction(function () use ($name, $address, $phone, $request) {
            $shop = Shop::create([
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
                'created_by' => $request->user('sanctum')->id,
            ]);

            $code = ShopCodeGenerator::generateUnique($name);
            ShopCode::create(['code' => $code, 'shop_id' => $shop->id]);

            Setting::create([
                'shop_id' => $shop->id,
                'shop_name' => $name,
                'address' => $address,
                'phone' => $phone,
                'receipt_footer' => 'Merci de votre visite !',
                'admin_pin_hash' => Hash::make('1234'),
            ]);

            Category::create(['shop_id' => $shop->id, 'name' => 'Général']);

            // Un owner est indispensable pour que le login par code boutique
            // fonctionne (voir ShopAuthController::login) — sans lui la boutique
            // resterait créée mais impossible à utiliser.
            ShopOwner::create([
                'shop_id' => $shop->id,
                'name' => 'Propriétaire',
                'email' => 'owner-'.strtolower($code).'@boutik.sn',
                'password_hash' => Hash::make(Str::random(32)),
            ]);

            return [$shop, $code];
        });

        return response()->json([
            'shop' => ['id' => $shop->id, 'name' => $shop->name, 'address' => $shop->address, 'phone' => $shop->phone],
            'code' => $code,
        ], 201);
    }

    public function update(Request $request, Shop $shop)
    {
        $name = $request->filled('name') ? trim((string) $request->input('name')) : null;
        $address = $request->filled('address') ? trim((string) $request->input('address')) : null;
        $phone = $request->filled('phone') ? trim((string) $request->input('phone')) : null;

        DB::transaction(function () use ($shop, $name, $address, $phone) {
            $shop->fill(array_filter([
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
            ], fn ($v) => $v !== null))->save();

            $settings = Setting::where('shop_id', $shop->id)->first();
            if ($settings) {
                $settings->fill(array_filter([
                    'shop_name' => $name,
                    'address' => $address,
                    'phone' => $phone,
                ], fn ($v) => $v !== null))->save();
            }
        });

        return response()->json($shop->fresh());
    }

    public function destroy(Shop $shop)
    {
        // Désactivation logique uniquement — voir mysql/schema.sql note 6
        // (jamais de DELETE direct, risque de cascade InnoDB cassée).
        $shop->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }
}
