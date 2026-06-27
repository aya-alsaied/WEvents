<?php

namespace App\Http\Controllers;

use App\Models\Hall;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HallServiceController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'hall_id'     => 'required|exists:halls,id',
            'name'        => 'required|string|max:255',
            'price'       => 'required|decimal:8,2',
        ]);


        $hall = Hall::findOrFail($validatedData['hall_id']);

        if ($hall->provider_id !== Auth::id()) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $service = Service::create([
            'hall_id'     => $validatedData['hall_id'],
            'name'        => $validatedData['name'],
            'price'       => $validatedData['price'],
        ]);

        return response()->json(['message' => 'Add Service Success', 'service' => $service], 201);
    }


    public function destroy($id)
    {
        $service = Service::with('hall')->findOrFail($id);

        if ($service->hall->provider_id !== Auth::id()) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $service->delete();

        return response()->json(['message' => 'Delete Service Success'], 200);
    }
}
