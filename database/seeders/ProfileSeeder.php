<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\User;
use App\Models\Profile;

class ProfileSeeder extends Seeder
{
    public function run()
    {

        $imagePath = public_path('img');


        if (!file_exists($imagePath) || !is_dir($imagePath)) {
            throw new \Exception("Error: no such directory found at public/img");
        }

        $imageFiles = glob($imagePath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);


        if (empty($imageFiles)) {
            throw new \Exception("Error: no image files found in public/img");
        }


        $users = User::all();

        foreach ($users as $user) {
            $imageCount = rand(4, 6);
            $imagePaths = [];

            for ($i = 0; $i < $imageCount; $i++) {
                $randomFile = $imageFiles[array_rand($imageFiles)];


                $storedPath = Storage::disk('public')->putFile('images/profile', new File($randomFile));
                $imagePaths[] = $storedPath;
            }


            Profile::create([
                'user_id' => $user->id,
                'images' => json_encode($imagePaths),
                'prompt' => fake()->sentence(8)
            ]);
        }
    }
}
