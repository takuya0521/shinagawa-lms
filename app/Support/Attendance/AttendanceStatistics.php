<?php

namespace App\Support\Attendance;

final readonly class AttendanceStatistics
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param int $lessonCount 授業実施件数
     * @param int $expectedRecordCount 登録対象件数
     * @param int $recordedCount 登録済み件数
     * @param int $missingCount 未登録件数
     * @param int $presentCount 出席件数
     * @param int $absentCount 欠席件数
     * @param int $lateCount 遅刻件数
     * @param int $earlyLeaveCount 早退件数
     * @param ?float $attendanceRate 出席率
     */
    public function __construct(
        public int $lessonCount,
        public int $expectedRecordCount,
        public int $recordedCount,
        public int $missingCount,
        public int $presentCount,
        public int $absentCount,
        public int $lateCount,
        public int $earlyLeaveCount,
        public ?float $attendanceRate,
    ) {}

    /**
     * 出席率を画面表示用の文字列で返す。
     *
     * @return string 取得した文字列
     */
    public function attendanceRateLabel(): string
    {
        if ($this->attendanceRate === null) {
            return '-';
        }

        return number_format(
            $this->attendanceRate,
            2,
        ).'%';
    }

    /**
     * 未決仕様の影響を受けず出席率を確定できるか判定する。
     *
     * @return bool 判定結果
     */
    public function canCalculateAttendanceRate(): bool
    {
        return $this->attendanceRate !== null;
    }
}
