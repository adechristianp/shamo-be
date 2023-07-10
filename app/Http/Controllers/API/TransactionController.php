<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request){
        $id = $request->input('id');
        $limit = $request->input('limit', 5);
        $status = $request->input('status');

        if($id){
            $transaction = Transaction::with(['items.product'])->find($id);

            if($transaction){
                return ResponseFormatter::success($transaction, 'Data transaksi berhasil diambil');
            }else{
                return ResponseFormatter::error(null, 'Data transaksi tidak ada', 404);
            }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if($status){
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success($transaction->paginate($limit), "Success");
    }

    public function checkout(Request $request){
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exist:products,id',
            'address' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
            'total_price' => 'required',
            'shipping_price' => 'required',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'status' => $request->status,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
        ]);
        // users_id	bigint	
        // products_id	bigint	
        // transactions_id	bigint	
        // quantity	bigint
        foreach ($request->items as $product) {
            TransactionDetail::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product->id,
                'transactions_id' => $transaction->id,
                'quantity' => $product->quantity,
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), "Transaksi berhasil");
    }
}