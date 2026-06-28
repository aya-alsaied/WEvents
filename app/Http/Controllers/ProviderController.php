<?php

namespace App\Http\Controllers;

use App\Models\HallAvailability;
use App\Models\Provider;
use App\Models\ProviderProfile;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function importProviders(Request $request)
    {
        $request->validate([
            'providers' => 'required|array',
        ]);

        foreach ($request->providers as $data) {

            if (!isset($data['email'])) {
                continue;
            }

            $provider = Provider::updateOrCreate(
                [
                    'email' => $data['email']
                ],
                [
                    'name'       => $data['name'] ?? null,
                    'email'      => $data['email'],
                    'password'   => bcrypt('12345678'),
                    'phone'      => '0999999999',
                    'country'    => 'Syria',
                    'type'       => 'provider',
                    'descriptions' => $data['descriptions'],
                    'image'      => 'default.jpg',
                    'isApproved' => true,
                ]
            );

            $provider->profile()->updateOrCreate(
                [
                    'provider_id' => $provider->id
                ],
                [
                    'theme'          => $data['theme'] ?? null,
                    'pic'            => $data['pic'] ?? null,
                    'navbar'         => $data['navbar'] ?? null,
                    'hero'           => $data['hero'] ?? null,
                    'about'          => $data['about'] ?? null,
                    'services_data'  => $data['services_data'] ?? null,
                    'public_events'  => $data['public_events'] ?? null,
                    'recent'         => $data['recent'] ?? null,
                    'benefits'       => $data['benefits'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Providers imported successfully'
        ]);
    }

    public function getMyProfile()
    {
        $provider = auth('provider')->user();

        if (!$provider) {
            return response()->json([
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->load([
            'profile',
            'services',
            'halls',
            'foods',
            'decorations',
            'publicParties'
        ]);

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'provider' => $provider
        ], 200);
    }

    public function showProvider($providerId)
    {
        $provider = Provider::with([
            'profile',
            'services',
            'halls',
            'foods',
            'decorations',
            'publicParties'
        ])
            ->where('id', $providerId)
            ->where('isApproved', true)
            ->first();

        if (!$provider) {
            return response()->json([
                'message' => 'Provider not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Provider profile retrieved successfully',
            'provider' => $provider
        ], 200);
    }


    public function indexProviders()
    {
        $providers = Provider::with([
            'profile',
            'services',
            'halls',
            'foods',
            'decorations',
            'publicParties'
        ])
            ->where('isApproved', true)
            ->get();

        return response()->json([
            'message' => 'Providers retrieved successfully',
            'providers' => $providers
        ], 200);
    }
    
    public function register(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'email'       => 'required|email|unique:customers,email|unique:providers,email',
            'password'    => 'required|string|confirmed',
            'phone'       => 'required|string|digits:10|unique:providers,phone',
            'country'     => 'required|string',
            'type'        => 'required',
            'descriptions' => 'required|string',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'background_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'service_ids'   => 'required|array',
            'service_ids.*' => 'exists:services,id'
        ]);

        $path = $request->file('image')->store('provider_images', 'public');
        $path_image = Storage::url($path);

        $bgPath = $request->file('background_image')->store('provider_backgrounds', 'public');
        $path_bg_image = Storage::url($bgPath);

        $provider = Provider::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'country'    => $request->country,
            'type'       => $request->type,
            'descriptions' => $request->descriptions,
            'image'      => $path_image,
            'background_image' => $path_bg_image,
            'isApproved' => false
        ]);

        if (!empty($request->service_ids)) {
            $provider->services()->attach($request->service_ids);
        }

        return response()->json([
            'message' => 'Your account has been created with requested services and is awaiting admin approval.',
            'services' => $provider->services()->get()
        ], 201);
    }


    public function getUnapprovedProviders()
    {
        $providers = Provider::with('services')->unapproved()->get();

        return response()->json($providers, 200);
    }

    public function getApprovedProviders()
    {
        $providers = Provider::with('services')->approved()->get();

        return response()->json($providers, 200);
    }


    public function assignMyServices(Request $request)
    {
        $provider = auth('provider')->user();
        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 401);
        }

        $validated = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id'
        ]);

        $existingServiceIds = $provider->services()->pluck('services.id')->toArray();
        $newServiceIds = array_diff($validated['service_ids'], $existingServiceIds);

        if (count($newServiceIds) > 0) {
            $provider->services()->attach($newServiceIds);
        }

        return response()->json(['message' => 'Add Services Successful', 'services' => $provider->services()->get()], 201);
    }
}
