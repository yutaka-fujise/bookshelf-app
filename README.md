Bookshelf（書籍管理・読書支援アプリ）

Bookshelfは、書籍の登録・検索・レビュー投稿に加え、読書計画や読書レポートによって日々の読書活動をサポートするWebアプリケーションです。

書籍の検索や管理だけでなく、読みたい本の記録、レビューによる評価の共有、読書目標の管理まで一つのアプリケーションで行えるようにしています。

環境構築

使用する環境

Docker Desktop

Docker Compose

Laravel Sail

PHP 8.5

MySQL 8.4

Node.js / npm

Windows環境ではWSL2の利用を想定しています。

以下のコマンドは、Windows環境ではWSL2のターミナルから実行してください。

また、環境構築を開始する前にDocker Desktopを起動し、Dockerが使用できる状態にしてください。

本手順は、GitHubから新しくクローンし、未初期化のMySQLデータベースを使用する場合を想定しています。

1. リポジトリをクローン

git clone https://github.com/yutaka-fujise/bookshelf-app.git

cd bookshelf-app

HTTPS形式でクローンするため、GitHubのSSHキー設定は不要です。

以降のコマンドは、特に指定がない限り bookshelf-app ディレクトリ内で実行してください。

2. Composerの依存パッケージをインストール

Laravel SailのDockerfileは vendor ディレクトリ内にあるため、初回起動前にComposerの依存パッケージをインストールします。

ホスト環境にPHPとComposerがインストールされている場合：

composer install

ホスト環境にPHPやComposerがない場合は、DockerのComposerイメージからインストールできます。

docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" -w /app composer:2 install --ignore-platform-reqs

このコマンドはDocker上のComposerを使用するため、ホスト環境にPHPやComposerをインストールする必要はありません。

なお、--ignore-platform-reqs はインストール時のPHPバージョンや拡張機能などの要件チェックを省略するオプションです。アプリケーションは、後続のLaravel Sail環境で実行します。

3. .envファイルを作成

cp .env.example .env

.env に以下の設定が含まれていることを確認し、必要に応じて編集してください。

APP_NAME=Bookshelf
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bookshelf
DB_USERNAME=sail
DB_PASSWORD=password

GOOGLE_BOOKS_API_KEY=

MySQLのデータベース名・ユーザー名・パスワードは、Dockerコンテナを初めて起動する前に設定してください。

MySQLの初回起動時には、これらの設定を使用してデータベースとユーザーが初期化されます。

Google Books APIを利用する場合は、GOOGLE_BOOKS_API_KEY に自身で取得したAPIキーを設定してください。

APIキーはGitHubに公開しないでください。

4. Dockerコンテナをビルド・起動

./vendor/bin/sail up -d --build

初回起動時はDockerイメージのビルドとMySQLの初期化が行われます。

コンテナの起動状態を確認してください。

./vendor/bin/sail ps

MySQLコンテナの STATUS が healthy になっていることを確認してから、次の手順に進んでください。

起動直後は以下のように表示される場合があります。

Up ... (health: starting)

この場合、MySQLはまだ初期化中です。少し時間をおいて、再度 ./vendor/bin/sail ps を実行してください。

MySQLの初期化が完了する前にマイグレーションを実行すると、次のような接続エラーが発生する場合があります。

SQLSTATE[HY000] [2002] Connection refused

既存のDocker環境を再利用する場合の注意事項

以前に同じプロジェクト名でDocker環境を起動していた場合、MySQLの永続ボリュームが再利用されることがあります。

GitHubからリポジトリを再クローンしたり、bookshelf-app ディレクトリを削除したりしても、Dockerの永続ボリュームは自動的には削除されません。

そのため、既存のデータベース名やユーザー権限が現在の .env と一致せず、接続エラーが発生する場合があります。

既存のMySQLボリュームは次のコマンドで確認できます。

docker volume ls

通常のプロジェクト名で起動した場合、MySQLボリューム名は次のようになります。

bookshelf-app_sail-mysql

既存のボリュームがある場合は、保存済みデータが必要かどうかを確認してください。

Dockerボリュームの削除はデータベース内の保存済みデータを消去するため、通常の環境構築では実行しないでください。

既存データを破棄して完全に新しいデータベースで構築し直す場合の手順は、後述の「トラブルシューティング」に記載しています。

5. アプリケーションキーを生成

./vendor/bin/sail artisan key:generate

6. マイグレーション・シーディングを実行

MySQLコンテナが healthy になっていることを確認したうえで、次のコマンドを実行してください。

./vendor/bin/sail artisan migrate --seed

このコマンドは、新しいデータベースへの初回構築時に実行してください。

