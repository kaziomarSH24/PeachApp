<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function storeUserInfo(Request $request)
    {
        $user = auth()->user();
        //check status
        if ($user->status == 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked. Please contact admin.'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dob' => 'required|date',
            'is_notify' => 'nullable|boolean',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'max_distance' => 'nullable|integer',
            'age_range' => 'nullable|json',
            'gender' => 'nullable|json',
            'dating_with' => 'nullable|string',
            'height' => 'nullable|string',
            'passions' => 'nullable|array',
            'passions.*' => 'string',
            'interests' => 'nullable|array',
            'interests.*' => 'string',
            'ethnicity' => 'nullable|json',
            'have_children' => 'nullable|json',
            'home_town' => 'nullable|json',
            'work_place' => 'nullable|json',
            'job' => 'nullable|json',
            'school' => 'nullable|json',
            'edu_lvl' => 'nullable|json',
            'religion' => 'nullable|json',
            'drink' => 'nullable|json',
            'smoke' => 'nullable|json',
            'smoke_weed' => 'nullable|json',
            'drugs' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }


        $user->update($validator->validated());

        $user->avatar = $user->avatar ? url($user->avatar) : null;
        // $user->gender = json_decode($user->gender);

        return response()->json([
            'success' => true,
            'message' => 'User info updated successfully',
            'data' => $user
        ], 200);
    }

    //get user profile info
    public function getUserInfo()
    {
        $user = User::with('profile')->find(auth()->id());
        //check status
        if ($user->status == 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked. Please contact admin.'
            ], 403);
        }
        $profileImg = $user->profile ? json_decode($user->profile->images) : [];
        if ($user->profile) {
            $user->profile->prompt = json_decode($user->profile->prompt);
        }
        $user->name = $user->first_name . ' ' . $user->last_name;
        $user->avatar = asset('storage/' . $user->avatar);
        $user->gender = json_decode($user->gender);
        $user->age_range = json_decode($user->age_range);
        $user->passions = json_decode($user->passions);
        $user->interests = json_decode($user->interests);
        $user->ethnicity = json_decode($user->ethnicity);
        $user->have_children = json_decode($user->have_children);
        $user->home_town = json_decode($user->home_town);
        $user->work_place = json_decode($user->work_place);
        $user->job = json_decode($user->job);
        $user->school = json_decode($user->school);
        $user->edu_lvl = json_decode($user->edu_lvl);
        $user->religion = json_decode($user->religion);
        $user->drink = json_decode($user->drink);
        $user->smoke = json_decode($user->smoke);
        $user->smoke_weed = json_decode($user->smoke_weed);
        $user->drugs = json_decode($user->drugs);

        //add url in profile images
        $imagesUrl = [];
        foreach ($profileImg as $image) {
            $imagesUrl[] = asset('storage/' . $image);
        }
        if($user->profile){
            $user->profile->images = $imagesUrl;
        }


        return response()->json([
            'success' => true,
            'message' => 'User info',
            'data' => $user
        ], 200);
    }

    /**
     * Profile images and prompt
     */
    //get profile images and prompt
    public function getProfile()
    {
        $user_id = auth()->id();
        $profile = Profile::where('user_id', $user_id)->latest()->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->images = json_decode($profile->images);
        //add image full url
        $imagesUrl = [];
        foreach ($profile->images as $image) {
            $imagesUrl[] = asset('storage/' . $image);
        }
        $profile->images = $imagesUrl;

        $profile->prompt = json_decode($profile->prompt);

        return response()->json([
            'success' => true,
            'message' => 'Profile images and prompt',
            'data' => $profile
        ], 200);
    }

    //store profile images and prompts
    public function storeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:4|max:6',
            'images.*' => 'image',
            'prompt' => 'nullable|array',
            'prompt.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }

        //images handling
        $imagesFile = $request->file('images');
        $images = [];
        foreach ($imagesFile as $image) {
            $path = $image->store('images/profile', 'public');
            $images[] = $path;
        }

        //store avatar in user table
        $user = auth()->user();
        if ($user->avatar == null) {
            $avatarImg = $imagesFile[0];
            $user->avatar = $avatarImg->store('images/avatars', 'public');
            $user->save();
        }

        //store images and prompt in profile table
        $profile = new Profile();
        $profile->user_id = auth()->id();
        $profile->images = json_encode($images);
        $profile->prompt = json_encode($request->prompt);
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile images and prompt saved successfully',
            'data' => $profile
        ], 200);
    }

    //update profile images and prompt
    public function updateProfile(Request $request)
    {
        $user_id = auth()->id();
        $validator = Validator::make($request->all(), [
            'images' => 'nullable|array',
            'images.*' => 'image',
            'prompt' => 'nullable|array',
            'prompt.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 400);
        }
        $profile = Profile::where('user_id', $user_id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $imagesFile = $request->file('images');
        if ($imagesFile) {
            //remove old images
            if ($profile && !empty($profile->images)) {
                $oldImages = json_decode($profile->images, true);
                foreach ($oldImages as $oldImage) {
                    if (Storage::disk('public')->exists($oldImage)) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }
            }

            //images handling

            $images = [];
            foreach ($imagesFile as $image) {
                $path = $image->store('images/profile', 'public');
                $images[] = $path;
            }
            $profile->images = json_encode($images);
        }

        //store images and prompt in profile table

        $profile->prompt = $request->prompt ? json_encode($request->prompt) : $profile->prompt;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile images and prompt updated successfully',
            'data' => $profile
        ], 200);
    }

    //delete profile images and prompt
    public function deleteProfile(Request $request)
    {
        $user_id = auth()->id();
        $profile = Profile::where('user_id', $user_id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        //remove old images
        if (!empty($profile->images)) {
            $oldImages = json_decode($profile->images, true);
            foreach ($oldImages as $oldImage) {
                if (Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
        }

        $profile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profile images and prompt deleted successfully'
        ], 200);
    }
}
