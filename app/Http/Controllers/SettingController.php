<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function show()
    {
        $settings = Setting::first();
        if (! $settings) {
            throw new HttpException(404, 'Paramètres introuvables');
        }

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $settings = Setting::first();
        if (! $settings) {
            throw new HttpException(404, 'Paramètres introuvables');
        }

        $data = $request->only(['shop_name', 'address', 'phone', 'receipt_footer']);

        if ($request->filled('admin_pin')) {
            $data['admin_pin_hash'] = Hash::make((string) $request->input('admin_pin'));
        }

        if (empty($data)) {
            throw new HttpException(400, 'Aucune modification fournie');
        }

        $settings->update($data);

        return response()->json($settings->fresh());
    }
}
