<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class UserSeeder extends Seeder
{
    public function run()
    {

        // Origin point (latitude, longitude) for 100 km range
        $originLat = 23.75892765; // Example: Dhaka Latitude
        $originLng = 90.44689324; // Example: Dhaka Longitude

        $imgPath = public_path('img');
        $imgFile = glob($imgPath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);


        $randomImg = $imgFile ? $imgFile[array_rand($imgFile)] : null;


        $storedPath = null;
        if ($randomImg) {
            $storedPath = Storage::disk('public')->putFile('images/avatars', new File($randomImg));
        }

        // Number of random users to create
        $totalUsers = 50;

        for ($i = 0; $i < $totalUsers; $i++) {
            // Generate random latitude and longitude within 100km
            $randomCoordinates = $this->generateCoordinates($originLat, $originLng, 10, 100);
            $gender = fake()->randomElement(['male', 'female']);
            User::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'), // Default password
                'lat' => $randomCoordinates['lat'],
                'lng' => $randomCoordinates['lng'],
                'avatar' => $storedPath,
                'dob' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'gender' => json_encode(['value' => $gender, 'is_show' => true]),
                'dating_with' => fake()->randomElement(['male', 'female']),
                'is_notify' => fake()->boolean(),
                'address' => fake()->address(),
            ]);
        }
    }

    /**
     * Generate random coordinates within a given distance (in km).
     */
    private function generateCoordinates($originLat, $originLng, $minDistance = 10, $maxDistance = 100)
    {
        $earthRadius = 6371;


        $distance = mt_rand($minDistance * 1000, $maxDistance * 1000) / 1000;

        $distanceInRadians = $distance / $earthRadius;


        $bearing = deg2rad(mt_rand(0, 360));


        $newLat = asin(
            sin(deg2rad($originLat)) * cos($distanceInRadians) +
                cos(deg2rad($originLat)) * sin($distanceInRadians) * cos($bearing)
        );


        $newLng = deg2rad($originLng) + atan2(
            sin($bearing) * sin($distanceInRadians) * cos(deg2rad($originLat)),
            cos($distanceInRadians) - sin(deg2rad($originLat)) * sin($newLat)
        );

        return [
            'lat' => rad2deg($newLat),
            'lng' => rad2deg($newLng),
        ];
    }
}
