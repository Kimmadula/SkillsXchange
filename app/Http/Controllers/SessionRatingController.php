<?php

namespace App\Http\Controllers;

use App\Models\SessionRating;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SessionRatingController extends Controller
{
    /**
     * Store a new session rating
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'trade_id' => 'required|exists:trades,id',
                'rated_user_id' => 'required|exists:users,id',
                'session_type' => 'required|in:chat_session,trade_session,skill_sharing',
                'overall_rating' => 'required|integer|min:1|max:5',
                'communication_rating' => 'required|integer|min:1|max:5',
                'helpfulness_rating' => 'required|integer|min:1|max:5',
                'knowledge_rating' => 'required|integer|min:1|max:5',
                'written_feedback' => 'nullable|string|max:1000',
                'session_duration' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Verify the trade exists and user is part of it
            $trade = Trade::find($request->trade_id);
            if (!$trade) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trade not found'
                ], 404);
            }

            // Check if user is part of this trade (either owner or requester)
            $isTradeOwner = $trade->user_id === $user->id;
            $hasAcceptedRequest = $trade->requests()
                ->where('requester_id', $user->id)
                ->where('status', 'accepted')
                ->exists();

            if (!$isTradeOwner && !$hasAcceptedRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to rate this session'
                ], 403);
            }

            // Verify the rated user is the other participant
            $ratedUser = User::find($request->rated_user_id);
            if (!$ratedUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rated user not found'
                ], 404);
            }

            // Ensure user is not rating themselves
            if ($user->id === $ratedUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot rate yourself'
                ], 400);
            }

            // Check if user has already rated this session
            $existingRating = SessionRating::where('rater_id', $user->id)
                ->where('rated_user_id', $ratedUser->id)
                ->where('session_type', $request->session_type)
                ->where('session_id', $request->trade_id)
                ->first();

            if ($existingRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this session'
                ], 400);
            }

            // Create the rating
            $rating = SessionRating::create([
                'session_id' => $request->trade_id,
                'rater_id' => $user->id,
                'rated_user_id' => $ratedUser->id,
                'session_type' => $request->session_type,
                'overall_rating' => $request->overall_rating,
                'communication_rating' => $request->communication_rating,
                'helpfulness_rating' => $request->helpfulness_rating,
                'knowledge_rating' => $request->knowledge_rating,
                'written_feedback' => $request->written_feedback,
                'session_duration' => $request->session_duration,
                'skills_discussed' => $this->getSkillsDiscussed($trade),
            ]);

            // Log the rating creation
            Log::info('Session rating created', [
                'rating_id' => $rating->id,
                'rater_id' => $user->id,
                'rated_user_id' => $ratedUser->id,
                'trade_id' => $request->trade_id,
                'overall_rating' => $request->overall_rating,
                'session_type' => $request->session_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'rating' => [
                    'id' => $rating->id,
                    'overall_rating' => $rating->overall_rating,
                    'communication_rating' => $rating->communication_rating,
                    'helpfulness_rating' => $rating->helpfulness_rating,
                    'knowledge_rating' => $rating->knowledge_rating,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Session rating creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the rating'
            ], 500);
        }
    }

    /**
     * Get skills discussed in the trade
     */
    private function getSkillsDiscussed(Trade $trade)
    {
        $skills = [];
        
        // Add offering skill
        if ($trade->offeringSkill) {
            $skills[] = [
                'skill_id' => $trade->offeringSkill->skill_id,
                'name' => $trade->offeringSkill->name,
                'category' => $trade->offeringSkill->category,
                'type' => 'offering'
            ];
        }
        
        // Add looking skill
        if ($trade->lookingSkill) {
            $skills[] = [
                'skill_id' => $trade->lookingSkill->skill_id,
                'name' => $trade->lookingSkill->name,
                'category' => $trade->lookingSkill->category,
                'type' => 'looking'
            ];
        }
        
        return $skills;
    }

    /**
     * Get ratings for a specific user
     */
    public function getUserRatings($userId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Only allow users to view their own ratings or admin access
            if ($user->id != $userId && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $ratings = SessionRating::where('rated_user_id', $userId)
                ->with(['rater:id,firstname,lastname,username'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'ratings' => $ratings
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user ratings', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ratings'
            ], 500);
        }
    }

    /**
     * Get rating statistics for a user
     */
    public function getUserRatingStats($userId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Only allow users to view their own stats or admin access
            if ($user->id != $userId && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $ratings = SessionRating::where('rated_user_id', $userId)->get();

            if ($ratings->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'stats' => [
                        'total_ratings' => 0,
                        'average_overall' => 0,
                        'average_communication' => 0,
                        'average_helpfulness' => 0,
                        'average_knowledge' => 0,
                    ]
                ]);
            }

            $stats = [
                'total_ratings' => $ratings->count(),
                'average_overall' => round($ratings->avg('overall_rating'), 2),
                'average_communication' => round($ratings->avg('communication_rating'), 2),
                'average_helpfulness' => round($ratings->avg('helpfulness_rating'), 2),
                'average_knowledge' => round($ratings->avg('knowledge_rating'), 2),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user rating stats', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rating statistics'
            ], 500);
        }
    }
}
