<?php

return [
    'required' => ':attributeを入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => '正しいメールアドレス形式で入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'confirmed' => 'パスワード確認と一致しません。',
    'unique' => 'この:attributeはすでに登録されています。',

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
    ],

    'custom' => [
        'email' => [
            'unique' => 'このメールアドレスはすでに登録されています。',
        ],
    ],
];
