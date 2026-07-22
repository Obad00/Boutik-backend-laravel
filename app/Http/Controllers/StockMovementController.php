<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index()
    {
        $movements = StockMovement::with('product:id,name')->orderByDesc('created_at')->get();

        $result = $movements->map(fn (StockMovement $m) => [
            ...$m->toArray(),
            'product_name' => $m->product?->name,
        ]);

        return response()->json($result);
    }

    public function restock(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        if (! $productId || ! is_numeric($quantity) || (float) $quantity <= 0) {
            throw new HttpException(400, 'product_id et quantité positive requis');
        }

        $movement = DB::transaction(function () use ($productId, $quantity) {
            $product = Product::find($productId);
            if (! $product) {
                throw new HttpException(404, 'Produit introuvable pour cette boutique');
            }

            $product->increment('stock_quantity', (float) $quantity);

            return StockMovement::create([
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => $quantity,
                'reason' => 'Réapprovisionnement',
            ]);
        });

        return response()->json($movement, 201);
    }
}
