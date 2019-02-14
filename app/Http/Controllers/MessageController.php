<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\PrivateMessageSent;
use App\Message;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function fetchMessages()
    {
        return Message::with('user')->where('receiver_id', Null)->get();
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        $message = $user->messages()->create([
            'message' => $request->input('message'),
        ]);

        broadcast(new MessageSent($user, $message))->toOthers();

        return response(['status' => 'Message Sent Successfully', 'message' => $message]);
    }

    public function privateMessage(User $user)
    {
        $privateCommunication = Message::with('user')
            ->where(['user_id' => auth()->id(), 'receiver_id' => $user->id])
            ->orWhere(function ($query) use ($user) {
                $query->where(['user_id' => $user->id, 'receiver_id' => auth()->id()]);
            })
            ->get();
        return $privateCommunication;
    }

    public function sendPrivateMessage(Request $request, User $user)
    {
        //echo "hi";exit;
        $input = $request->all();
        $input['receiver_id'] = $user->id;
        $message = auth()->user()->messages()->create($input);

        $user = Auth::user();
        broadcast(new PrivateMessageSent($user, $message->load('user')))->toOthers();
        return response(['status' => 'private Message Sent Successfully', 'message' => $message]);

    }

}
