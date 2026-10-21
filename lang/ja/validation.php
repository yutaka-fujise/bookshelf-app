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

        // 書籍
        'title' => 'タイトル',
        'author' => '著者',
        'isbn' => 'ISBN-13',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',

        // ジャンル
        'genres' => 'ジャンル',
        'genres.*' => 'ジャンル',
    ],

    'custom' => [
        // 会員登録
        'email' => [
            'unique' => 'このメールアドレスはすでに登録されています。',
        ],

        // 書籍タイトル
        'title' => [
            'required' => 'タイトルを入力してください。',
            'max' => 'タイトルは120文字以内で入力してください。',
        ],

        // 著者
        'author' => [
            'required' => '著者を入力してください。',
            'max' => '著者は255文字以内で入力してください。',
        ],

        // ISBN-13
        'isbn' => [
            'required' => 'ISBN-13は数字13桁で入力してください。',
            'digits' => 'ISBN-13は数字13桁で入力してください。',
            'unique' => 'このISBN-13はすでに登録されています。',
        ],

        // 出版日
        'published_date' => [
            'required' => '出版日を入力してください。',
            'date' => '正しい出版日を入力してください。',
        ],

        // 説明
        'description' => [
            'max' => '説明は1000文字以内で入力してください。',
        ],

        // 画像URL
        'image_url' => [
            'url' => '画像URLは正しいURL形式で入力してください。',
        ],

        // ジャンル
        'genres' => [
            'required' => 'ジャンルを1つ以上選択してください。',
            'min' => 'ジャンルを1つ以上選択してください。',
        ],
    ],
];
