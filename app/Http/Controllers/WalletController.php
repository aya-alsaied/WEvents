<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /*
    عرض المحفظة
    */
    public function myWallet()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'wallet' => $customer->wallet
        ]);
    }

    /*
    عرض العمليات المالية
    */
    public function transactions()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $wallet = $customer->wallet;

        return response()->json([
            'transactions' => $wallet
                ->transactions()
                ->latest()
                ->get()
        ]);
    }

    /*
    شحن المحفظة (تجريبي)
    */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $wallet = $customer->wallet;

        DB::beginTransaction();

        try {

            $before = $wallet->balance;

            $wallet->increment(
                'balance',
                $request->amount
            );

            $wallet->refresh();

            $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $request->amount,
                'balance_before' => $before,
                'balance_after' => $wallet->balance,
                'description' => 'Wallet recharge'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Wallet charged successfully',
                'balance' => $wallet->balance
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}