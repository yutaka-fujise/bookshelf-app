<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $likeCount = rand(0, 3);

            $userIds = $users
                ->where('id', '!=', $review->user_id)
                ->shuffle()
                ->take($likeCount)
                ->pluck('id');

            $review->likedUsers()->syncWithoutDetaching($userIds);
        }
    }
}