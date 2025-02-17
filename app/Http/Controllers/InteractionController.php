<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Matching;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InteractionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InteractionController extends Controller
{
    public function handleInteraction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matched_user_id' => 'required|exists:users,id',
            'status' => 'required|in:like,dislike,star',
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }

        $currentUserId = auth()->id();
        $likedUserId = $request->matched_user_id;


        $existingInteraction = Matching::where('user_id', $currentUserId)
            ->where('matched_user_id', $likedUserId)
            ->first();

        if ($existingInteraction) {

            $existingInteraction->update(['status' => $request->status]);
            $this->sendMatchNotification($currentUserId, $likedUserId, $request->status);
        } else {

            $existingInteraction = Matching::create([
                'user_id' => $currentUserId,
                'matched_user_id' => $likedUserId,
                'status' => $request->status,
            ]);

            $this->sendMatchNotification($currentUserId, $likedUserId, $request->status);
        }


        if ($request->status === 'like') {
            $reciprocal = Matching::where('user_id', $likedUserId)
                ->where('matched_user_id', $currentUserId)
                ->where('status', 'like')
                ->first();

            if ($reciprocal) {

                $existingInteraction->update(['status' => 'matched']);
                $reciprocal->update(['status' => 'matched']);


                $this->sendMatchNotification($currentUserId, $likedUserId, 'matched');
            }
        }
        return response()->json([
            'success' => true,
            'data' => $existingInteraction
        ]);
    }

    private function sendMatchNotification($userId, $matchedUserId, $status)
    {
        $user = User::find($userId);
        $matchedUser = User::find($matchedUserId);
        $userPreferences = Setting::where('user_id', $matchedUserId)->first();
        if(!$userPreferences){
            $userPreferences['is_app_notify'] = 1;
            $userPreferences['is_email_notify'] = 0;
            $userPreferences['is_push_notify'] = 0;
            $userPreferences = (object) $userPreferences;
        }

        // dd($userPreferences->is_app_notify);

        if ($status === 'matched') {
            // Notify both users
            $user->notify(new InteractionNotification($matchedUser, $status, $userPreferences));
            $matchedUser->notify(new InteractionNotification($user, $status, $userPreferences));
        } else {
            // Notify the user
            $user->notify(new InteractionNotification($matchedUser, $status, $userPreferences));
        }
    }

    public function getMatches(Request $request)
    {
        $userId = auth()->id();

        $conversationUserIds = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->pluck('user_one_id', 'user_two_id')
            ->toArray();
        $conversationUserIds = array_unique(array_merge(array_keys($conversationUserIds), array_values($conversationUserIds)));
        sort($conversationUserIds);


        $matches = Matching::where('user_id', $userId)
            ->where('status', 'matched')
            ->whereHas('matchedUser', function ($query) use ($conversationUserIds) {
                $query->whereNotIn('matched_user_id', $conversationUserIds);
            })
            ->with([
                'matchedUser' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'avatar', 'is_active');
                }
            ])
            ->latest()
            ->paginate($request->per_page ?? 10);

        $matches->getCollection()->transform(function ($match) {
            $matchedUser = $match->matchedUser;
            $matchedUser->avatar = asset('storage/' . $matchedUser->avatar);
            return $matchedUser;
        });

        // Return response
        return response()->json([
            'success' => true,
            'matches' => $matches
        ]);
    }
}
