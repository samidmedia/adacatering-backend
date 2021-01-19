<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

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

    public function checkout(Request $request)
    {
        $request->validate([
            'food_id'=> 'required|exists:food,id',
            'user_id'=> 'required|exists:users,id',
            'quantity'=> 'required',
            'total'=> 'required',
            'status'=> 'required',
        ]);

        $transactions = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        // konfigurasi midtrans
            Config::$serverKey = config('services.midtrans.serverKey');
            Config::$isProduction = config('services.midtrans.isProduction');
            Config::$isSanitized = config('services.midtrans.isSanitized');
            Config::$is3ds = config('services.midtrans.is3ds');
            
        // panggil transaksi yang tadi dibuat
        $transactions = Transaction::with(['food','user'])->find($transactions->id);


        // Membuat Transaksi midtrans
            $midtrans =[
                'midtrans'=> [
                    'order_id'=>$transactions->id,
                    'gross_amount'=> (int) $transactions->total,
                ],
                'customer_details' => [
                    'first_name' => $transactions->user->name,
                    'email' => $transactions->user->email,
                ],
                'enable_payment'=> ['gopay','bank_transfer'],
                'vtweb' => []
            ];

        // memanggil midtrans
            try {
                //ambil halaman payment midtrans nya 
                $paymentUrl =Snap::createTransaction($midtrans)->redirect_url;
                $transactions->payment_url = $paymentUrl;
                $transactions->save();

                return ResponseFormatter::success($transactions, 'Transaksi berhasil');
            } catch (Exception $e) {
              return ResponseFormatter::error($e->getMessage(), 'Transaksi gagal');
            }

        // Mengembalikan data ke API

    }
}
