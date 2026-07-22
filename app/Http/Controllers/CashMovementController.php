<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\CashMovement;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    public function index()
    {
        return response()->json(CashMovement::orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        $amount = $request->input('amount');
        $reason = trim((string) $request->input('reason', ''));

        if (! in_array($type, ['in', 'out'], true)) {
            throw new HttpException(400, 'type doit être "in" ou "out"');
        }
        if (! is_numeric($amount) || (float) $amount <= 0) {
            throw new HttpException(400, 'Montant positif requis');
        }
        if ($reason === '') {
            throw new HttpException(400, 'Motif requis');
        }

        $movement = CashMovement::create([
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        return response()->json($movement, 201);
    }
}
