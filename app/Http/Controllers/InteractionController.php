<?php

namespace App\Http\Controllers;

use App\Models\Matching;
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
        if ($status === 'matched') {
            // Notify both users
            $user->notify(new InteractionNotification($matchedUser, $status));
            $matchedUser->notify(new InteractionNotification($user, $status));
        } else {
            // Notify the user
            $user->notify(new InteractionNotification($matchedUser, $status));
        }
    }

    public function getMatches()
{
    $userId = auth()->id();

    $matches = Matching::where('user_id', $userId)
        ->where('status', 'matched')
        ->with([
            'matchedUser.profile' => function ($query) {
                $query->select('id', 'user_id', 'images');
            },
            'matchedUser' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'avatar');
            }
        ])
        ->paginate(10);

    $matches->getCollection()->transform(function ($match) {
        $matchedUser = $match->matchedUser;

        $profile = $matchedUser->profile ?? null;

        if($profile){
            $avatar = $profile->getAvatar();
            $matchedUser->setAttribute('avatar', $avatar);
        }
        $matchedUser->makeHidden('profile');

        return [
            'matched_user' => $matchedUser,
        ];
    });

    // Return response
    return response()->json([
        'success' => true,
        'data' => $matches
    ]);
}

}
