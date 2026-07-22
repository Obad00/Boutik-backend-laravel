<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HttpException;
use App\Http\Controllers\Controller;
use App\Models\Superadmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperadminAuthController extends Controller
{
    public function login(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            throw new HttpException(400, 'Email et mot de passe requis');
        }

        $admin = Superadmin::where('email', $email)->first();
        if (! $admin || ! Hash::check($password, $admin->password_hash)) {
            throw new HttpException(401, 'Identifiants incorrects');
        }

        $token = $admin->createToken('superadmin')->plainTextToken;

        return response()->json([
            'token' => $token,
            'superadmin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }
}
