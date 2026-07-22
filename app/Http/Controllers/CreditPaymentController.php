<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\CashMovement;
use App\Models\CreditPayment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditPaymentController extends Controller
{
    public function index()
    {
        $payments = CreditPayment::with('customer:id,name')->orderByDesc('created_at')->get();

        $result = $payments->map(fn (CreditPayment $p) => [
            ...$p->toArray(),
            'customer_name' => $p->customer?->name,
        ]);

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $customerId = $request->input('customer_id');
        $amount = $request->input('amount');

        if (! $customerId || ! is_numeric($amount) || (float) $amount <= 0) {
            throw new HttpException(400, 'customer_id et montant positif requis');
        }

        [$payment, $customerName] = DB::transaction(function () use ($customerId, $amount) {
            $customer = Customer::find($customerId);
            if (! $customer) {
                throw new HttpException(404, 'Client introuvable pour cette boutique');
            }

            $payment = CreditPayment::create([
                'customer_id' => $customerId,
                'amount' => $amount,
            ]);

            $customer->update(['current_debt' => max(0, (float) $customer->current_debt - (float) $amount)]);

            CashMovement::create([
                'type' => 'in',
                'amount' => $amount,
                'reason' => "Remboursement crédit — {$customer->name}",
            ]);

            return [$payment, $customer->name];
        });

        return response()->json([
            ...$payment->toArray(),
            'customer_name' => $customerName,
        ], 201);
    }
}
