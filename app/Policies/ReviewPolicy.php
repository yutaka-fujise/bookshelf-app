<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    // レビュー投稿者本人だけが編集できる
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    // レビュー投稿者本人だけが削除できる
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
