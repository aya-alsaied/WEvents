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
    // 1. جلب بيانات مقدم الخدمة الحالي المخول
    $provider = auth('provider')->user();

    if (!$provider) {
        return response()->json([
            'message' => 'Provider not found'
        ], 404);
    }

    // 2. تحميل العلاقة مع الملف الشخصي (Profile)
    $provider->load('profile');
    $profile = $provider->profile;

    // 3. استخراج مصفوفة الـ IDs الخاصة بالحفلات العامة من الـ JSON
    $publicEventIds = $profile?->public_events ?? [];

    // 4. جلب تفاصيل الحفلات كاملة بشرط أن تنتمي لهذا البروفايدر حصراً
    // تنبيه بروفيسور: أضفنا شرط المقارنة بـ provider_id لحماية وأمن البيانات
    $publicEventsDetails = \App\Models\PublicParty::whereIn('id', $publicEventIds)
        ->where('provider_id', $provider->id) // هذا السطر يضمن جلب حفلاته هو فقط
        ->get();

    // 5. إعادة تشكيل الاستجابة (Data Transformation)
    $responseData = [
        'id'            => $provider->id,
        'theme'         => $profile?->theme ?? null,
        'pic'           => $profile?->pic ?? null,
        'about'         => $profile?->about ?? null,
        'services'      => $profile?->services_data ?? [],
        'publicEvents'  => $publicEventsDetails, // تفاصيل الحفلات الخاصة به فقط
        'recent'        => $profile?->recent ?? [],
        'benefits'      => $profile?->benefits ?? [],
    ];

    // 6. إرسال الاستجابة المطابقة والمؤمنة تماماً
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
