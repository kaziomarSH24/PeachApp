<?php

namespace App\Http\Controllers;

use App\Models\Blocked;
use App\Models\User;
use App\Notifications\UserReportNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Termwind\Components\Raw;

class BlockedController extends Controller
{
    public function blockedUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'blocked_user_id' => 'required|exists:users,id',
                'reason' => 'nullable|string'
            ]);
            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            $blockedUser = $user->blockedUsers()->updateOrCreate(
                ['user_id' => $user->id],
                $validator->validated()
            );

            if($request->reason != null){
                //then send notification to the admin

                $admin = User::where('role', 'admin')->first();
                $userPreferences = $admin->settings()->first();
                if(!$userPreferences){
                    $userPreferences['is_app_notify'] = 1;
                    $userPreferences['is_email_notify'] = 0;
                    $userPreferences['is_push_notify'] = 0;
                    $userPreferences = (object) $userPreferences;
                }
                $blockedUser = User::find($request->blocked_user_id);

                $data = [
                    'id' => $blockedUser->id,
                    'user_id' => auth()->id(),
                    'block_user_id' => $request->blocked_user_id,
                    'message' => $user->first_name . ' has reported ' . $blockedUser->first_name,
                    'reason' => $request->reason,
                ];

                $admin->notify(new UserReportNotification($data,$userPreferences ));

            }

            return response()->json([
                'success' => true,
                'blocked_user' => $blockedUser
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function unBlocked(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'blocked_user_id' => 'required|exists:users,id',
            ]);
            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            $blockedUser = Blocked::where('user_id', $user->id)
                ->where('blocked_user_id', $request->blocked_user_id)
                ->first();
           if(!$blockedUser){
               return response()->json([
                   'success' => false,
                   'message' => 'User not found in blocked list'
               ], 404);
              }
              $blockedUser->delete();

            return response()->json([
                'success' => true,
                'blocked_user' => $blockedUser
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //get blocked users
    public function getBlockedUsers(Request $request)
    {
        $user = auth()->user();
        $blockedUsers = $user->blockedUsers()->with('user', function ($query) {
                            $query->select('id', 'first_name','last_name','avatar');
                        })
                        ->paginate($request->per_page ?? 10);
        if ($blockedUsers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No blocked users found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'blocked_users' => $blockedUsers
        ]);
    }
}
