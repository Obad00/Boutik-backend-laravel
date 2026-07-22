<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $categoryId = $request->input('category_id');
        $priceSell = $request->input('price_sell');

        if ($name === '' || ! $categoryId || ! $priceSell) {
            throw new HttpException(400, 'Champs requis manquants');
        }

        $product = Product::create([
            'name' => $name,
            'category_id' => $categoryId,
            'price_sell' => $priceSell,
            'price_buy' => $request->input('price_buy'),
            'unit' => $request->input('unit', 'piece'),
            'stock_quantity' => $request->input('stock_quantity', 0),
            'stock_alert_threshold' => $request->input('stock_alert_threshold', 5),
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $allowed = ['name', 'price_sell', 'price_buy', 'category_id', 'unit', 'stock_quantity', 'stock_alert_threshold'];
        $data = $request->only($allowed);

        if (empty($data)) {
            throw new HttpException(400, 'Aucune modification fournie');
        }

        $product->update($data);

        return response()->json($product->fresh());
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['ok' => true]);
    }

    public function adjustStock(Request $request, Product $product)
    {
        $delta = $request->input('delta');
        if (! is_numeric($delta)) {
            throw new HttpException(400, 'delta numérique requis');
        }

        $product->increment('stock_quantity', (float) $delta);

        return response()->json($product->fresh());
    }
}
