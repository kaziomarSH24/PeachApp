<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TremAndCondition;
use Illuminate\Http\Request;

class TremsAndConditionController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Terms and Conditions fetched successfully',
            'data' => [
                [
                    'title' => 'What is the purpose of this app?',
                    'description' => 'This app is a dating app that helps you find your perfect match.'
                ],
                [
                    'title' => 'How do I find my perfect match?',
                    'description' => 'You can find your perfect match by swiping right on the profiles you like.'
                ],
                [
                    'title' => 'How do I block a user?',
                    'description' => 'You can block a user by going to their profile and clicking on the block button.'
                ],
                [
                    'title' => 'How do I report a user?',
                    'description' => 'You can report a user by going to their profile and clicking on the report button.'
                ],
                [
                    'title' => 'How do I change my password?',
                    'description' => 'You can change your password by going to the settings page and clicking on the change password button.'
                ],
                [
                    'title' => 'How do I update my profile?',
                    'description' => 'You can update your profile by going to the settings page and clicking on the update profile button.'
                ],
                [
                    'title' => 'How do I delete my account?',
                    'description' => 'You can delete your account by going to the settings page and clicking on the delete account button.'
                ],
            ]
        ]);
    }

    public function termsAndConditions(Request $request)
    {
        try {
            $termsAndConditions = TremAndCondition::findOrFail(1);
            return response()->json([
                'status' => 'success',
                'termsAndConditions' => $termsAndConditions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Terms and Conditions',
                'error' => $e->getMessage()
            ]);
        }
    }

    //store and update terms and conditions
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);

        try {
            $termsAndConditions = TremAndCondition::first();
            if ($termsAndConditions) {
                $termsAndConditions->content = $request->content;
                $termsAndConditions->save();
            } else {
                TremAndCondition::create([
                    'content' => $request->content
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Terms and Conditions saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save Terms and Conditions',
                'error' => $e->getMessage()
            ]);
        }
    }
}
