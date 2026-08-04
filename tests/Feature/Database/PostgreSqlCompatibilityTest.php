<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PostgreSQL固有の接続設定と物理DB定義を確認するフィーチャーテスト。
 *
 * 既定接続、主要カラム型、CHECK制約がPostgreSQL初版設計どおりに作成されることを検証する。
 */
final class PostgreSqlCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト実行時の既定データベース接続がPostgreSQLであることを確認する。
     *
     * 前提: phpunit.xmlのPostgreSQL接続情報を使用してテストを起動する。
     * 処理: Laravelの既定接続名と実際のPDOドライバ名を取得する。
     * 期待結果: いずれも`pgsql`となり、SQLite等へフォールバックしていないことを確認する。
     */
    public function test_default_database_connection_is_postgresql(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', DB::connection()->getDriverName());
    }

    /**
     * 主要カラムがPostgreSQL向けの物理型で作成されることを確認する。
     *
     * 前提: 全マイグレーションをPostgreSQLへ適用する。
     * 処理: information_schemaから主要カラムの内部型名を取得する。
     * 期待結果: JSONB、SMALLINT、BIGINT、INTEGERが物理DB設計どおりに設定されることを確認する。
     */
    public function test_postgresql_column_types_match_the_physical_design(): void
    {
        $this->assertSame(
            'jsonb',
            $this->columnUdtName('operation_logs', 'detail'),
        );
        $this->assertSame(
            'int2',
            $this->columnUdtName('courses', 'academic_year'),
        );
        $this->assertSame(
            'int2',
            $this->columnUdtName('timetable_slots', 'day_of_week'),
        );
        $this->assertSame(
            'int2',
            $this->columnUdtName('final_evaluations', 'grade_level'),
        );
        $this->assertSame(
            'int8',
            $this->columnUdtName('operation_logs', 'target_id'),
        );
        $this->assertSame(
            'int4',
            $this->columnUdtName('external_links', 'display_order'),
        );
    }

    /**
     * PostgreSQL向けの範囲CHECK制約が作成されることを確認する。
     *
     * 前提: 全マイグレーションをPostgreSQLへ適用する。
     * 処理: pg_constraintから設計対象のCHECK制約名を取得する。
     * 期待結果: 年度、曜日、時限、評価、外部リンク、監査ログの制約がすべて存在することを確認する。
     */
    public function test_postgresql_check_constraints_are_created(): void
    {
        $expectedConstraints = [
            'chk_courses_academic_year',
            'chk_evaluations_academic_year',
            'chk_evaluations_grade_level',
            'chk_external_links_display_order',
            'chk_external_links_scope_id',
            'chk_operation_logs_target_id',
            'chk_timetable_day',
            'chk_timetable_period',
        ];

        $actualConstraints = DB::table('pg_constraint')
            ->whereIn('conname', $expectedConstraints)
            ->orderBy('conname')
            ->pluck('conname')
            ->map(static fn (mixed $name): string => (string) $name)
            ->all();

        $this->assertSame($expectedConstraints, $actualConstraints);
    }

    /**
     * 指定カラムのPostgreSQL内部型名を取得する。
     *
     * @param  string  $table  テーブル名
     * @param  string  $column  カラム名
     * @return string PostgreSQLの内部型名
     */
    private function columnUdtName(string $table, string $column): string
    {
        $row = DB::selectOne(
            <<<'SQL'
                SELECT udt_name
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND column_name = ?
                SQL,
            [$table, $column],
        );

        $this->assertNotNull($row);
        $values = (array) $row;

        return (string) ($values['udt_name'] ?? '');
    }
}
