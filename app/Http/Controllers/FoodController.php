<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Http\Requests\FoodRequest;
use App\Models\Provider;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class FoodController extends Controller
{

    public function showFood($foodId)
    {
        $food = Food::where('id', $foodId)->where('status', true)->without('status')->first();

        if (!$food) {
            return response()->json(['message' => 'Food not found'], 404);
        }

        return response()->json(['message' => 'Food retrieved successfully', 'data' => $food]);
    }


    public function indexFoods()
    {
        $foods = Food::where('status', true)->get();

        return response()->json(['message' => 'Foods retrieved successfully', 'data' => $foods]);
    }

    public function providerFoods($providerId)
    {
        $foods = Food::where('provider_id', $providerId)->where('status', true)->get();

        return response()->json(['message' => 'Provider foods retrieved successfully', 'data' => $foods]);
    }




    public function getUnapprovedFoods()
    {
        $pendingFoods = Food::with(['provider:id,name,email,image'])->where('status', false)->get();
        return response()->json($pendingFoods);
    }

    public function getApprovedFoods()
    {
        $approveFoods = Food::with(['provider:id,name,email,image'])->where('status', true)->get();
        return response()->json($approveFoods);
    }


    public function MyFoodsFalse()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $foods = $provider->foods()->where('status', false)->get();
        return response()->json(['All foods not approve yet...' => $foods, 'Provider' => $provider->name], 200);
    }

    public function MyFoodsTrue()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $foods = $provider->foods()->where('status', true)->get();
        return response()->json(['All foods' => $foods, 'Provider' => $provider->name], 200);
    }


    public function store(FoodRequest $request)
    {
        $provider_id = auth('provider')->user();
        $provider = $provider_id->id;

        $validatedData = $request->validated();
        $validatedData['provider_id'] = $provider;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('food_images', 'public');
            $path_image = Storage::url($path);
            $validatedData['image'] = $path_image;
        }

        $food = Food::create($validatedData);
        return response()->json(['message' => 'Send the food for approve by admin'], 201);
    }


    public function deleteFood($foodId)
    {
        $providerId = auth('provider')->user();
        if (!$providerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $food = $providerId->foods()->where('id', $foodId)->first();
        if (!$food) {
            return response()->json(['message' => 'the food not found..'], 404);
        }
        $food->delete();
        return response()->json(['message' => 'Hall delete successfully'], 200);
    }


    public function filterFoods(Request $request, $providerId = null)
    {

        //$sortOrder = in_array($request->get('sort'), ['asc', 'desc']) ? $request->get('sort') : 'asc';


        if ($providerId) {
            $query = Provider::findOrFail($providerId)->foods();
        } else {
            $query = Food::query();
        }

        if ($request->filled('price')) {
            $query->ofPrice($request->price);
        }

        if ($request->filled('location')) {
            $query->ofLocation($request->location);
        }

        $foods = $query->get();
        return response()->json(['Foods:' => $foods], 200);
    }
}
