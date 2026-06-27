<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;



class CustomerController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:customers,email|unique:providers,email',
            'password' => 'required|string|confirmed',
            'phone' => 'required|string|digits:10|unique:customers,phone',
            'country' => 'required|string',
            'type' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('customer_images', 'public');
            $path_image = Storage::url($path);
        } else {
            $path_image = null;
        }

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'country' => $request->country,
            'type' => $request->type,
            'image' => $path_image,
            'isApproved' => true,

        ]);
        //$token = $customer->createToken('CustomerToken')->plainTextToken;
        return response()->json([
            //'token' => $token,
            'user' => $customer
        ]);
    }
}
