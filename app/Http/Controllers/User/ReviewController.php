<?php

namespace App\Http\Controllers\User;

use App\Models\Review;
use App\Models\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::with('basecamp')
        ->where('id', $request->booking_id)
        ->where('user_id', auth()->id())
        ->where('status', 'completed')
        ->firstOrFail();

        if ($booking->review) {
            return response()->json([
                'message' => 'Booking ini sudah direview sebelumnya'
            ], 400);
        }

        $review = Review::create([
            'user_id' => auth()->id(),
            'booking_id' => $request->booking_id,
            'gunung_id' => $request->gunung_id,
            'basecamp_id' => $request->basecamp_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review berhasil dibuat',
            'data' => $review
        ]);
    }

    public function gunungReviews($gunungId)
    {
        $reviews = Review::where('gunung_id', $gunungId)
        ->with('user:id,name,foto')
        ->latest()
        ->paginate(10);

        return response()->json([
            'message' => 'Reviews for Gunung',
            'data' => $reviews
        ]);
    }
}