初期データとして、ユーザー・ジャンル・書籍・レビュー・お気に入り・レビューへのいいね・読書計画が登録されます。

注意事項

すでにシーディング済みのデータベースに対して、同じコマンドを繰り返し実行すると、レビューなどのデータが重複し、UNIQUE制約エラーが発生する場合があります。

SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry

これは、初期データがすでに登録されている状態で、同じ組み合わせのデータを再登録しようとした場合に発生するエラーです。

一度初期構築が成功した後は、通常のアプリケーション起動時にシーディングを再実行する必要はありません。

既存データを保持したまま、新しく追加されたマイグレーションのみを実行する場合は、次のコマンドを使用してください。

./vendor/bin/sail artisan migrate

7. Node.jsパッケージをインストール

./vendor/bin/sail npm install

依存パッケージのインストール時に、脆弱性に関する警告が表示される場合があります。

警告が表示された場合は内容を確認してください。警告の有無とインストール処理の成否は別なので、コマンドが正常終了したことを確認して次へ進んでください。

8. フロントエンドのビルド

./vendor/bin/sail npm run build

このコマンドで、画面表示に必要なCSS・JavaScriptのファイルが生成されます。

フロントエンドのビルドが完了してから、ブラウザでアプリケーションを開いてください。

ビルド前にアクセスすると、次のエラーが発生する場合があります。

Vite manifest not found

開発中にViteの開発サーバーを利用する場合：

./vendor/bin/sail npm run dev

9. アプリケーションにアクセス

ブラウザで以下にアクセスしてください。

http://localhost

書籍一覧画面が表示され、初期データの書籍が確認できれば、基本的な環境構築は完了です。

ログインなどの動作確認には、後述のサンプルアカウントを使用できます。

10. 2回目以降の起動

一度環境構築が完了している場合、通常は次のコマンドでDockerコンテナを起動できます。

./vendor/bin/sail up -d

停止する場合：

./vendor/bin/sail down

./vendor/bin/sail down は通常、MySQLの永続ボリュームを削除しません。

そのため、通常の起動・停止のたびにマイグレーションやシーディングを再実行する必要はありません。

トラブルシューティング

MySQLへの接続が拒否される場合

以下のエラーが発生する場合：

SQLSTATE[HY000] [2002] Connection refused

MySQLコンテナの起動が完了していない可能性があります。

次のコマンドで状態を確認してください。

./vendor/bin/sail ps

MySQLの状態が healthy になってから、マイグレーションを再実行してください。

healthy になっても問題が解消しない場合は、MySQLのログを確認してください。

./vendor/bin/sail logs mysql

データベースへのアクセス権限エラーが発生する場合

以下のエラーが発生する場合：

SQLSTATE[HY000] [1044] Access denied for user 'sail'@'%' to database 'bookshelf'

.env のデータベース設定と、MySQL内に実際に作成されているデータベース・ユーザー権限が一致していない可能性があります。

まず .env の DB_DATABASE、DB_USERNAME、DB_PASSWORD を確認してください。

また、以前のDocker環境で使用したMySQLボリュームが残っていないかを確認してください。

docker volume ls

MySQLの永続ボリュームは、リポジトリを再クローンしても残る場合があります。

既存データを保持する場合は、ボリュームを削除せず、実際のデータベースと権限の設定を確認してください。

既存のMySQLデータを破棄して初期化し直す場合

この手順は、既存のMySQLデータをすべて削除してよい場合に限って実行してください。

削除したデータは、別途バックアップがなければ復元できません。

対象プロジェクトのコンテナを停止します。

./vendor/bin/sail down

通常の bookshelf-app プロジェクトで使用しているMySQLボリュームを確認します。

docker volume ls

対象が bookshelf-app_sail-mysql であることを確認し、データを破棄してよい場合のみ削除します。

docker volume rm bookshelf-app_sail-mysql

削除が完了したことを確認します。

docker volume ls

その後、.env のデータベース設定を確認し、Dockerコンテナを起動してください。

./vendor/bin/sail up -d --build

MySQLが healthy になったら、新しいデータベースに対してマイグレーションとシーディングを実行します。

./vendor/bin/sail artisan migrate --seed

この操作は、既存DBの再利用によって設定が一致しない場合の初期化手順です。通常の起動時には実行しないでください。

シーディングで重複エラーが発生する場合

以下のエラーが発生する場合：

SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry

初期データがすでに登録されているデータベースに対し、シーディングを再実行した可能性があります。

すべてのマイグレーションが完了済みの場合は、次のように表示されます。

INFO  Nothing to migrate.

マイグレーションの実行状態は次のコマンドで確認できます。

