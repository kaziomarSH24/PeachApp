<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function settings(Request $request)
    {
        $validator = Validator::make($request->all
        (), [
            'is_push_notify' => 'nullable|boolean',
            'is_email_notify' => 'nullable|boolean',
            'is_app_notify' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }

        $user = auth()->user();

        $settings = $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            $validator->validated()
        );

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);

    }

    public function getSettings()
    {
        $user = auth()->user();

        $settings = $user->settings;

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }



    //update avatar
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }

        $user = auth()->user();

        $avatar = $request->file('avatar');
        if($avatar) {
            //remove old avatar if exists
            if (!empty($user->avatar)) {
                if(Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete( $user->avatar);
                }
            }

          $image = $avatar->store('images/avatars', 'public');
           $user->avatar = $image;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'avatar' => asset('storage/'.$user->avatar)
        ]);
    }


    //update password
    public function updatePassword(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|confirmed|min:8',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            // return $request->all();

            $user_id = auth()->id();
            $user = User::find($user_id);
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!',
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
            ]);
        }
    }
}
