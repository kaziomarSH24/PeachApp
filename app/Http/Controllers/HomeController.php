<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //get nearby users
    public function getNearbyUsers(Request $request)
{
    $perPage = $request->per_page ?? 1;
    $unit = $request->unit ?? 'M'; // M for miles, K for kilometers
    $lat = auth()->user()->lat;
    $lng = auth()->user()->lng;
    $dob = auth()->user()->dob;
    $ageRange = json_decode(auth()->user()->age_range);

    $max_distance = auth()->user()->max_distance;
    $dating_with = auth()->user()->dating_with;
    $radius = $unit === 'M' ? 3958.8 : 6371;

    $minAge = $ageRange->min_age;
    $maxAge = $ageRange->max_age;
    $minDob = now()->subYears($maxAge)->format('Y-m-d');
    $maxDob = now()->subYears($minAge)->format('Y-m-d');

    $users = User::with('profile')
        ->selectRaw("users.*,
            (? * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) AS distance,
            TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age", // Calculate user age
            [$radius, $lat, $lng, $lat]
        )
        ->having('distance', '<', $max_distance)
        ->where('id', '!=', auth()->id())
        ->whereBetween('dob', [$minDob, $maxDob]);


    if ($dating_with !== 'everyone') {
        $users->whereJsonContains('gender->value', $dating_with);
    }

    $users = $users->orderBy('distance')->paginate($perPage);

    $users->getCollection()->transform(function ($user) use ($unit) {
        $user->makeHidden('email_verified_at', 'password', 'otp', 'otp_expiry_at', 'remember_token', 'created_at', 'updated_at','dating_with','is_notify','max_distance','age_range');
        if($user->profile){
            $user->profile->makeHidden('user_id', 'created_at', 'updated_at');
        }
        $user->distance = round($user->distance, 2);
        $user->unit = $unit === 'M' ? 'mi' : 'km';
        // $user->gender = json_decode($user->gender)->value;
        $user->gender = json_decode($user->gender);
        $user->passions = json_decode($user->passions);
        $user->interests = json_decode($user->interests);
        $user->ethnicity = json_decode($user->ethnicity);
        $user->have_children = json_decode($user->have_children);
        $user->home_town = json_decode($user->home_town);
        $user->work_place = json_decode($user->work_place);
        $user->job = json_decode($user->job);
        $user->school = json_decode($user->school);
        $user->edu_lvl = json_decode($user->edu_lvl);
        $user->religion = json_decode($user->religion);
        $user->drink = json_decode($user->drink);
        $user->smoke = json_decode($user->smoke);
        $user->smoke_weed = json_decode($user->smoke_weed);
        $user->drugs = json_decode($user->drugs);

        $profile = $user->profile;
        if($profile){
            $user->profile->images = collect(json_decode($user->profile->images))->map(function ($image) {
                return asset('storage/' . $image);// Convert to full URL
            });
            $user->profile->prompt = json_decode($user->profile->prompt);

        $user->avatar = $profile->getAvatar();
        }

        return $user;
    });

    return response()->json([
        'success' => true,
        'data' => $users,
    ]);
}

}
