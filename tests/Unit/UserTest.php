<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * 一括代入可能な項目を確認する
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User();

        $this->assertSame([
            'name',
            'email',
            'password',
        ], $user->getFillable());
    }

    /**
     * 非表示にする項目を確認する
     */
    public function test_user_has_correct_hidden_attributes(): void
    {
        $user = new User();

        $this->assertSame([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    /**
     * 型変換の設定を確認する
     */
    public function test_user_has_correct_casts(): void
    {
        $user = new User();

        $this->assertSame(
            'datetime',
            $user->getCasts()['email_verified_at']
        );

        $this->assertSame(
            'hashed',
            $user->getCasts()['password']
        );
    }
}
