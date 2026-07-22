<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Exceptions\InsufficientStockException;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product:id,name')->orderByDesc('created_at')->get();

        $result = $orders->map(fn (Order $order) => $this->present($order));

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $items = $request->input('items');
        $total = $request->input('total');
        $paymentMode = $request->input('payment_mode');
        $customerId = $request->input('customer_id');
        $customerName = $request->input('customer_name');

        if (! is_array($items) || count($items) === 0) {
            throw new HttpException(400, 'Le panier est vide');
        }
        if (! in_array($paymentMode, ['cash', 'credit'], true)) {
            throw new HttpException(400, 'Mode de paiement invalide');
        }
        if ($paymentMode === 'credit' && ! $customerId) {
            throw new HttpException(400, 'Un client est requis pour une vente à crédit');
        }

        $order = DB::transaction(function () use ($items, $total, $paymentMode, $customerId) {
            // 1. Verrouille et valide le stock de CHAQUE ligne AVANT toute écriture.
            $products = [];
            foreach ($items as $line) {
                $product = Product::where('id', $line['product_id'])->lockForUpdate()->first();

                if (! $product) {
                    throw new HttpException(404, "Produit introuvable : {$line['product_name']}");
                }
                if ((float) $product->stock_quantity < (float) $line['quantity']) {
                    throw new InsufficientStockException("Stock insuffisant pour {$product->name}");
                }

                $products[$line['product_id']] = $product;
            }

            // 2. Commande.
            $order = Order::create([
                'customer_id' => $customerId,
                'total' => $total,
                'payment_mode' => $paymentMode,
            ]);

            // 3. Lignes + décrément stock + mouvement de stock.
            foreach ($items as $line) {
                $product = $products[$line['product_id']];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);

                $product->decrement('stock_quantity', (float) $line['quantity']);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $line['quantity'],
                    'reason' => 'Vente #'.substr($order->id, -6),
                ]);
            }

            // 4. Encaissement ou dette client.
            if ($paymentMode === 'cash') {
                CashMovement::create([
                    'order_id' => $order->id,
                    'type' => 'in',
                    'amount' => $total,
                    'reason' => 'Vente comptant #'.substr($order->id, -6),
                ]);
            } elseif ($customerId) {
                Customer::where('id', $customerId)->increment('current_debt', (float) $total);
            }

            return $order;
        });

        $order->load('items.product:id,name');

        return response()->json([
            ...$this->present($order),
            'customer_name' => $customerName,
        ], 201);
    }

    private function present(Order $order): array
    {
        return [
            'id' => $order->id,
            'shop_id' => $order->shop_id,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
            'payment_mode' => $order->payment_mode,
            'created_at' => $order->created_at,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]),
        ];
    }
}
