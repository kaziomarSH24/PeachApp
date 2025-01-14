<?php

use App\Mail\OTP;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

if(!function_exists('sentOTP')){
    function sentOTP(array $data, $otp_expiry_time){
        $otp = generateOtp();
        $otp_expiry_at = Carbon::now()->addMinutes($otp_expiry_time)->format('Y-m-d H:i:s');
        $data = [
            'email' => $data['email'],
            'subject' => 'OTP Verification',
            'otp' => $otp,
            'otp_expiry_at' => $otp_expiry_at,
            'otp_expiry_time' => $otp_expiry_time
        ];
        Mail::to($data['email'])->send(new OTP($data));
        return $data;
    }
}

//generate otp
if (!function_exists('generateOtp')) {
    function generateOtp($length = 6)
    {
        $otp = str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        return $otp;
    }
}

//calculate distance between two points
if(!function_exists('calculateDistance')){
    function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = null){
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}
