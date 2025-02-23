<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function updateAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email',
                'avatar' => 'nullable|image',
                'old_password' => 'required|string',
                'password' => 'required|string|confirmed|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    $validator->errors()
                ], 400);
            }

            if (!Hash::check($request->old_password, auth()->user()->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Old password is incorrect!'
                ], 400);
            }

            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $image = $avatar->store('images/avatars', 'public');
            }

            $user = auth()->user();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            if ($request->hasFile('avatar')) {
                $user->avatar = $image;
            }
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully!',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
            ]);
        }
    }

    //get admin profile
    public function getAdminProfile()
    {
        try {
            $user = auth()->user();
            $user = $user->only(['first_name', 'last_name', 'email', 'avatar']);

            $user['avatar'] = $user['avatar'] ? asset('storage/' . $user['avatar']) : null;
            return response()->json([
                'success' => true,
                'message' => 'Admin profile fetched successfully!',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ]);
        }
    }

    //get all reports
    public function getReports()
    {
        try {
            $reports = Blocked::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'avatar');
            }, 'blockedUser' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'avatar',);
            }])
                ->latest()
                ->paginate(10);
                // return $reports;
            if ($reports->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reports found!',
                ], 404);
            }


            return response()->json([
                'success' => true,
                'message' => 'Reports fetched successfully!',
                'reports' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ]);
        }
    }


    //report details
    public function reportDetails($id)
    {
        try {
            $report = Blocked::find($id);
            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found!'
                ], 404);
            }

            //reporter user
            $report->user = $report->user()->select('id', 'first_name', 'last_name', 'email', 'avatar')->first();


            //reported user
            $report->reported_user = $report->blockedUser()->select('id', 'first_name', 'last_name', 'email', 'avatar')->first();



            return response()->json([
                'success' => true,
                'message' => 'Report details fetched successfully!',
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ]);
        }
    }
}
