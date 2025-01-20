<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FAQController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'FAQs fetched successfully',
            'data' => [
                [
                    'question' => 'What is the purpose of this app?',
                    'answer' => 'This app is a dating app that helps you find your perfect match.'
                ],
                [
                    'question' => 'How do I find my perfect match?',
                    'answer' => 'You can find your perfect match by swiping right on the profiles you like.'
                ],
                [
                    'question' => 'How do I block a user?',
                    'answer' => 'You can block a user by going to their profile and clicking on the block button.'
                ],
                [
                    'question' => 'How do I report a user?',
                    'answer' => 'You can report a user by going to their profile and clicking on the report button.'
                ],
                [
                    'question' => 'How do I change my password?',
                    'answer' => 'You can change your password by going to the settings page and clicking on the change password button.'
                ],
                [
                    'question' => 'How do I update my profile?',
                    'answer' => 'You can update your profile by going to the settings page and clicking on the update profile button.'
                ],
                [
                    'question' => 'How do I delete my account?',
                    'answer' => 'You can delete your account by going to the settings page and clicking on the delete account button.'
                ],
            ]
        ]);
    }



   //get all faqs
    public function faqs(Request $request)
    {
        try {
            $faqs = Faq::paginate($per_page = 10);
            return response()->json([
                'status' => 'success',
                'message' => 'FAQs fetched successfully',
                'faqs' => $faqs
            ]);
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the FAQs',
            ]);
    }
}

    public function store(Request $request)
    {
       try {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);
        if($validator->fails()) {
            return response()->json($validator->errors());
        }

        $faq = new Faq();
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();


        return response()->json([
            'status' => 'success',
            'message' => 'FAQ added successfully',
            'data' => $faq
        ]);
       } catch (\Exception $e) {
           return response()->json([
               'status' => 'error',
               'message' => 'An error occurred while adding the FAQ',
           ]);
       }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question' => 'required|string',
                'answer' => 'required|string',
            ]);
            if($validator->fails()) {
                return response()->json($validator->errors());
            }

            $faq = Faq::find($id);
            if(!$faq) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ not found',
                ]);
            }

            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->save();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ updated successfully',
                'data' => $faq
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the FAQ',
            ]);
        }
    }

    public function delete($id)
    {
        try {
            $faq = Faq::find($id);
            if(!$faq) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ not found',
                ]);
            }

            $faq->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the FAQ',
            ]);
        }
    }
}
