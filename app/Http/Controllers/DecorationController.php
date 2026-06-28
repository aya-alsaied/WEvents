<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecorationRequest;
use App\Models\Decoration;
use App\Models\Provider;
use App\Models\Occasion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class DecorationController extends Controller
{
    public function getUnapprovedDecorations()
    {
        $pendingDecorations = Decoration::with(['provider:id,name,email,type,image'])->where('status', false)->get();
        return response()->json($pendingDecorations);
    }

    public function getApprovedDecorations()
    {
        $approveDecorations = Decoration::with(['provider:id,name,email,type,image'])->where('status', true)->get();
        return response()->json($approveDecorations);
    }

    public function store(DecorationRequest $request)
    {
        $provider = auth('provider')->id();

        $imagePaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('decorations', 'public');
                $imagePaths[] = Storage::url($path);
            }
        }

        $decoration = Decoration::create([
            'provider_id' => $provider,
            'information' => $request->information,
            'location' => $request->location,
            'price' => $request->price,
            'images' => $imagePaths,
            'status' => false,
        ]);

        if ($request->filled('occasion_ids')) {
            $decoration->occasions()->attach($request->occasion_ids);
        }

        return response()->json([
            'message' => 'Send the decoration for approve by admin'
        ], 201);
    }

    public function MyDecorationsFalse()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $decorations = $provider->decorations()->where('status', false)->get();
        return response()->json(['All decorations not approve yet...' => $decorations, 'Provider' => $provider->name], 200);
    }

    public function MyDecorationsTrue()
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'provider not found'], 404);
        }
        $decorations = $provider->decorations()->where('status', true)->get();
        return response()->json(['All decorations' => $decorations, 'Provider' => $provider->name], 200);
    }

    public function deleteDecoration($decorationId)
    {
        $providerId = auth('provider')->user();
        if (!$providerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $decoration = $providerId->decorations()->where('id', $decorationId)->first();
        if (!$decoration) {
            return response()->json(['message' => 'the decoration not found..'], 404);
        }
        $decoration->delete();
        return response()->json(['message' => 'Decoration delete successfully'], 200);
    }

    public function filterDecorations(Request $request, $providerId = null)
    {
        if ($providerId) {
            $query = Provider::findOrFail($providerId)
                ->decorations()
                ->with('occasions');
        } else {
            $query = Decoration::with('occasions');
        }

        $query->where('status', true);

        if ($request->filled('price')) {
            $query->ofPrice($request->price);
        }

        if ($request->filled('location')) {
            $query->ofLocation($request->location);
        }

        if ($request->filled('occasion_id')) {
            $query->ofOccasion($request->occasion_id);
        }

        $decorations = $query->get();

        return response()->json([
            'Decorations' => $decorations
        ], 200);
    }

    public function showDecoration($decorationId)
    {
        $decoration = Decoration::with('occasions')
            ->where('id', $decorationId)
            ->where('status', true)
            ->first();

        if (!$decoration) {
            return response()->json([
                'message' => 'Decoration not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Decoration retrieved successfully',
            'data' => $decoration
        ]);
    }


    public function indexDecorations()
    {
        $decorations = Decoration::with('occasions')
            ->where('status', true)
            ->get();

        return response()->json([
            'message' => 'Decorations retrieved successfully',
            'data' => $decorations
        ]);
    }

    public function providerDecorations($providerId)
    {
        $decorations = Decoration::with('occasions')
            ->where('provider_id', $providerId)
            ->where('status', true)
            ->get();

        return response()->json([
            'message' => 'Provider decorations retrieved successfully',
            'data' => $decorations
        ]);
    }

    public function indexOccasions()
    {
        return response()->json(Occasion::all());
    }
}