./vendor/bin/sail artisan migrate:status

既存データが登録されていてアプリケーションが正常に動作する場合、シーディングを繰り返す必要はありません。

なお、migrate:status はマイグレーションの状態を確認するコマンドであり、シーディングの完了状況まですべて確認するものではありません。

Viteのmanifestエラーが発生する場合

以下のエラーが発生する場合：

Vite manifest not found

フロントエンドのビルドが完了していない可能性があります。

次のコマンドを実行してください。

./vendor/bin/sail npm install
./vendor/bin/sail npm run build

正常終了後、ブラウザを再読み込みしてください。

使用技術（実行環境）

技術

バージョン・用途

PHP

8.5

Laravel

10.x

MySQL

8.4

Docker / Docker Compose

開発環境

Laravel Sail

Laravelのコンテナ環境

Laravel Fortify

ユーザー認証

Laravel Sanctum

APIトークン認証

Tailwind CSS

3.x

Alpine.js

3.x

Vite

5.x

PHPUnit

10.x

機能一覧

ユーザー認証

ユーザー登録

ログイン / ログアウト

認証状態に応じたアクセス制御

日本語のバリデーションメッセージ

書籍管理

書籍一覧表示

書籍詳細表示

書籍登録

書籍情報の編集

書籍削除

書籍所有者のみ編集・削除可能

ISBN-13の重複登録防止

書籍への複数ジャンル設定

書籍検索・絞り込み・並べ替え

書籍タイトル・著者によるキーワード検索

ジャンルによる絞り込み

キーワードとジャンルの組み合わせ検索

新着順・古い順・評価順・タイトル順の並べ替え

ページネーション

検索条件の保持・リセット

ISBNによる書籍情報取得

ISBNから書籍情報を検索

Google Books APIとの連携

Google Booksで取得できない場合はOpen Library APIを利用

タイトル・著者・説明・出版日・画像などの取得

取得した情報を登録フォームに反映

取得できなかった項目は手動で入力可能

注意事項：
ISBN検索後、著者名が自動入力されず、著者が不明な場合は、著者欄に「不明」と入力して登録してください。

ジャンル管理

ジャンル一覧・詳細表示

ジャンル登録

ジャンル名の編集

ジャンル削除

同名ジャンルの重複登録防止

書籍が関連付けられているジャンルの削除制限

レビュー機能

書籍へのレビュー投稿

5段階評価

コメント入力

レビューの編集・削除

レビュー所有者のみ編集・削除可能

同一ユーザーによる同一書籍への重複レビュー防止

レビューへのいいね・いいね解除

お気に入り機能

書籍のお気に入り登録・解除

自分のお気に入り書籍一覧の表示

お気に入りの重複登録防止

ランキング機能

書籍の平均評価に基づくランキング

評価の高い書籍を上位10件表示

レビューのない書籍はランキングから除外

読書計画

読書計画の登録

読書計画の一覧・詳細確認

読書期限の変更

読書完了への変更

読書計画の削除

進行中・期限切れ・完了の状態管理

状態別の絞り込み

同一書籍の進行中の読書計画の重複防止

完了済み・期限切れの読書計画がある場合の再登録

期限切れの読書計画の再編集

リマインダー・通知

読書期限の3日前のリマインダー

読書期限当日のリマインダー

読書期限の3日後のリマインダー

通知一覧表示

通知の既読処理

重複通知の防止

自分の通知のみ閲覧・操作可能

読書レポート

自分の読書データの集計

読書状況の統計表示

評価分布の表示

高評価書籍の上位5件表示

ジャンル別評価の上位5件表示

REST API

書籍一覧取得

書籍詳細取得

書籍登録

書籍更新

書籍削除

キーワード検索・ジャンル絞り込み

ページネーション

Laravel Sanctumによる書き込みAPIの認証

Policyによる書籍所有者の認可

API仕様

APIのベースURL：

http://localhost/api/v1

メソッド

エンドポイント

内容

認証

GET

/books

書籍一覧

不要

GET

/books/{book}

書籍詳細

不要

POST

/books

書籍登録

Sanctum

PUT

/books/{book}

書籍更新

Sanctum

DELETE

/books/{book}

書籍削除

Sanctum

一覧取得APIでは、次のクエリパラメータが使用できます。

パラメータ

説明

keyword

タイトル・著者による検索

genre_id

ジャンルIDによる絞り込み

per_page

1ページの表示件数（1〜100件）

page

取得するページ番号

書き込み系APIでは、Bearerトークンによる認証が必要です。

Authorization: Bearer {token}

書籍の更新・削除は、その書籍を登録したユーザーだけが実行できます。

サンプルアカウント

