<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\GetMessageRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Message;
use App\Models\Otp;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;

class AuthController extends Controller
{
    public function register(RegisterRequest $request){

        $data = $request->validated();
         if (isset($data['profile_img'])) {
            $file = $data['profile_img'];
            $path = $file->store('profile_images', 'public');
            $path = Str::replace('profile_images/' , '' , $path);
            $data['profile_img'] = $path;
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'profile_img' => $data['profile_img']
        ]);
        if($user)
        return response()->json([
            'success' => true ,
            'message' => 'User Registered Successfully !!' , 
            'user' => $user
        ]);
        return response()->json([
            'success' => false ,
            'error' => 'Registraition failed !!'
        ]);

    }

    public function login(LoginRequest $request){
        $data = $request->validated();
        if(Auth::attempt(['email' => $data['email'] , 'password' => $data['password']])){
            if(Auth::check()){
                $user = Auth::user();
                $token = $user->createToken('token')->plainTextToken;
                return response()->json([
                    'success' => true ,
                    'message' => 'User logged in Successfully !!' ,
                    'user' => $user ,
                    'token' => $token
                ]);
            }
        }
        return response()->json([
            'success' => false ,
            'error' => 'Login failed !!'
        ]);
    }

    public function logout(Request $request){
        $user = $request->user();
        $user->tokens()->delete();
        return response()->json([
            'success' => 'true' ,
            'message' => 'User Logged out Successfully !!'
        ]);
    }

    public function changePassword(ChangePasswordRequest $request){
        $user = $request->user();
        $data = $request->validated();
        if(!Hash::check($data['oldPassword'] , $user->password )){
            return response()->json([
                'success' => false ,
                'message' => "Enter valid Old Password !"
            ]) ;  
        }
        $user->password = Hash::make($data['newPassword']);
        $user->save();
        return response()->json([
            'success' =>true ,
            'message' => 'Password changed successfully !!'
        ]);
    }

     public function forgotPassword(ForgotPasswordRequest $request){
         $data = $request->validated();
         try{
            $user = User::where('email' , $data['email'])->first();
            if(!$user){
                return response()->json([
                    'success' => false ,
                    'message' => 'Email is not registered !!'
                ]);
            }
            $otp = random_int(100000 , 999999);
            Otp::updateOrCreate(                
            [
                'user_id' => $user->id 
            ],
            [
                'user_id' => $user->id ,
                'otp' => $otp
            ]);
            return response()->json([
                'success' => true ,
                'message' => 'Otp sent successfully !!',
                'otp' => $otp
            ]);



         }catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json([
                'success' => false ,
                'message' => 'Something went wrong while sending OTP !!!'
            ]);
         }
     }

     public function verifyOtp(VerifyOtpRequest $request){
         try{
            $data = $request->validated();
            $otp = $data['otp'] ;
            $userID = User::where('email' , $data['email'])->first()->id;
            $dbOtp = Otp::where('user_id' , $userID)->first()->otp;
            if($otp != $dbOtp){
                return response()->json([
                    'success' =>false , 
                    'message' => 'Invalid OTP !!'
                ]);
            }

            $user = User::findOrFail($userID);
            $newPassword = random_int(100000 , 999999);
            $user->password = Hash::make($newPassword);
            $user->save();
            Otp::where('user_id' , $userID)->first()->delete();

            return response()->json([
                'success' => true ,
                'message' => 'Password sent successfully !!',
                'password' => $newPassword
            ]);

        }catch(Exception $e){
             Log::error($e->getMessage());
            return response()->json([
                'success' => false ,
                'message' => 'Something went wrong while generating your password !!!'
            ]);
        }
     }

    public function getAllUsers()
{
    $authId = Auth::id();

    $users = User::where('id', '!=', $authId)
        ->withCount([
            'messagesReceived as unread_count' => function ($q) use ($authId) {
                $q->where('receiver_id', $authId)
                  ->where('seen', 0);
            }
        ])
        ->get()
        ->map(function ($user) use ($authId) {

            //  LAST MESSAGE
            $lastMessage = Message::where(function ($q) use ($user, $authId) {
                $q->where('sender_id', $authId)
                  ->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($user, $authId) {
                $q->where('sender_id', $user->id)
                  ->where('receiver_id', $authId);
            })
            ->latest()
            ->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                //  FIXED IMAGE
                'image' => $user->profile_img 
                    ? asset('storage/profile_images/'.$user->profile_img)
                    : null,

                //  LAST MESSAGE
                'last_message' => $lastMessage->chat_messages ?? 'Say Hi ',
                'last_message_time' => $lastMessage->created_at ?? null,

                //  UNREAD COUNT
                'unread_count' => $user->unread_count,
            ];
        });

    return response()->json([
        'success' => true ,
        'message' => 'All users fetched successfully !!' , 
        'users' => $users
    ]);
}


