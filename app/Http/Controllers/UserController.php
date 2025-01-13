<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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
}
