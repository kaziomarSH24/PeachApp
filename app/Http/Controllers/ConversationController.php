<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{

    /**
     * Get all Conversations for the Authenticated User.
     */
    public function getContact()
    {
        $conversations = Conversation::where('user_one_id', auth()->id())
            ->orWhere('user_two_id', auth()->id())
            ->with([
                'userOne' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'avatar', 'is_active');
                },
                'userTwo' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'avatar', 'is_active');
                },
                'messages' => function ($query) {
                    $query->latest()->first();
                }
            ])
            ->paginate(10);

        if ($conversations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No conversations found.',
            ], 404);
        }

       $conversations->transform(function ($conversation) {
            $conversation->user = $conversation->userOne->id === auth()->id() ? $conversation->userTwo : $conversation->userOne;
            $conversation->unread_messages = $conversation->getUnreadMessagesCount(auth()->id());
            $conversation->latest_message = $conversation->getLatestMessage();
            $mediaType = $conversation->latest_message->media_type;
            $firstName = $conversation->user->first_name;
            $lastName = $conversation->user->last_name;

            $lastMessageDate = Carbon::parse($conversation->latest_message->created_at);
            return [
                'id' => $conversation->id,
                'user_id' => auth()->id(),
                'receiver_id' => $conversation->userOne->id === auth()->id() ? $conversation->userTwo->id : $conversation->userOne->id,
                'name' => $firstName . ' ' . $lastName,
                'avatar' => asset('storage/'. $conversation->user->avatar),
                'is_active' => $conversation->user->is_active,
                'unread_messages' => $conversation->unread_messages,
                'latest_message' => [
                    'message' => $mediaType == 'text' ? $conversation->latest_message->message : ($mediaType == 'image' ? (auth()->id() == $conversation->latest_message->sender_id ? "You sent a photo" : $firstName . " sent a photo") : ($mediaType == 'video' ? (auth()->id() == $conversation->latest_message->sender_id ? "You sent a video" : $firstName . " sent a video") : ($mediaType == 'audio' ? (auth()->id() == $conversation->latest_message->sender_id ? "You sent an audio" : $firstName . " sent an audio") : $conversation->latest_message->message))),
                    'media' => $conversation->latest_message->media ? asset('storage/'. $conversation->latest_message->media) : null,
                    'media_type' => $conversation->latest_message->media_type,
                    'created_at' => $lastMessageDate->diffInDays(Carbon::now()) < 1 ? $lastMessageDate->format('h:i A') : ($lastMessageDate->diffInDays(Carbon::now()) < 7 ? $lastMessageDate->format('l') : $lastMessageDate->format('d M Y')),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'conversation' => $conversations,
        ]);
    }




    /**
     * Send a Message.
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable',
            // 'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mp3,wav,ogg,avi,flv,webm|max:20480',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mp3,wav,ogg,avi,flv,webm,pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            // 'media_type' => 'nullable|in:text,image,video,audio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $sender = auth()->id();
        $receiver = $request->receiver_id;

        if($sender == $receiver) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a message to yourself.',
            ], 400);
        }

        // Check if a conversation already exists
        $conversation = Conversation::where(function ($query) use ($sender, $receiver) {
            $query->where('user_one_id', $sender)
                ->where('user_two_id', $receiver);
        })->orWhere(function ($query) use ($sender, $receiver) {
            $query->where('user_one_id', $receiver)
                ->where('user_two_id', $sender);
        })->first();

        // Create a conversation if it doesn't exist
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $sender,
                'user_two_id' => $receiver,
            ]);
        }

        // Handle media upload (if applicable)
        $mediaPath = null;
        if ($request->hasFile('media')) {

            $file = $request->file('media');
            $extension = $file->getClientOriginalExtension();
            $mediaType = 'text';
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $mediaType = 'image';
            } elseif (in_array($extension, ['mp4', 'avi', 'flv', 'webm'])) {
                $mediaType = 'video';
            } elseif (in_array($extension, ['mp3', 'wav', 'ogg'])) {
                $mediaType = 'audio';
            }elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                $mediaType = 'document';
            }
            $mediaPath = $request->file('media')->store('messages/media', 'public');
        }
        $mediaUrl = $mediaPath ? asset('storage/'. $mediaPath) : null;
        // Save the message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'message' => $request->message ?? null,
            'media' => $mediaPath,
            'media_type' => $mediaType ?? 'text',
            'status' => 'sent',
        ]);

        $message->media = $mediaUrl;
        $message->created_at_formatted = Carbon::parse($message->created_at)->format('H:i');
        $message->created_at_date = Carbon::parse($message->created_at)->format('d M Y');


        //notification data;
        $data = [
            'message' => $message->sender->first_name . ' sent you a'. ($message->media_type == 'text' ? ' message' : ($message->media_type == 'image' ? ' photo' : ($message->media_type == 'video' ? ' video' : ($message->media_type == 'audio' ? ' audio' : ' document')))),
            'avatar' => $message->sender->avatar ? asset('storage/'. $message->sender->avatar) : null,
            'conversation_id' => $conversation->id,
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'created_at' => $message->created_at,
        ];

        $message->makehidden('sender');
        // Notify the receiver (if applicable)
        $receiverUser = User::find($receiver);
        $userPreferences = $receiverUser->settings;
        if(!$userPreferences){
            $userPreferences['is_app_notify'] = 1;
            $userPreferences['is_email_notify'] = 0;
            $userPreferences['is_push_notify'] = 0;
            $userPreferences = (object) $userPreferences;
        }

        $receiverUser->notify(new \App\Notifications\MessageNotification($data , $userPreferences));

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $message,
        ]);
    }

    /**
     * Mark a Message as Read.
     */
    public function markAsRead(Request $request)
    {
        $conversation = Conversation::findOrFail($request->conversation_id);

        $conversation->markMessagesAsRead(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
        ]);
    }



    /**
     * Get all Messages in a Conversation.
     */
    public function getMessages(Request $request, $conversationId)
    {
        $perPage = $request->per_page ?? 10;
        $conversation = Conversation::findOrFail($conversationId);

        $messages = $conversation->getMessages($perPage);

        $messages->transform(function ($message) {

            $message->is_sender = $message->sender_id == auth()->id();
            $message->media = $message->media ? asset('storage/'. $message->media) : null;
            $message->created_at_formatted = Carbon::parse($message->created_at)->format('H:i');
            $message->created_at_date = Carbon::parse($message->created_at)->format('d M Y');
            return $message;
        });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }
}