//     public function getMessagesApi(Request $request)
// {
//     $request->validate([
//         'user_id' => 'required|exists:users,id'
//     ]);

//     $authId = Auth::id();
//     $userId = $request->user_id;

//     //  GET ALL MESSAGES
//     $messages = Message::withTrashed()
//         ->with('sender')
//         ->where(function ($q) use ($authId, $userId) {
//             $q->where('sender_id', $authId)
//               ->where('receiver_id', $userId);
//         })
//         ->orWhere(function ($q) use ($authId, $userId) {
//             $q->where('sender_id', $userId)
//               ->where('receiver_id', $authId);
//         })
//         ->orderBy('created_at', 'asc')
//         ->get();

//     //  FORMAT RESPONSE
//     $messages->transform(function ($msg) use ($authId) {

//         //  IMAGE FIX
//         $msg->sender_image = $msg->sender && $msg->sender->profile_img
//             ? asset('storage/profile_images/'.$msg->sender->profile_img)
//             : null;

//         //  DELETE HANDLING
//         if ($msg->deleted_at) {
//             $msg->chat_messages = $msg->sender_id == $authId
//                 ? '<i>Deleted by you</i>'
//                 : '<i>Deleted by author</i>';
//         }

//         return [
//             'id' => $msg->id,
//             'chat_messages' => $msg->chat_messages,
//             'sender_id' => $msg->sender_id,
//             'receiver_id' => $msg->receiver_id,
//             'sender_image' => $msg->sender_image,
//             'seen' => $msg->seen,
//             'delivered' => $msg->delivered,
//             'created_at' => $msg->created_at,
//         ];
//     });

//     //  UNREAD COUNT
//     $unread = Message::where('sender_id', $userId)
//         ->where('receiver_id', $authId)
//         ->where('seen', 0)
//         ->count();

//     return response()->json([
//         'messages' => $messages,
//         'unread_count' => $unread
//     ]);
// }
public function getMessagesApi(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id'
    ]);

    $authId = Auth::id();
    $userId = $request->user_id;

    $messages = Message::withTrashed()
        ->with('sender')    
        ->where(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)
              ->where('receiver_id', $userId);
        })   
        ->orWhere(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $userId)
              ->where('receiver_id', $authId);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20); 

    $messages->getCollection()->transform(function ($msg) use ($authId) {

        $senderImage = $msg->sender && $msg->sender->profile_img
            ? asset('storage/profile_images/'.$msg->sender->profile_img)
            : null;

        
        $fileUrl = $msg->file_path
            ? asset('storage/'.$msg->file_path)
            : null;

        if ($msg->deleted_at) {
            return [
                'id' => $msg->id,
                'chat_messages' => $msg->sender_id == $authId
                    ? '<i>Deleted by you</i>'
                    : '<i>Deleted by author</i>',

                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,

                
                'file_type' => null,
                'file_url' => null,

                'sender_image' => $senderImage,
                'seen' => $msg->seen,
                'delivered' => $msg->delivered,
                'created_at' => $msg->created_at,

                'is_deleted' => true,
            ];
        }

        //  NORMAL MESSAGE
        return [
            'id' => $msg->id,
            'chat_messages' => $msg->chat_messages,
            'sender_id' => $msg->sender_id,
            'receiver_id' => $msg->receiver_id,

            'file_type' => $msg->file_type,
            'file_url' => $fileUrl,
 
            'sender_image' => $senderImage,
            'seen' => $msg->seen,
            'delivered' => $msg->delivered,
            'created_at' => $msg->created_at,

            'is_deleted' => false,
        ];
    });

    //  UNREAD COUNT
    $unread = Message::where('sender_id', $userId)
        ->where('receiver_id', $authId)
        ->where('seen', 0)
        ->count();

    $receiver = User::find($userId);

$receiverData = [
    'id' => $receiver->id,
    'name' => $receiver->name,
    'profile_image' => $receiver->profile_img
        ? asset('storage/profile_images/'.$receiver->profile_img)
        : null,
];
    return response()->json([
        'receiver' => $receiverData,
        'messages' => $messages->items(),
        'pagination' => [
        'current_page' => $messages->currentPage(),
        'last_page' => $messages->lastPage(),
        'has_more' => $messages->hasMorePages(),
    ],
        'unread_count' => $unread
    ]);
}

    
}
