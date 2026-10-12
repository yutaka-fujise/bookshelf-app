<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn13' => '9784101010014',
                'published_at' => '1905-01-01',
                'description' => '猫の視点から人間社会を風刺的に描いた夏目漱石の代表作。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genres' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn13' => '9784422100524',
                'published_at' => '1936-10-01',
                'description' => '人間関係やコミュニケーションの基本原則をまとめた一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn13' => '9784873115658',
                'published_at' => '2012-06-23',
                'description' => '読みやすく理解しやすいコードを書くための考え方を解説した技術書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genres' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn13' => '9784863940246',
                'published_at' => '2013-08-30',
                'description' => '人生や仕事における主体的な行動と成長の原則をまとめた一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn13' => '9784101010021',
                'published_at' => '1906-04-01',
                'description' => '正義感の強い青年教師の赴任先での騒動を描いた小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genres' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn13' => '9784309226712',
                'published_at' => '2016-09-08',
                'description' => '人類の歴史を大きな視点から読み解く歴史・科学書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genres' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn13' => '9784844303598',
                'published_at' => '2017-12-18',
                'description' => '保守しやすく品質の高いコードを書くための原則を解説した技術書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genres' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn13' => '9784478025819',
                'published_at' => '2013-12-13',
                'description' => 'アドラー心理学を対話形式で分かりやすく紹介した自己啓発書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genres' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn13' => '9784163902302',
                'published_at' => '2015-03-11',
                'description' => '若手芸人たちの才能や葛藤、人間関係を描いた小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genres' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn13' => '9784822289607',
                'published_at' => '2019-01-11',
                'description' => 'データをもとに世界を正しく捉えるための考え方を解説した一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genres' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn13' => '9784822251468',
                'published_at' => '2007-01-18',
                'description' => '輸送用コンテナが世界経済や物流をどのように変えたかを描いた一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $bookData) {
            $genreNames = $bookData['genres'];
            unset($bookData['genres']);

            $book = Book::firstOrCreate(
                ['isbn13' => $bookData['isbn13']],
                array_merge($bookData, [
                    'user_id' => $users->random()->id,
                ])
            );

            $genreIds = Genre::whereIn('name', $genreNames)
                ->pluck('id');

            $book->genres()->sync($genreIds);
        }
    }
}