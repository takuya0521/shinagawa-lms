<?php

namespace App\Support;

use App\Models\OperationLog;
use Illuminate\Support\Str;

final class OperationLogPresenter
{
    /**
     * 操作コードに対応する日本語名を返す。
     *
     * @param  string  $action  業務処理
     * @return string 取得した文字列
     */
    public static function actionLabel(
        string $action,
    ): string {
        return match ($action) {
            'assign_course_teacher' => '担当教員設定',
            'unassign_course_teacher' => '担当教員解除',
            'change_password' => 'パスワード変更',
            'create_user' => 'ユーザー登録',
            'update_user' => 'ユーザー更新',
            'change_user_status' => 'ユーザー利用状態変更',
            'create_class_group' => 'クラス登録',
            'update_class_group' => 'クラス更新',
            'create_subject' => '科目登録',
            'update_subject' => '科目更新',
            'create_course' => '授業登録',
            'update_course' => '授業更新',
            'create_timetable_slot' => '時間割登録',
            'update_timetable_slot' => '時間割更新',
            'create_announcement' => 'お知らせ登録',
            'publish_announcement' => 'お知らせ公開',
            'update_announcement' => 'お知らせ更新',
            'delete_announcement' => 'お知らせ削除',
            'create_external_link' => '外部リンク登録',
            'update_external_link' => '外部リンク更新',
            'create_interview' => '面談記録登録',
            'update_interview' => '面談記録更新',
            'evaluation_correct' => '成績修正',
            'export_operation_logs' => '操作ログCSV出力',
            default => Str::headline($action),
        };
    }

    /**
     * 対象テーブルに対応する日本語名を返す。
     *
     * @param  ?string  $targetTable  対象テーブル名
     * @return string 取得した文字列
     */
    public static function targetLabel(
        ?string $targetTable,
    ): string {
        return match ($targetTable) {
            'announcements' => 'お知らせ',
            'class_groups' => 'クラス',
            'courses' => '授業',
            'external_links' => '外部リンク',
            'final_evaluations' => '最終評価',
            'interview_records' => '面談記録',
            'operation_logs' => '操作ログ',
            'subjects' => '科目',
            'timetable_slots' => '時間割',
            'users' => 'ユーザー',
            null, '' => '対象なし',
            default => $targetTable,
        };
    }

    /**
     * 一覧へ表示するログ詳細の要約を返す。
     *
     * @param  OperationLog  $operationLog  対象操作ログ
     * @return string 取得した文字列
     */
    public static function detailSummary(
        OperationLog $operationLog,
    ): string {
        $detail = $operationLog->detail ?? [];

        foreach ([
            'title',
            'name',
            'class_name',
            'subject_name',
            'course_name',
            'link_name',
            'correction_reason',
            'interview_type',
        ] as $key) {
            $value = $detail[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return Str::limit(
                    trim($value),
                    80,
                );
            }
        }

        $before = $detail['before'] ?? null;
        $after = $detail['after'] ?? null;

        if (is_array($before) || is_array($after)) {
            return '変更前後の情報あり';
        }

        return $detail === []
            ? '-'
            : '詳細情報あり';
    }
}
