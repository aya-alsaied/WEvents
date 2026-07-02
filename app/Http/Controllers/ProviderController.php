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

        // 1. جلب البروفايدر الحالي المسجل دخوله عبر التوكن (الحالي حصراً)
        $currentProvider = auth('provider')->user();

        if (!$currentProvider) {
            return response()->json([
                'message' => 'Unauthenticated or Provider not found'
            ], 401);
        }

        DB::beginTransaction();

        try {
            foreach ($request->providers as $data) {
                $about = $data['about'] ?? [];
                $pic = $data['pic'] ?? [];

                $providerName = $about['name'] ?? $currentProvider->name;

                // 2. تحديث بيانات البروفايدر الحالي "فقط" بناءً على الـ JSON الممرر
                // تنبيه بروفيسور: تم إلغاء التخمين الثابت، التحديث يتم للتوكن الحالي حصراً
                $currentProvider->update([
                    'name'             => $providerName,
                    'country'          => $about['location'] ?? $currentProvider->country,
                    'descriptions'     => $about['info'] ?? $currentProvider->descriptions,
                    'image'            => $pic['personalpic'] ?? $currentProvider->image,
                    'background_image' => $pic['backgroundpic'] ?? $currentProvider->background_image,
                ]);

                // 3. تخزين أو تحديث الـ Profile وربطه بـ ID البروفايدر الحالي صاحب التوكن
                $currentProvider->profile()->updateOrCreate(
                    [
                        'provider_id' => $currentProvider->id // يضمن التخزين للبروفايدر الصحيح
                    ],
                    [
                        'theme'         => $data['theme'] ?? null,
                        'pic'           => $data['pic'] ?? null,
                        'about'         => $data['about'] ?? null,
                        'services_data' => $data['services'] ?? null,
                        'public_events' => $data['publicEvents'] ?? null,
                        'recent'        => $data['recent'] ?? null,
                        'benefits' => $data['benefits'] ?? null,
                    ]
                );

                // 4. ربط الخدمات في جدول الـ Pivot للبروفايدر الحالي
                if (!empty($data['services']) && is_array($data['services'])) {
                    $serviceIds = array_column($data['services'], 'id');
                    $currentProvider->services()->sync($serviceIds); 
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Your provider profile imported successfully!'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to import profile',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function getMyProfile()
{
    $provider = auth('provider')->user();

    if (!$provider) {
        return response()->json([
            'message' => 'Provider not found'
        ], 404);
    }

    $provider->load(['profile', 'services']);
    $profile = $provider->profile;

    $rawPublicEvents = $profile?->public_events ?? [];
    $publicEventIds = [];

    if (!empty($rawPublicEvents)) {
        if (is_array($rawPublicEvents) && isset($rawPublicEvents[0]) && is_array($rawPublicEvents[0])) {
            $publicEventIds = array_column($rawPublicEvents, 'id');
        } else {
            $publicEventIds = $rawPublicEvents;
        }
    }

    if (empty($publicEventIds)) {
        $publicEventIds = \App\Models\PublicParty::where('provider_id', $provider->id)
            ->where('status', true)
            ->pluck('id')
            ->toArray();
    }

    $servicesData = [];
    if (!empty($profile?->services_data)) {
        $servicesData = $profile->services_data;
    } else {
        foreach ($provider->services as $service) {
            $serviceName = strtolower($service->name);
            
            $type = 'hall';
            $href = '/halls';
            
            if ($serviceName === 'food' || $serviceName === 'foods' || $serviceName === 'food services') {
                $type = 'food';
                $href = '/foodservices';
            } elseif ($serviceName === 'decorations' || $serviceName === 'decoration' || $serviceName === 'planning') {
                $type = 'planning';
                $href = '/plannings';
            }

            $servicesData[] = [
                'id'    => $service->id,
                'label' => $service->name,
                'href'  => $href,
                'type'  => $type
            ];
        }
        
        if (!empty($publicEventIds)) {
            $servicesData[] = [
                'id'    => 4,
                'label' => 'Public Events',
                'href'  => '/events',
                'type'  => 'public_event'
            ];
        }
    }

    // المنطق الجديد لفلترة الـ benefitpic بناءً على محتوى قاعدة البيانات الفعلي
    $benefitPicValue = null;
    if ($profile && is_array($profile->pic)) {
        if (array_key_exists('benefitpic', $profile->pic)) {
            $storedPic = $profile->pic['benefitpic'];
            $providerImage = $provider->image; 

            // فحص ذكي: إذا كانت الصورة المخزنة هي نفسها صورة الشخصية للمزود، نعتبرها null (لم يتم عمل import مخصص)
            if (empty($storedPic) || $storedPic === $providerImage) {
                $benefitPicValue = null;
            } else {
                $benefitPicValue = $storedPic;
            }
        } else {
            $benefitPicValue = null;
        }
    } else {
        $benefitPicValue = null; 
    }

    $responseData = [
        'id'    => $provider->id,
        'theme' => $profile?->theme ?? [
            'background' => '#ffffff',
            'text'       => '#111111',
            'primary'    => '#000',
            'button'     => ['background' => '#000000', 'text' => '#ffffff'],
            'card'       => ['background' => '#ffffff', 'text' => '#000000', 'buttoncolor' => '#000000', 'buttontext' => '#ffffff']
        ],
        
        'pic' => [
            'benefitpic'    => $benefitPicValue,
            'backgroundpic' => $profile?->pic['backgroundpic'] ?? ($provider->background_image ?? "/images/2.jpg"),
            'personalpic'   => $profile?->pic['personalpic'] ?? ($provider->image ?? "/images/OIP.webp")
        ],

        'about' => $profile?->about ?? [
            'name'     => $provider->name,
            'location' => $provider->country ?? 'Damascus',
            'title'    => 'About Us',
            'info'     => $provider->descriptions ?? 'Information about this profile and what services are provided by this website.'
        ],
        'services'     => $servicesData,
        'publicEvents' => $publicEventIds,
        'recent'       => $profile?->recent ?? [1, 2, 3],
        
        'benefits'     => $profile?->benefits ?? [
            [
                'title' => 'All in one',
                'info'  => 'Plan venue, decor, and food in one place'
            ],
            [
                'title' => 'Easy management',
                'info'  => 'Manage all your events from one dashboard'
            ],
            [
                'title' => 'Fast booking',
                'info'  => 'Book services quickly without complications'
            ]
        ]
    ];

    return response()->json($responseData, 200);
}
    public function showProvider($providerId)
    {
        $provider = Provider::with([
            'profile',
            'wallet',
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
            'wallet',
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

        $provider->wallet()->create([
            'balance' => 0
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
