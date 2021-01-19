<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');
 

        if ($id) {
            
            $transactions = Transaction::with(['food','user'])->find($id);

            if ($transactions) 
            {
                return ResponseFormatter::success($transactions,'Data produk berhasil diambil');    
            }
            else {
                return ResponseFormatter::error(
                    null,'Data transaksi tidak ada',
                    404
                );
            }
        }

        $transactions = Transaction::with(['food','user'])
            ->where('user_id', Auth::user()->id);

        if ($food_id) {
            $food_id->where('food_id',$food_id);
        }

        if ($status) {
            $status->where('status',$status);
        }

        return ResponseFormatter::success(
            $transactions->paginate($limit),
            'Data list transaksi berhasil diambil'
        );





    }

    public function update(Request $request, $id)
    {
            $transactions = Transaction::findOrFail($id);

            $transactions->update($request->all());


            return ResponseFormatter::success($transactions, 'Transaksi berhasil diperbarui');
    }
}
