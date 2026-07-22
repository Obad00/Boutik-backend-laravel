<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HttpException;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopCode;
use App\Models\ShopOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShopAuthController extends Controller
{
    public function login(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code', '')));
        if ($code === '') {
            throw new HttpException(400, 'Code boutique requis');
        }

        $shopCode = ShopCode::find($code);
        if (! $shopCode) {
            throw new HttpException(401, 'Code boutique invalide');
        }

        $shop = Shop::where('id', $shopCode->shop_id)->where('is_active', true)->first();
        if (! $shop) {
            throw new HttpException(401, 'Boutique introuvable ou désactivée');
        }

        $owner = ShopOwner::where('shop_id', $shop->id)->first();
        if (! $owner) {
            throw new HttpException(500, 'Aucun compte propriétaire associé à cette boutique');
        }

        $token = $owner->createToken('shop-owner')->plainTextToken;

        return response()->json([
            'token' => $token,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'address' => $shop->address,
                'phone' => $shop->phone,
                'created_at' => $shop->created_at,
            ],
            'owner' => [
                'id' => $owner->id,
                'shop_id' => $owner->shop_id,
                'name' => $owner->name,
                'role' => $owner->role,
            ],
        ]);
    }

    public function verifyAdminPin(Request $request)
    {
        $pin = (string) $request->input('pin', '');

        $setting = Setting::first();
        if (! $setting) {
            throw new HttpException(404, 'Paramètres introuvables');
        }

        if (! Hash::check($pin, $setting->admin_pin_hash)) {
            throw new HttpException(401, 'Code PIN incorrect');
        }

        return response()->json(['ok' => true]);
    }
}
