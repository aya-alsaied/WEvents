<?php

namespace App\Http\Controllers;

use App\Http\Requests\HallRequest;
use Illuminate\Http\Request;
use App\Models\Hall;
use App\Models\HallAvailability;
use App\Models\HallBooking;
use App\Models\Provider;
use Illuminate\Support\Facades\Storage;


class HallController extends Controller
{

    public function showHall($hallId)
    {
        $hall = Hall::with('services')->where('id', $hallId)->where('status', true)->first();

        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }

        $hall->makeHidden(['status', 'buffer_minutes']);

        return response()->json(['message' => 'Hall retrieved successfully', 'data' => $hall]);
    }


    public function indexHalls()
    {
        $halls = Hall::where('status', true)->get();

        $halls->makeHidden(['status', 'buffer_minutes']);

        return response()->json(['message' => 'Halls retrieved successfully', 'data' => $halls]);
    }

    public function providerHalls($providerId)
    {
        $halls = Hall::where('provider_id', $providerId)->where('status', true)->get();

        $halls->makeHidden(['status', 'buffer_minutes']);

        return response()->json(['message' => 'Provider halls retrieved successfully', 'data' => $halls]);
    }


    public function HallInside()
    {
        $halls = Hall::inside()->get();

        $halls->makeHidden(['buffer_minutes']);

        return response()->json($halls);
    }

    public function HallOutside()
    {
        $halls = Hall::outside()->get();

        $halls->makeHidden(['buffer_minutes']);

        return response()->json($halls);
    }

    public function getUnapprovedHalls()
    {
        $pendingHalls = Hall::with(['provider:id,name,email,type,image'])->where('status', false)->get();
        return response()->json($pendingHalls);
    }

    public function getApprovedHalls()
    {
        $approveHalls = Hall::with(['provider:id,name,email,type,image'])->where('status', true)->get();
        return response()->json($approveHalls);
    }

    public function store(HallRequest $request)
    {

        $provider_id = auth('provider')->user();
        $provider = $provider_id->id;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('halls', 'public');
                $path_image = Storage::url($path);
                $imagePaths[] = $path_image;
            }
        }
        $hall = Hall::create([
            'provider_id' => $provider,
            'name' => $request->name,
            'type' => $request->type,
            'CapacityOfPeople' => $request->CapacityOfPeople,
            'location' => $request->location,
            'full_day_price' => $request->full_day_price,
            'hour_price' => $request->hour_price,
            'information' => $request->information,
            'rules' => $request->rules,
            'images' => $imagePaths,
            'buffer_minutes' => 60,
            'status' => false,

        ]);

        return response()->json(['message' => 'Send the hall for approve by admin'], 201);
    }

    public function MyHallsFalse()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $halls = $provider->halls()->where('status', false)->get();
        return response()->json(['All halls not approve yet...' => $halls, 'Provider' => $provider->name], 200);
    }

    public function MyHallsTrue()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $halls = $provider->halls()->where('status', true)->get();
        return response()->json(['All halls' => $halls, 'Provider' => $provider->name], 200);
    }


    public function filterHalls(Request $request, $providerId = null)
    {
        if ($providerId) {
            $query = Provider::findOrFail($providerId)->halls();
        } else {
            $query = Hall::query();
        }

        if ($request->type) {
            if ($request->type === 'inside') {
                $query->inside();
            } elseif ($request->type === 'outside') {
                $query->outside();
            }
        }

        if ($request->filled('price')) {
            $priceType = $request->price_type ?? 'full_day';

            $query->ofPrice(
                $request->price,
                $priceType
            );
        }

        if ($request->filled('CapacityOfPeople')) {
            $query->minCapacity($request->CapacityOfPeople);
        }

        if ($request->filled('location')) {
            $query->ofLocation($request->location);
        }

        $halls = $query->get();

        return response()->json([
            'Halls' => $halls
        ]);
    }


    public function deleteHall($hallId)
    {
        $providerId = auth('provider')->user();
        if (!$providerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hall = $providerId->halls()->where('id', $hallId)->first();
        if (!$hall) {
            return response()->json(['message' => 'the hall not found..'], 404);
        }
        $hall->delete();
        return response()->json(['message' => 'Hall delete successfully'], 200);
    }

    public function availableDays($hallId)
    {
        $days = HallAvailability::where('hall_id', $hallId)->where('status', 'available')->select('date')->distinct()->pluck('date');
        return response()->json($days);
    }

    public function availableHours($hallId, $date)
    {
        $hours = HallAvailability::where('hall_id', $hallId)->where('date', $date)->where('status', 'available')->where('availability_type', 'hourly')->get();
        return response()->json($hours);
    }

    public function addAvailability(Request $request, $hallId)
    {
        $request->validate([
            'date' => 'required|date',
            'availability_type' => 'required|in:full_day,hourly',
            'start_time' => 'nullable',
            'end_time' => 'nullable'
        ]);

        $providerId = auth()->user()->id;

        $hall = Hall::findOrFail($hallId);

        if ($hall->provider_id != $providerId) {
            return response()->json([
                'message' => 'You are not allowed to manage this hall'
            ], 403);
        }


        $HallAvailability = HallAvailability::create([
            'hall_id' => $hallId,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'availability_type' => $request->availability_type
        ]);

        return response()->json(['message' => 'Availability added successfully', 'Hall Availability' => $HallAvailability]);
    }
}
