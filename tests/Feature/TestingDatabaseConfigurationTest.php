<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestingDatabaseConfigurationTest extends TestCase
{
    public function test_testing_database_is_configured(): void
    {
        // Laravelの実行環境を確認
        $this->assertSame(
            'testing',
            config('app.env')
        );

        // DB接続方式を確認
        $this->assertSame(
            'mysql',
            config('database.default')
        );

        // 設定上のDB名を確認
        $this->assertSame(
            'testing',
            config('database.connections.mysql.database')
        );

        // 実際にMySQLへ接続してDB名を確認
        $databaseName = DB::selectOne(
            'SELECT DATABASE() AS database_name'
        )->database_name;

        $this->assertSame(
            'testing',
            $databaseName
        );
    }
}
