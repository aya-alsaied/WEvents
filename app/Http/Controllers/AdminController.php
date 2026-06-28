<?php

namespace App\Http\Controllers;

use App\Models\Decoration;
use App\Models\Provider;
use App\Models\Hall;
use App\Models\Service;
use App\Models\Food;
use App\Models\PublicParty;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function approveProvider(Request $request)
    {
        $provider_id = $request->id;
        $provider = Provider::findOrFail($provider_id);

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $provider->isApproved = true;
        $provider->save();

        return response()->json(['message' => 'Provider approved successfully', 'provider' => $provider], 200);
    }


    /* public function approveHall(Request $request)
    {
        $hall_id = $request->id;
        $hall = Hall::find($hall_id);
        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }
        $hall->status = true;
        $hall->save();
        return response()->json(['message' => 'Hall approved successfully', 'Hall' => $hall], 200);
    }*/


    public function approveHall(Request $request)
    {
        $hallId = $request->id;

        $hall = Hall::with(['provider:id,name,email,type'])->find($hallId);

        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }

        $provider = $hall->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found for this hall'], 404);
        }

        $hallService = Service::where('name', 'Halls')->first();

        if (!$hallService) {
            return response()->json(['message' => 'Service "Halls" not found'], 404);
        }

        $hasHallService = $provider->services()->where('service_id', $hallService->id)->exists();

        if (!$hasHallService) {
            $provider->services()->attach($hallService->id);
        }

        $hall->status = true;
        $hall->save();

        return response()->json([
            'message' => 'Hall approved successfully and service added if needed.',
            'Hall' => $hall
        ], 200);
    }


    public function approveFood(Request $request)
    {
        $foodId = $request->id;

        $food = Food::with(['provider:id,name,email,type'])->find($foodId);

        if (!$food) {
            return response()->json(['message' => 'Food not found'], 404);
        }

        $provider = $food->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found for this food'], 404);
        }

        $foodService = Service::where('name', 'Food')->first();

        if (!$foodService) {
            return response()->json(['message' => 'Service "Food" not found'], 404);
        }

        $hasFoodService = $provider->services()->where('service_id', $foodService->id)->exists();

        if (!$hasFoodService) {
            $provider->services()->attach($foodService->id);
        }

        $food->status = true;
        $food->save();

        return response()->json([
            'message' => 'Food approved successfully and service added if needed.',
            'Hall' => $food
        ], 200);
    }

    public function approvePublicParties(Request $request)
    {
        $partyId = $request->id;

        $party = PublicParty::with(['provider:id,name,email,type'])->find($partyId);

        if (!$party) {
            return response()->json(['message' => 'Public Party not found'], 404);
        }

        $provider = $party->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found for this party'], 404);
        }

        $partyService = Service::where('name', 'Public Parties')->first();

        if (!$partyService) {
            return response()->json(['message' => 'Service "Public Parties" not found'], 404);
        }

        $hasPartyService = $provider->services()->where('service_id', $partyService->id)->exists();

        if (!$hasPartyService) {
            $provider->services()->attach($partyService->id);
        }

        $party->status = true;
        $party->save();

        return response()->json([
            'message' => 'Public Party approved successfully and service added if needed.',
            'Hall' => $party
        ], 200);
    }








    public function approveDecoration(Request $request)
    {
        $decorationId = $request->id;

        $decoration = Decoration::with(['provider:id,name,email,type'])->find($decorationId);

        if (!$decoration) {
            return response()->json(['message' => 'Decoration not found'], 404);
        }

        $provider = $decoration->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found for this decoration'], 404);
        }

        $decorationService = Service::where('name', 'Decoration')->first();

        if (!$decorationService) {
            return response()->json(['message' => 'Service "Decoration" not found'], 404);
        }

        $hasDecorationService = $provider->services()->where('service_id', $decorationService->id)->exists();

        if (!$hasDecorationService) {
            $provider->services()->attach($decorationService->id);
        }

        $decoration->status = true;
        $decoration->save();

        return response()->json([
            'message' => 'Decoration approved successfully and service added if needed.',
            'Decoration' => $decoration
        ], 200);
    }





    public function deleteProvider($id)
    {
        $provider = Provider::findOrFail($id);

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $provider->delete();

        return response()->json(['message' => 'Provider deleted successfully']);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }




    public function suspendUser($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->status = false;
        $user->save();

        return response()->json(['message' => 'User account suspended successfully']);
    }


    public function suspendProvider($id)
    {
        $provider = Provider::findOrFail($id);

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $provider->status = false;
        $provider->save();

        return response()->json(['message' => 'Provider account suspended successfully']);
    }


    public function activateUser($id)
    {
        $user = User::findOrFail($id);

        $user->status = true;
        $user->save();

        return response()->json(['message' => 'User activated successfully']);
    }

    public function getAllApprovedPosts()
    {
        $halls = Hall::with('provider:id,name,image')
            ->where('status', true)
            ->get()
            ->map(function ($hall) {
                $hall->post_type = 'hall';
                return $hall;
            });

        $foods = Food::with('provider:id,name,image')
            ->where('status', true)
            ->get()
            ->map(function ($food) {
                $food->post_type = 'food';
                return $food;
            });

        $decorations = Decoration::with('provider:id,name,image')
            ->where('status', true)
            ->get()
            ->map(function ($decoration) {
                $decoration->post_type = 'decoration';
                return $decoration;
            });

        $parties = PublicParty::with('provider:id,name,image')
            ->where('status', true)
            ->get()
            ->map(function ($party) {
                $party->post_type = 'public_party';
                return $party;
            });

        $posts = collect()
            ->merge($halls)
            ->merge($foods)
            ->merge($decorations)
            ->merge($parties)
            ->values();

        return response()->json([
            'message' => 'Approved posts retrieved successfully',
            'data' => $posts
        ]);
    }
}
