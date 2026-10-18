<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    // 書籍の作成者本人だけが編集できる
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    // 書籍の作成者本人だけが削除できる
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