シーディングを実行すると、次の開発用ユーザーが作成されます。

ユーザー名

メールアドレス

山田太郎

yamada@example.com

鈴木花子

suzuki@example.com

田中一郎

tanaka@example.com

佐藤美咲

sato@example.com

高橋健太

takahashi@example.com

共通パスワード： password

これらは動作確認用のダミーアカウントです。

テスト

Laravel Sailを使用し、次のコマンドでテストを実行できます。

./vendor/bin/sail artisan test

テスト用データベース

テストでは、通常のアプリケーション用データベースとは別の testing データベースを使用します。

phpunit.xml で以下の設定を行っています。

<env name="APP_ENV" value="testing"/>
<env name="DB_DATABASE" value="testing"/>

主なテスト内容

ユーザー登録・ログイン・ログアウト

書籍CRUD・入力値のバリデーション

書籍所有者によるアクセス制御

ISBN検索と外部APIのフォールバック

書籍の検索・絞り込み・並べ替え

ジャンル管理

レビュー投稿・更新・削除

レビューへのいいね

お気に入り

ランキング

読書計画の登録・更新・削除・完了

読書計画の期限切れ判定

リマインダー通知

読書レポート

Sanctum認証を使用したAPI

モデルの属性・データベース制約

2026年10月24日時点の全体テスト実行結果：

Tests:    3 deprecated, 251 passed (720 assertions)
Duration: 27.30s

非推奨警告はPHP 8.5などの依存環境に関連するもので、テスト失敗はありません。

テーブル設計

主なテーブル

テーブル名

内容

users

ユーザー

books

書籍

genres

ジャンル

book_genre

書籍とジャンルの関連

reviews

書籍レビュー

review_likes

レビューへのいいね

favorites

お気に入り

reading_plans

読書計画

notifications

通知

personal_access_tokens

APIトークン

Laravel標準の password_reset_tokens・failed_jobs テーブルも使用しています。

テーブル設計①



テーブル設計②



ER図



URL

項目

URL

アプリケーション

http://localhost

書籍一覧

http://localhost/books

書籍ランキング

http://localhost/ranking

ログイン

http://localhost/login

ユーザー登録

http://localhost/register

phpMyAdmin

http://localhost:8080

書籍API

http://localhost/api/v1/books

上記URLはデフォルトのポート設定を使用した場合のものです。

補足

ユーザー認証にはLaravel Fortifyを使用しています。

API認証にはLaravel Sanctumを使用しています。

書籍の編集・削除にはLaravel Policyを使用し、所有者以外の操作を制限しています。

ISBN検索にはGoogle Books APIとOpen Library APIを使用しています。

読書計画のリマインダーは、期限の3日前・当日・3日後を対象としています。

Docker環境下での再現性を重視し、Laravel Sailを使用しています。

工夫した点

ISBN検索のフォールバック設計

書籍登録時の入力負担を軽減するため、ISBNを使用した書籍情報の自動取得機能を実装しました。

Google Books APIで書籍情報を取得できない場合には、Open Library APIを利用する構成としています。

また、取得できない項目については手動入力を可能にし、外部APIのデータ不足によって書籍登録ができなくなることを防いでいます。

Policyによる認可処理の共通化

書籍の編集・削除では BookPolicy を使用しています。

Web画面とREST APIで共通の所有者判定を利用することで、認可ルールの重複を減らし、一貫したアクセス制御を行っています。

所有者以外による更新・削除操作はHTTP 403で拒否する設計です。

読書計画の状態管理

読書計画に in_progress・expired・completed の状態を設けています。

同一ユーザー・同一書籍について進行中の計画が重複しないよう制御しながら、過去に完了した計画や期限切れになった計画の履歴は保持できる構成としています。

また、期限切れの計画は期限を変更することで再び進行中に戻せるようにし、実際の読書状況に合わせて柔軟に管理できるようにしています。

リマインダー通知の重複防止

読書期限の3日前・当日・3日後を対象に通知を生成する機能を実装しました。

同じ対象に対する通知の重複生成を防ぎ、通知一覧から既読管理できる構成としています。

書籍・ジャンル・レビューのデータ整合性

書籍の登録・更新処理では、関連するジャンルの更新をトランザクション内で行っています。

また、ISBNやジャンル名の重複登録をバリデーションで防止し、関連書籍が存在するジャンルの削除を制限することで、データ整合性を保つ設計としています。

テストの充実

PHPUnitを使用して、正常系だけでなく異常系のテストも実装しています。

特に、他ユーザーによる不正な更新・削除、入力値の不備、重複登録、読書計画の状態遷移、外部API取得失敗時の処理などを重点的に検証しました。