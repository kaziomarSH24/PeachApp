<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        // return "Hello";
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->get();

        $notifications->transform(function ($notification) {
            //create_at format
            $notification->created_at_formatted = $notification->created_at->format('d/m/Y H:i:s A');
            return $notification;
        });

        return $this->sendResponse($notifications, 'Notifications retrieved successfully.');
    }

    private function sendResponse($result, $message)
    {
        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ]);
    }

    private function sendError($error, $errorMessages = [], $code = 404)
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'data'    => $errorMessages,
        ], $code);
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }
        $user = auth()->user();
        $notification = $user->notifications()->find($request->notification_id);
        if ($notification) {
            $notification->markAsRead();
            return $this->sendResponse(null, 'Notification marked as read.');
        }
        return $this->sendError('Notification not found.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        return $this->sendResponse([], 'All notifications marked as read.');
    }
    public function deleteNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors());
        }
        $user = auth()->user();
        $notification = $user->notifications()->find($request->notification_id);
        if ($notification) {
            $notification->delete();
            return $this->sendResponse([], 'Notification deleted successfully.');
        }
        return $this->sendError('Notification not found.');
    }

    public function saveToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors());
        }
        $user = auth()->user();
        $user->update(['fcm_token' => $request->fcm_token]);
        return $this->sendResponse($user,'FCM token saved successfully.');
    }
}
