<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(Customer::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            throw new HttpException(400, 'Nom requis');
        }

        $customer = Customer::create([
            'name' => $name,
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'current_debt' => 0,
        ]);

        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = [];
        if ($request->has('name')) {
            $data['name'] = trim((string) $request->input('name'));
        }
        if ($request->has('phone')) {
            $data['phone'] = $request->input('phone') ? trim((string) $request->input('phone')) : null;
        }

        if (empty($data)) {
            throw new HttpException(400, 'Aucune modification fournie');
        }

        $customer->update($data);

        return response()->json($customer->fresh());
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['ok' => true]);
    }
}
