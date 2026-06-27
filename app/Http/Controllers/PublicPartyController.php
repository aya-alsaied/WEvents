<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PublicParty;
use App\Http\Requests\PublicPartyRequest;
use App\Models\Provider;
use Carbon\Carbon;

class PublicPartyController extends Controller
{


    public function showParty($partyId)
    {
        $party = PublicParty::where('id', $partyId)->where('status', true)->without('status')->first();

        if (!$party) {
            return response()->json(['message' => 'Party not found'], 404);
        }

        return response()->json(['message' => 'Party retrieved successfully', 'data' => $party]);
    }


    public function indexParties()
    {
        $parties = PublicParty::where('status', true)->get();

        return response()->json(['message' => 'Parties retrieved successfully', 'data' => $parties]);
    }

    public function providerParties($providerId)
    {
        $parties = PublicParty::where('provider_id', $providerId)->where('status', true)->get();

        return response()->json(['message' => 'Provider Parties retrieved successfully', 'data' => $parties]);
    }







    public function getUnapprovedPublicParties()
    {
        $pendingParties = PublicParty::with(['provider:id,name,email,type,image,tickets'])->where('status', false)->get();
        return response()->json($pendingParties);
    }

    public function getApprovedPublicParties()
    {
        $approveParties = PublicParty::with(['provider:id,name,email,type,image,tickets'])->where('status', true)->get();
        return response()->json($approveParties);
    }

    public function MyPublicPartiesFalse()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $parties = $provider->publicParties()->where('status', false)->get();

        $result = $parties->map(function ($party) {
            $formattedStart = Carbon::createFromFormat('H:i:s', $party->start_time)->format('g:i A');
            $formattedEnd = Carbon::createFromFormat('H:i:s', $party->end_time)->format('g:i A');

            return [
                'id' => $party->id,
                'title' => $party->title,
                'date' => $party->date,
                'start_time' => $formattedStart,
                'end_time' => $formattedEnd,
                'status' => $party->status,
                'provider_id' => $party->provider_id,
                'image' => $party->image,
                'tickets' => $party->tickets
            ];
        });
        return response()->json(['All Public Parties not approve yet...' => $result, 'Provider' => $provider->name], 200);
    }

    public function MyPublicPartiesTrue()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $parties = $provider->publicParties()->where('status', true)->get();


        $result = $parties->map(function ($party) {
            $formattedStart = Carbon::createFromFormat('H:i:s', $party->start_time)->format('g:i A');
            $formattedEnd = Carbon::createFromFormat('H:i:s', $party->end_time)->format('g:i A');

            return [
                'id' => $party->id,
                'title' => $party->title,
                'date' => $party->date,
                'start_time' => $formattedStart,
                'end_time' => $formattedEnd,
                'status' => $party->status,
                'provider_id' => $party->provider_id,
                'image' => $party->image,
                'tickets' => $party->tickets
            ];
        });
        return response()->json(['All halls' => $result, 'Provider' => $provider->name], 200);
    }

    public function store(PublicPartyRequest $request)
    {
        $provider_id = auth('provider')->user();
        $provider = $provider_id->id;

        $validatedData = $request->validated();
        $validatedData['provider_id'] = $provider;


        if ($request->filled('start_time')) {
            $validatedData['start_time'] = Carbon::createFromFormat('g:i A', $request->start_time)->format('H:i:s');
        }

        if ($request->filled('end_time')) {
            $validatedData['end_time'] = Carbon::createFromFormat('g:i A', $request->end_time)->format('H:i:s');
        }


        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('publicParties_images', 'public');
            //$path_image = Storage::url($path);
            $validatedData['image'] = $path;
        }

        $public = PublicParty::create($validatedData);
        return response()->json(['message' => 'Send the public for approve by admin'], 201);
    }


    public function deleteParty($partyId)
    {
        $providerId = auth('provider')->user();
        if (!$providerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $party = $providerId->publicParties()->where('id', $partyId)->first();
        if (!$party) {
            return response()->json(['message' => 'the Party not found..'], 404);
        }
        $party->delete();
        return response()->json(['message' => 'Party delete successfully'], 200);
    }

    public function filterParties(Request $request, $providerId = null)
    {

        //$sortOrder = in_array($request->get('sort'), ['asc', 'desc']) ? $request->get('sort') : 'asc';


        if ($providerId) {
            $query = Provider::findOrFail($providerId)->publicParties();
        } else {
            $query = PublicParty::query();
        }

        if ($request->filled('price')) {
            $query->ofPrice($request->price);
        }


        if ($request->filled('location')) {
            $query->ofLocation($request->location);
        }

        $parties = $query->get();
        return response()->json(['Parties:' => $parties], 200);
    }
}
