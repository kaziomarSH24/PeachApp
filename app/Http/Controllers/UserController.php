<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function storeUserInfo(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dob' => 'required|date',
            'is_notify' => 'nullable|boolean',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'gender' => 'nullable|json',
            'dating_with' => 'nullable|string',
            'height' => 'nullable|integer',
            'passions' => 'nullable|array',
            'passions.*' => 'string',
            'ethinicity' => 'nullable|json',
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

        $user = auth()->user();
        $user->update($validator->validated());

        $user->avatar = $user->avatar ? url($user->avatar) : null;
        // $user->gender = json_decode($user->gender);

        return response()->json([
            'success' => true,
            'message' => 'User info updated successfully',
            'data' => $user
        ], 200);
    }

    /**
     * Profile images and prompt
     */
    //get profile images and prompt
    public function getProfile()
    {
        // $user_id = auth()->id();
        $user_id = 1;
        $profile = Profile::where('user_id', $user_id)->first();

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

        //store images and prompt in profile table
        $profile = new Profile();
        $profile->user_id = 1;
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
        $user_id = 1; //auth()->id();
        $validator = Validator::make($request->all(), [
            'images' => 'nullable|array|min:4|max:6',
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

        $imagesFile = $request->file('images');
        if($imagesFile){
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
    // public function deleteProfile(Request $request)
    // {
    //     $user_id = 1; //auth()->id();
    //     $profile = Profile::where('user_id', $user_id)->first();

    //     if (!$profile) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Profile not found'
    //         ], 404);
    //     }

    //     //remove old images
    //     if (!empty($profile->images)) {
    //         $oldImages = json_decode($profile->images, true);
    //         foreach ($oldImages as $oldImage) {
    //             if (Storage::disk('public')->exists($oldImage)) {
    //                 Storage::disk('public')->delete($oldImage);
    //             }
    //         }
    //     }

    //     $profile->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Profile images and prompt deleted successfully'
    //     ], 200);
    // }
}
