<?php

namespace App\Http\Controllers;

use App\Mail\VerifyMail;
use App\Mail\PasswordMail;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{

    /*public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:customers,email|unique:providers,email',
            'password' => 'required|string|confirmed',
            'type' => 'required|in:customer,provider,'
        ]);

        if ($request->type === 'customer') {
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'type' => $request->type,
                'password' => bcrypt($request->password)
            ]);
            $token = $customer->createToken('CustomerToken')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $customer
            ]);
        } else {
            $provider = Provider::create([
                'name' => $request->name,
                'email' => $request->email,
                'type' => $request->type,
                'password' => Hash::make($request->password),
                'isApproved' => false
            ]);
            return response()->json([
                'message' => 'Your account has been created and is awaiting admin approval.'
            ]);
        }
    }*/

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $customer = Customer::where('email', $request->email)->first();
        if ($customer && Hash::check($request->password, $customer->password)) {
            if (!$customer->isApproved) {
                return response()->json(['message' => 'Your account is still pending approval.', 403]);
            }
            $token = $customer->createToken('CustomerToken')->plainTextToken;
            return response()->json([
                'token' => $token,
                'type' => 'customer',
                'user' => $customer
            ]);
        }

        $provider = Provider::where('email', $request->email)->first();
        if ($provider && Hash::check($request->password, $provider->password)) {
            if (! $provider->isApproved) {
                return response()->json(['message' => 'Your account is still pending approval.', 403]);
            }
            $token = $provider->createToken('ProviderToken')->plainTextToken;
            return response()->json([
                'token' => $token,
                'type' => 'provider',
                'user' => $provider
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('AdminToken')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $admin
            ]);
        }

        return response()->json(['message' => 'Invalid credential.', 401]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'logout successfully']);
    }



    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:provider,customer,admin'
        ]);

        $model = $this->resolveModel($request->type);
        $user = $model::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not Found'], 406);
        }

        $code =  (string) rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        $user->verification_code = $code;
        $user->verification_code_expires_at = $expiresAt;
        $user->save();

        Mail::to($user->email)->send(new VerifyMail($code));
        return response()->json(['message' => 'Verification send code']);
    }



    public function verifyEmail(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'type' => 'required|in:provider,customer,admin'
        ]);

        $model = $this->resolveModel($request->type);
        $user = $model::where('email', $request->email)->first();

        if (!$user || $user->verification_code !== $request->code) {
            return response()->json(['error' => 'Invalid code'], 400);
        }

        if (Carbon::now()->gt($user->verification_code_expires_at)) {
            return response()->json(['error' => 'Code expired'], 400);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }
    private function resolveModel($type)
    {

        return match ($type) {

            'provider' => Provider::class,
            'customer' => Customer::class,
            'admin' => Admin::class,
            default => abort(400, 'Invalid user type')
        };
    }



    public function sendResetToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:provider,customer,admin'
        ]);

        $model = $this->resolveModel($request->type);
        $user = $model::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $token = Str::random(64);
        $user->reset_password_token = $token;
        $user->reset_password_expires_at = Carbon::now()->addMinutes(7);
        $user->save();

        Mail::to($user->email)->send(new PasswordMail($token));
        return response()->json(['message' => 'Reset Token Sent']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:provider,customer,admin',
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);

        $model = $this->resolveModel($request->type);
        $user = $model::where('email', $request->email)->first();

        if (!$user || $user->reset_password_token !== $request->token) {
            return response()->json(['error' => 'Invalid Token'], 400);
        }

        if (Carbon::now()->gt($user->reset_password_expires_at)) {
            return response()->json(['error' => 'Token Expired'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_password_token = null;
        $user->reset_password_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Password Reset Successfully']);
    }



    public function getMyProfile()
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
        } elseif (Auth::guard('provider')->check()) {
            $user = Auth::guard('provider')->user();
        } elseif (Auth::guard('customer')->check()) {
            $user = Auth::guard('customer')->user();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($user, 200);
    }


    // public function updateProfile(Request $request)
    // {
    //     $guard = null;

    //     if (Auth::guard('admin')->check()) {
    //         $guard = 'admin';
    //     } elseif (Auth::guard('provider')->check()) {
    //         $guard = 'provider';
    //     } elseif (Auth::guard('customer')->check()) {
    //         $guard = 'customer';
    //     } else {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     $user = Auth::guard($guard)->user();

    //     $validatedDate = $request->validate([
    //         'name' => 'sometimes|nullable|string',
    //         'phone' => 'sometimes|nullable|string',
    //         'country' => 'sometimes|nullable|string',
    //         'image' => 'nullable'
    //     ]);
    //     if ($guard = 'customer') {
    //         $imagePath = $request->hasFile('image') ? $request->file('image')->store('customer_images', options: 'public') : $user->image;
    //     } else {
    //         $imagePath = $request->hasFile('image') ? $request->file('image')->store('provider_images', options: 'public') : $user->image;
    //     }
    //     $validatedDate['image'] = $imagePath;
    //     $filteredData = array_filter($validatedDate, function ($value) {
    //         return $value !== null && $value !== '';
    //     });

    //     $user->update($filteredData);


    //     return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 201);
    // }








    public function updateProfileCustomer(Request $request)
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json(['error' => 'Customer not found or unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'name'    => 'sometimes|nullable|string',
            'phone'   => 'sometimes|nullable|string',
            'country' => 'sometimes|nullable|string',
            'image'   => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('customer_images', 'public');
            $validatedData['image'] = Storage::url($path);
        }

        $filteredData = array_filter($validatedData, function ($value) {
            return $value !== null && $value !== '';
        });

        $customer->update($filteredData);

        return response()->json([
            'message' => 'Customer profile updated successfully',
            'user'    => $customer
        ], 200);
    }



    public function updateProfileProvider(Request $request)
    {
        $provider = auth('provider')->user();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found or unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'name'             => 'sometimes|nullable|string',
            'phone'            => 'sometimes|nullable|string|digits:10|unique:providers,phone,' . $provider->id,
            'country'          => 'sometimes|nullable|string',
            'image'            => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'background_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',

            'service_ids'      => 'sometimes|nullable|array',
            'service_ids.*'    => 'exists:services,id'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('provider_images', 'public');
            $validatedData['image'] = Storage::url($path);
        }

        if ($request->hasFile('background_image')) {
            $bgPath = $request->file('background_image')->store('provider_backgrounds', 'public');
            $validatedData['background_image'] = Storage::url($bgPath);
        }

        $filteredData = array_filter($validatedData, function ($value) {
            return $value !== null && $value !== '';
        });

        $provider->update($filteredData);

        if ($request->has('service_ids')) {
            $provider->services()->sync($request->service_ids);
        }

        return response()->json([
            'message' => 'Provider profile updated successfully',
            'user'    => $provider->load('services')
        ], 200);
    }






    public function showUserProfile($id, $type)
    {
        if ($type === 'provider') {

            $provider = Provider::with(['services', 'profile'])->find($id);

            if (!$provider) {
                return response()->json(['error' => 'Provider not found'], 404);
            }

            $providedServices = $provider->services->pluck('name')->map(function ($item) {
                return strtolower(trim($item));
            })->toArray();

            $profileData = [
                'id'       => $provider->id,
                'name'     => $provider->name,
                'type'     => $provider->type,
                'image'    => $provider->image,
                'background_image' => $provider->background_image,
                'details'  => $provider->profile,
                'services' => $provider->services->pluck('name')->toArray(),
                'content'  => (object)[]
            ];

            $content = [];

            if (in_array('halls', $providedServices)) {
                $content['halls'] = $provider->halls()
                    ->where('status', true)
                    ->get(['id', 'name', 'CapacityOfPeople', 'full_day_price', 'hour_price', 'type', 'location']);
            }

            if (in_array('food', $providedServices)) {
                $content['foods'] = $provider->foods()->where('status', true)->get();
            }

            if (in_array('decoration', $providedServices)) {
                $content['decorations'] = $provider->decorations()->where('status', true)->get();
            }

            if (in_array('public parties', $providedServices)) {
                $content['public_parties'] = $provider->publicParties()->where('status', true)->get();
            }

            $profileData['content'] = $content;

            return response()->json([
                'type'    => 'provider',
                'profile' => $profileData
            ], 200);
        }

        if ($type === 'customer') {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            return response()->json([
                'type' => 'customer',
                'profile' => [
                    'name'  => $customer->name,
                    'type'  => $customer->type,
                    'image' => $customer->image
                ]
            ], 200);
        }

        return response()->json(['error' => 'Invalid type'], 400);
    }
}
