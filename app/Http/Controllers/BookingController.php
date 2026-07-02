<?php

namespace App\Http\Controllers;

use App\Models\HallAvailability;
use App\Models\HallBooking;
use App\Models\Hall;
use App\Models\FoodBooking;
use App\Models\Food;
use App\Models\Decoration;
use App\Models\DecorationBooking;
use App\Models\HallService;
use App\Models\PublicParty;
use App\Models\PublicPartyBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function bookHall(Request $request)
    {
        $request->validate([
            'hall_id'        => 'required|exists:halls,id',
            'date'           => 'required|date',
            'booking_type'   => 'required|in:full_day,hourly',
            'guest_count'    => 'required|integer|min:1',
            'start_time'     => 'nullable|required_if:booking_type,hourly',
            'end_time'       => 'nullable|required_if:booking_type,hourly',
            'notes'          => 'nullable|string',
            'services'       => 'nullable|array',
            'services.*'     => 'integer|exists:hall_services,id',
        ]);

        $hall = Hall::findOrFail($request->hall_id);

        if ($request->guest_count > $hall->CapacityOfPeople) {
            return response()->json(['message' => 'Hall capacity exceeded'], 422);
        }

        $basePrice = 0;
        $availability = null;

        if ($request->booking_type == 'full_day') {
            $availability = HallAvailability::where('hall_id', $hall->id)
                ->where('date', $request->date)
                ->where('availability_type', 'full_day')
                ->where('status', 'available')
                ->first();

            if (!$availability) {
                return response()->json(['message' => 'This day is not available'], 422);
            }

            $basePrice = $hall->full_day_price;
        }

        if ($request->booking_type == 'hourly') {
            $availability = HallAvailability::where('hall_id', $hall->id)
                ->where('date', $request->date)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->where('availability_type', 'hourly')
                ->where('status', 'available')
                ->first();

            if (!$availability) {
                return response()->json(['message' => 'This time slot is not available'], 422);
            }

            $start = strtotime($request->start_time);
            $end = strtotime($request->end_time);

            if ($end <= $start) {
                return response()->json(['message' => 'End time must be greater than start time'], 422);
            }

            $hours = ($end - $start) / 3600;
            $basePrice = $hours * $hall->hour_price;
        }

        $servicesToAttach = [];
        $servicesPrice = 0;

        if ($request->has('services') && !empty($request->services)) {
            $services = HallService::whereIn('id', $request->services)
                ->where('hall_id', $hall->id)
                ->get();

            foreach ($services as $service) {
                $servicesPrice += $service->price;
                $servicesToAttach[$service->id] = ['price' => $service->price];
            }
        }

        $totalPrice = $basePrice + $servicesPrice;
        $adminCommission = $totalPrice * 0.20;
        $providerAmount = $totalPrice - $adminCommission;

        // جلب زبون المحفظة والتحقق من الرصيد قبل بدء الـ Transaction
        $customer = auth('customer')->user();
        
        // التحقق من وجود علاقة wallet، وإن لم توجد نمنع الحجز
        if (!$customer->wallet || $customer->wallet->balance < $totalPrice) {
            return response()->json(['message' => 'Insufficient wallet balance. Please charge your wallet.'], 400);
        }

        DB::beginTransaction();

        try {
            // 1. تجميد الرصيد من محفظة الزبون
            $customer->wallet->decrement('balance', $totalPrice);
            $customer->wallet->increment('frozen_balance', $totalPrice);

            // 2. إنشاء الحجز بحالة معلقة وحالة دفع "مجمد"
            $booking = HallBooking::create([
                'hall_id'              => $hall->id,
                'hall_availability_id' => $availability->id,
                'customer_id'          => $customer->id,
                'date'                 => $request->date,
                'start_time'           => $request->start_time,
                'end_time'             => $request->end_time,
                'booking_type'         => $request->booking_type,
                'guest_count'          => $request->guest_count,
                'notes'                => $request->notes,
                'total_price'          => $totalPrice,
                'admin_commission'     => $adminCommission,
                'provider_amount'      => $providerAmount,
                'status'               => 'pending',
                'payment_status'       => 'holding' // تم تجميد المبلغ بنجاح
            ]);

            if (!empty($servicesToAttach)) {
               $booking->services()->attach($servicesToAttach);
            }

            $availability->update(['status' => 'booked']);

            DB::commit();

            $booking->load('services');

            return response()->json([
                'message' => 'Booking created successfully and amount has been frozen.',
                'booking' => $booking,
                'pricing' => [
                    'base_price' => $basePrice,
                    'services_price' => $servicesPrice,
                    'total_price' => $totalPrice,
                    'admin_commission' => $adminCommission,
                    'provider_amount' => $providerAmount
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong, booking failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function blockAvailability($id)
    {
        $availability = HallAvailability::findOrFail($id);
        $availability->status = 'blocked';
        $availability->save();

        return response()->json(['message' => 'Availability blocked']);
    }

    public function unblockAvailability($id)
    {
        $availability = HallAvailability::findOrFail($id);
        $availability->status = 'available';
        $availability->save();

        return response()->json(['message' => 'Availability restored']);
    }

    public function providerHallBookings()
    {
        $providerId = auth('sanctum')->user()->id;

        $bookings = HallBooking::with([
            'customer',
            'hall'
        ])
            ->whereHas('hall', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function approveHallBooking($id)
    {
        // شحن علاقة العميل والمزود مع الحسابات والمحافظ المقترنة بها
        $booking = HallBooking::with(['hall.provider.wallet', 'customer.wallet'])->findOrFail($id);

        if ($booking->hall->provider_id !== auth('sanctum')->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not pending'], 422);
        }

        DB::beginTransaction();

        try {
            // جلب الأدمن العام للمنصة ومحفظته
            $admin = \App\Models\User::where('role', 'admin')->first();
            if (!$admin) {
                throw new \Exception("Admin account not found for commission transfer.");
            }

            // قفل السجلات في قاعدة البيانات لمنع التداخل أثناء التحديث (Race Conditions)
            $customerWallet = DB::table('wallets')->where('id', $booking->customer->wallet->id)->lockForUpdate()->first();
            $providerWallet = DB::table('wallets')->where('id', $booking->hall->provider->wallet->id)->lockForUpdate()->first();
            $adminWallet    = DB::table('wallets')->where('user_id', $admin->id)->lockForUpdate()->first();

            if (!$customerWallet) {
                throw new \Exception("Customer wallet not found.");
            }

            // 1. خصم الأموال من الرصيد المجمد للزبون نهائياً
            DB::table('wallets')->where('id', $customerWallet->id)->decrement('frozen_balance', $booking->total_price);

            // 2. تحويل الصافي (80%) مباشرة لمحفظة المزود المتاحة
            if ($providerWallet) {
                DB::table('wallets')->where('id', $providerWallet->id)->increment('balance', $booking->provider_amount);
            }

            // 3. تخزين عمولة الأدمن (20%) وتحويلها إلى محفظته
            if ($adminWallet) {
                DB::table('wallets')->where('id', $adminWallet->id)->increment('balance', $booking->admin_commission);
            }

            // 4. تحديث الحجز ليصبح مؤكداً ومدفوعاً تلقائياً
            $booking->update([
                'status'         => 'confirmed',
                'payment_status' => 'paid'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking approved, admin commission stored, and payments distributed successfully.',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process payments during approval.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function rejectHallBooking($id)
    {
        $booking = HallBooking::with([
            'hall',
            'availability',
            'customer.wallet'
        ])->findOrFail($id);

        if ($booking->hall->provider_id !== auth('sanctum')->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not pending'], 422);
        }

        DB::beginTransaction();

        try {
            $customerWallet = $booking->customer->wallet;

            // إعادة الأموال المجمدة كاملة إلى رصيد الزبون المتاح
            if ($customerWallet) {
                $customerWallet->decrement('frozen_balance', $booking->total_price);
                $customerWallet->increment('balance', $booking->total_price);
            }

            $booking->update([
                'status'         => 'rejected',
                'payment_status' => 'refunded'
            ]);

            $booking->availability()->update([
                'status' => 'available'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking rejected successfully and amount refunded to customer.',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to reject booking.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function customerHallBookings()
    {
        $customerId = auth('customer')->id();

        $bookings = HallBooking::with(['hall'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();

        return response()->json([
            'data' => $bookings
        ]);
    }

    public function cancelHallBooking($id)
    {
        $booking = HallBooking::with([
            'hall',
            'availability',
            'customer.wallet'
        ])->findOrFail($id);

        $user = auth()->user();

        $isCustomer = $booking->customer_id == $user->id;
        $isProvider = $booking->hall->provider_id == $user->id;

        if (!$isCustomer && !$isProvider) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Already cancelled'], 422);
        }

        DB::beginTransaction();

        try {
            // فك التجميد وإرجاع المال فقط إذا كان الحجز لم يتم قبوله بعد (ما زال pending ومجمّد)
            if ($booking->status === 'pending' && $booking->payment_status === 'holding') {
                $customerWallet = $booking->customer->wallet;
                if ($customerWallet) {
                    $customerWallet->decrement('frozen_balance', $booking->total_price);
                    $customerWallet->increment('balance', $booking->total_price);
                }
                $booking->payment_status = 'refunded';
            }

            $booking->status = 'cancelled';
            $booking->save();

            $booking->availability()->update([
                'status' => 'available'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking cancelled successfully.',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel booking.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
   // Food Booking
    public function bookFood(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'meal_count' => 'required|integer|min:1',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'note' => 'nullable|string'
        ]);

        $food = Food::findOrFail($request->food_id);

        $totalPrice = $food->price * $request->meal_count;
        $adminCommission = $totalPrice * 0.20;
        $providerAmount = $totalPrice - $adminCommission;

        $booking = FoodBooking::create([
            'food_id' => $request->food_id,
            'customer_id' => auth('customer')->id(),
            'meal_count' => $request->meal_count,
            'total_price' => $totalPrice,
            'admin_commission' => $adminCommission,
            'provider_amount' => $providerAmount,
            'event_date' => $request->event_date,
            'event_time' => $request->event_time,
            'notes' => $request->note,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Food booking created successfully',
            'pricing' => [
                'total_price' => $totalPrice,
                'admin_commission' => $adminCommission,
                'provider_amount' => $providerAmount
            ],
            'booking' => $booking
        ], 201);
    }

    public function providerFoodBookings()
    {
        $providerId = auth('sanctum')->user()->id;

        $bookings = FoodBooking::with([
            'customer',
            'food'
        ])
            ->whereHas('food', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function approveFoodBooking($id)
    {
        $booking = FoodBooking::findOrFail($id);

        if ($booking->food->provider_id !== auth('sanctum')->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not pending'], 422);
        }

        $booking->update([
            'status' => 'approved',
            'payment_deadline' => now()->addDays(3)
        ]);

        return response()->json([
            'message' => 'Booking approved successfully',
            'booking' => $booking
        ]);
    }

    public function rejectFoodBooking($id)
    {
        $booking = FoodBooking::findOrFail($id);

        if ($booking->food->provider_id !== auth('sanctum')->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not pending'], 422);
        }

        $booking->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Booking rejected successfully',
            'booking' => $booking
        ]);
    }

    public function confirmFoodPayment($id)
    {
        $booking = FoodBooking::findOrFail($id);

        if ($booking->food->provider_id !== auth('sanctum')->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->status !== 'approved') {
            return response()->json([
                'message' => 'Booking is not approved'
            ], 422);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 422);
        }

        $booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'booking' => $booking
        ]);
    }

    public function customerFoodBookings()
    {
        $customerId = auth('customer')->id();

        $bookings = FoodBooking::with(['food'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();

        return response()->json([
            'data' => $bookings
        ]);
    }

    public function cancelFoodBooking($id)
    {
        $booking = FoodBooking::findOrFail($id);

        $user = auth()->user();

        if (auth('customer')->check()) {

            if ($booking->customer_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        if (auth('sanctum')->check() && $user->role === 'provider') {

            if ($booking->food->provider_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Already cancelled'], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
    // Decoration Booking
    public function bookDecoration(Request $request)
    {
        $request->validate([
            'decoration_id' => 'required|exists:decorations,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'notes' => 'nullable|string'
        ]);

        $decoration = Decoration::findOrFail($request->decoration_id);

        $totalPrice = $decoration->price;

        $adminCommission = $totalPrice * 0.20;
        $providerAmount = $totalPrice - $adminCommission;

        $booking = DecorationBooking::create([
            'decoration_id' => $request->decoration_id,
            'customer_id' => auth('customer')->id(),
            'event_date' => $request->event_date,
            'event_time' => $request->event_time,
            'notes' => $request->notes,
            'total_price' => $totalPrice,
            'admin_commission' => $adminCommission,
            'provider_amount' => $providerAmount,
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        return response()->json([
            'message' => 'Decoration booking request sent successfully',
            'pricing' => [
                'total_price' => $totalPrice,
                'admin_commission' => $adminCommission,
                'provider_amount' => $providerAmount
            ],
            'booking' => $booking
        ], 201);
    }

    public function providerDecorationBookings()
    {
        $providerId = auth()->id();

        $bookings = DecorationBooking::with([
            'customer',
            'decoration'
        ])
            ->whereHas('decoration', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function approveDecorationBooking($id)
    {
        $booking = DecorationBooking::findOrFail($id);

        if ($booking->decoration->provider_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking is not pending'
            ], 422);
        }

        $booking->update([
            'status' => 'approved',
            'payment_deadline' => Carbon::now()->addDays(3)
        ]);

        return response()->json([
            'message' => 'Booking approved successfully',
            'booking' => $booking
        ]);
    }

    public function rejectDecorationBooking($id)
    {
        $booking = DecorationBooking::findOrFail($id);

        if ($booking->decoration->provider_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking is not pending'
            ], 422);
        }

        $booking->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Booking rejected successfully',
            'booking' => $booking
        ]);
    }

    public function customerDecorationBookings()
    {
        $bookings = DecorationBooking::with('decoration')
            ->where('customer_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'data' => $bookings
        ]);
    }

    public function confirmDecorationPayment($id)
    {
        $booking = DecorationBooking::findOrFail($id);

        if ($booking->decoration->provider_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->status !== 'approved') {
            return response()->json([
                'message' => 'Booking must be approved first'
            ], 422);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Payment already confirmed'
            ], 422);
        }

        $booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'booking' => $booking
        ]);
    }

    public function cancelDecorationBooking($id)
    {
        $booking = DecorationBooking::findOrFail($id);

        if (auth('sanctum')->check()) {

            $user = auth()->user();

            if ($booking->decoration->provider_id == $user->id) {

                $booking->update([
                    'status' => 'cancelled'
                ]);

                return response()->json([
                    'message' => 'Booking cancelled successfully by provider',
                    'booking' => $booking
                ]);
            }

            if ($booking->customer_id == $user->id) {

                $booking->update([
                    'status' => 'cancelled'
                ]);

                return response()->json([
                    'message' => 'Booking cancelled successfully by customer',
                    'booking' => $booking
                ]);
            }
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    // Public Party Booking
    public function bookPublicParty(Request $request)
    {
        $request->validate([
            'public_party_id' => 'required|exists:public_parties,id',
            'ticket_count' => 'required|integer|min:1'
        ]);

        $party = PublicParty::findOrFail($request->public_party_id);

        if ($party->tickets < $request->ticket_count) {
            return response()->json([
                'message' => 'Not enough tickets available'
            ], 422);
        }

        $totalPrice = $party->price * $request->ticket_count;

        $adminCommission = $totalPrice * 0.20;
        $providerAmount = $totalPrice - $adminCommission;

        $booking = PublicPartyBooking::create([
            'public_party_id' => $party->id,
            'customer_id' => auth('customer')->id(),
            'tickets_count' => $request->ticket_count,
            'total_price' => $totalPrice,
            'admin_commission' => $adminCommission,
            'provider_amount' => $providerAmount,
            'payment_deadline' => now()->addMinute(),
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        $party->decrement('tickets', $request->ticket_count);

        return response()->json([
            'message' => 'Party booking created successfully',
            'pricing' => [
                'total_price' => $totalPrice,
                'admin_commission' => $adminCommission,
                'provider_amount' => $providerAmount
            ],
            'booking' => $booking
        ], 201);
    }

    public function providerPartyBookings()
    {
        $providerId = auth()->id();

        $bookings = PublicPartyBooking::with([
            'customer',
            'publicParty'
        ])
            ->whereHas('publicParty', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function customerPartyBookings()
    {
        $bookings = PublicPartyBooking::with('publicParty')
            ->where('customer_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'data' => $bookings
        ]);
    }

    public function confirmPartyPayment($id)
    {
        $booking = PublicPartyBooking::findOrFail($id);

        if ($booking->publicParty->provider_id != auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Already paid'
            ], 422);
        }

        $booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'booking' => $booking
        ]);
    }

    public function cancelPartyBooking($id)
    {
        $booking = PublicPartyBooking::findOrFail($id);

        if (auth('customer')->check()) {

            if ($booking->customer_id != auth('customer')->id()) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        if (auth('sanctum')->check() && auth()->user()->role === 'provider') {

            if ($booking->publicParty->provider_id != auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'message' => 'Already cancelled'
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        $booking->publicParty->increment(
            'tickets',
            $booking->tickets_count
        );

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }

    public function providerAllBookings()
    {
        $providerId = auth('sanctum')->user()->id;

        $hallBookings = HallBooking::with(['customer', 'hall'])
            ->whereHas('hall', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->get();

        $foodBookings = FoodBooking::with(['customer', 'food'])
            ->whereHas('food', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->get();

        $decorationBookings = DecorationBooking::with(['customer', 'decoration'])
            ->whereHas('decoration', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->get();

        $partyBookings = PublicPartyBooking::with(['customer', 'publicParty'])
            ->whereHas('publicParty', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->get();

        return response()->json([
            'hall_bookings' => $hallBookings,
            'food_bookings' => $foodBookings,
            'decoration_bookings' => $decorationBookings,
            'party_bookings' => $partyBookings,
        ]);
    }

    public function providerBookingsByStatus(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,cancelled,confirmed'
        ]);
        $providerId = auth('sanctum')->user()->id;
        $status = $request->status;

        $hallBookings = HallBooking::with(['customer', 'hall'])
            ->whereHas('hall', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $foodBookings = FoodBooking::with(['customer', 'food'])
            ->whereHas('food', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $decorationBookings = DecorationBooking::with(['customer', 'decoration'])
            ->whereHas('decoration', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $partyBookings = PublicPartyBooking::with(['customer', 'publicParty'])
            ->whereHas('publicParty', function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        return response()->json([
            'hall_bookings' => $hallBookings,
            'food_bookings' => $foodBookings,
            'decoration_bookings' => $decorationBookings,
            'party_bookings' => $partyBookings,
        ]);
    }
}
