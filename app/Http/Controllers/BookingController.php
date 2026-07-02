<?php

namespace App\Http\Controllers;

use App\Models\HallAvailability;
use App\Models\HallBooking;
use App\Models\Hall;
use App\Models\FoodBooking;
use App\Models\Food;
use App\Models\Admin;
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

        $customer = auth('customer')->user();

        $hall = Hall::findOrFail($request->hall_id);

        if ($request->guest_count > $hall->CapacityOfPeople) {
            return response()->json(['message' => 'Hall capacity exceeded'], 422);
        }

        // availability check
        $availability = HallAvailability::where('hall_id', $hall->id)
            ->where('date', $request->date)
            ->where('status', 'available')
            ->first();

        if (!$availability) {
            return response()->json(['message' => 'Not available'], 422);
        }

        // price
        $basePrice = $request->booking_type == 'full_day'
            ? $hall->full_day_price
            : (($request->end_time && $request->start_time)
                ? ((strtotime($request->end_time) - strtotime($request->start_time)) / 3600) * $hall->hour_price
                : 0);

        // services
        $servicesPrice = 0;
        $servicesToAttach = [];

        if ($request->services) {
            $services = HallService::whereIn('id', $request->services)
                ->where('hall_id', $hall->id)
                ->get();

            foreach ($services as $service) {
                $servicesPrice += $service->price;
                $servicesToAttach[$service->id] = ['price' => $service->price];
            }
        }

        $totalPrice = $basePrice + $servicesPrice;

        // wallet check
        if (!$customer->wallet || $customer->wallet->balance < $totalPrice) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        DB::beginTransaction();

        try {

            // freeze money
            $customer->wallet->decrement('balance', $totalPrice);
            $customer->wallet->increment('frozen_balance', $totalPrice);

            // create booking
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
                'admin_commission'     => $totalPrice * 0.20,
                'provider_amount'      => $totalPrice * 0.80,
                'status'               => 'pending',
                'payment_status'       => 'holding',
            ]);

            if ($servicesToAttach) {
                $booking->services()->attach($servicesToAttach);
            }

            $availability->update(['status' => 'booked']);

            DB::commit();

            return response()->json([
                'message' => 'Booking created & money frozen',
                'booking' => $booking
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
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
    $booking = HallBooking::with([
        'hall.provider.wallet',
        'customer.wallet'
    ])->findOrFail($id);

    $providerId = auth('sanctum')->id();

    if ($booking->hall->provider_id != $providerId) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    if ($booking->status !== 'pending') {
        return response()->json([
            'message' => 'Invalid booking status'
        ], 422);
    }

    DB::beginTransaction();

    try {

        $customerWallet = $booking->customer->wallet;
        $providerWallet = $booking->hall->provider->wallet;
        $admin = Admin::first();

        if (!$customerWallet) {
            throw new \Exception('Customer wallet not found.');
        }

        if (!$providerWallet) {
            throw new \Exception('Provider wallet not found.');
        }

        if (!$admin || !$admin->wallet) {
            throw new \Exception('Admin wallet not found.');
        }

        $adminWallet = $admin->wallet;

        /*
        |--------------------------------------------------
        | تحويل الأموال
        |--------------------------------------------------
        */

        // إزالة التجميد عن المبلغ
        $customerWallet->decrement(
            'frozen_balance',
            $booking->total_price
        );

        // إضافة حصة المزود
        $providerWallet->increment(
            'balance',
            $booking->provider_amount
        );

        // إضافة عمولة الأدمن
        $adminWallet->increment(
            'balance',
            $booking->admin_commission
        );

        /*
        |--------------------------------------------------
        | تحديث الحجز
        |--------------------------------------------------
        */

        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Booking approved successfully.',
            'booking' => $booking->fresh()
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function rejectHallBooking($id)
    {
        $booking = HallBooking::with(['customer.wallet', 'hall'])->findOrFail($id);

        if ($booking->hall->provider_id !== auth('sanctum')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {

            $wallet = $booking->customer->wallet;

            if ($booking->payment_status === 'holding') {
                $wallet->decrement('frozen_balance', $booking->total_price);
                $wallet->increment('balance', $booking->total_price);
            }

            $booking->update([
                'status' => 'rejected',
                'payment_status' => 'refunded'
            ]);

            $booking->availability()->update(['status' => 'available']);

            DB::commit();

            return response()->json(['message' => 'Booking rejected']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
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
        $booking = HallBooking::with(['customer.wallet'])->findOrFail($id);

        if ($booking->customer_id !== auth('customer')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel'], 422);
        }

        DB::beginTransaction();

        try {

            $wallet = $booking->customer->wallet;

            $wallet->decrement('frozen_balance', $booking->total_price);
            $wallet->increment('balance', $booking->total_price);

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded'
            ]);

            $booking->availability()->update(['status' => 'available']);

            DB::commit();

            return response()->json(['message' => 'Cancelled successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
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
