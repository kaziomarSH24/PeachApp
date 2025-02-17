<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    //Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                $validator->errors()->first()
            ], 400);
        }

        $data = [
            'email' => $request->email
        ];

        $data = sentOTP($data, 10);
        $user = new User();
        $user->email = $data['email'];
        // $user->password = $request->password;
        $user->otp = $data['otp'];
        $user->otp_expiry_at = $data['otp_expiry_at'];
        $user->save();

        return response([
            'success' => true,
            'message' => 'OTP sent to your email'
        ], 200);
    }

    /**
     * Verify account
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user = User::where('otp', $request->otp)->first();

        if ($user) {
            $otpExpiryAt = Carbon::parse($user->otp_expiry_at);

            if ($otpExpiryAt->lt(Carbon::now())) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired! Please request for a new OTP!',
                ], 401);
            }

            // return $user;
            try {
                if (!$token = JWTAuth::fromUser($user)) {
                    return response()->json(['error' => 'Could not create token.'], 500);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token.'], 500);
            }

            $user->email_verified_at = Carbon::now();
            $user->otp = null;
            $user->otp_expiry_at = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully!',
                'token' => $token,
                'user' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP!',
            ], 401);
        }
    }

    /**
     * Login a User
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|',
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if (!$token = JWTAuth::attempt($validator->validated())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password!'
            ]);
        } else {
            $user = Auth::user();
            if ($user->email_verified_at == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email address!',
                ]);
            }
            if ($user->status == 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is blocked!',
                ]);
            }
        }

        return $this->responseWithToken($token);
    }


    /**
     * resend OTP // also using for forgot password
     */
    public function resentOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $data = [
                'email' => $user->email,
            ];
            $data = sentOTP($data, 10);
            $user->otp = $data['otp'];
            $user->otp_expiry_at = $data['otp_expiry_at'];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully! Please check your email!',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
            ]);
        }
    }

    /**
     * reset password
     */
    public function resetPassword(Request $request)
    {

        if (isset($request->otp)) {
            $user = User::where('otp', $request->otp)->first();
            if ($user) {
                if ($user->otp_expiry_at < Carbon::now()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'OTP has expired! Please request for a new OTP!',
                    ], 401);
                }
                $validator = Validator::make($request->all(), [
                    'password' => 'required|string|confirmed|min:8',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors());
                }
                $user->password = Hash::make($request->password);
                $user->otp = null;
                $user->otp_expiry_at = null;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully!',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP!',
                ], 401);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP is required!',
            ], 401);
        }
    }


    /**
     * Response with Token
     */
    protected function responseWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 600000000,
            'user' => Auth::user(),
        ]);
    }

    //check token
    public function validateToken(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if ($token) {
                $user = JWTAuth::setToken($token)->authenticate();

                if ($user) {
                    return response()->json([
                        'token_status' => true,
                        'message'      => 'Token is valid.',
                    ]);
                } else {
                    return response()->json([
                        'token_status' => false,
                        'message'      => 'Token is valid but user is not authenticated.',
                    ]);
                }
            }

            return response()->json([
                'token_status' => false,
                'error'        => 'No token provided.',
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'token_status' => false,
                'error'        => 'Token is invalid or expired.',
            ], 401);
        }
    }

    /**
     * Logout a User
     */
    public function logout(){
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
            ]);
        }
    }
}
