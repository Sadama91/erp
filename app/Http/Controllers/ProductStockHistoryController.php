<?php
namespace App\Http\Controllers;

use App\Models\ProductStockHistory;
use App\Models\ProductStock;
use Illuminate\Http\Request;

class ProductStockHistoryController extends Controller
{
    public function index()
    {
        $histories = ProductStockHistory::with(['product', 'user'])->get();
        return view('product_stock_histories.index', compact('histories'));
    }

    public function show($id)
    {
        $history = ProductStockHistory::with(['product', 'user'])->findOrFail($id);
        return view('product_stock_histories.show', compact('history'));
    }

    public function create()
    {
        $stocks = ProductStock::all();
        return view('product_stock_histories.create', compact('stocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'stock_action' => 'required|string',
            'reason' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        ProductStockHistory::create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'stock_action' => $request->stock_action,
            'reason' => $request->reason,
            'user_id' => $request->user_id,
            'changed_at' => now(),
        ]);

        // Hier zou je ook de voorraad kunnen updaten op basis van de actie
        $this->updateStock($request->product_id, $request->quantity, $request->stock_action);

        return redirect()->route('product_stock_histories.index')
            ->with('success', 'Voorraadhistorie succesvol toegevoegd.');
    }

    protected function updateStock($productId, $quantity, $action)
    {
        $stock = ProductStock::where('product_id', $productId)->first();

        switch ($action) {
            case 'IN':
                $stock->available_quantity += $quantity;
                break;
            case 'OUT':
                $stock->available_quantity -= $quantity;
                break;
            case 'RESERVE':
                $stock->reserved_quantity += $quantity;
                break;
            case 'BLOCK':
                $stock->blocked_quantity += $quantity;
                break;
            case 'UNBLOCK':
                $stock->blocked_quantity -= $quantity;
                break;
            default:
                break;
        }

        $stock->save();
    }
}
