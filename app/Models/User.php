<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property UserRole $role
 * @property UserStatus $status
 * @property-read GoogleDriveConnection|null $googleDriveConnection
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'status',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * ユーザーが利用可能な状態か判定する。
     *
     * @return bool 判定結果
     */
    public function isActive(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * ユーザーに紐付く生徒情報を返す。
     *
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * ユーザーに紐付く教員情報を返す。
     *
     * @return HasOne<Teacher, $this>
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * ユーザー本人のOAuthトークンだけを参照できるよう、Google Workspace共通連携情報との1対1関連を返す。
     *
     * @return HasOne<GoogleDriveConnection, $this>
     */
    public function googleDriveConnection(): HasOne
    {
        return $this->hasOne(GoogleDriveConnection::class);
    }

    /**
     * このユーザーが作成した授業実施日を返す。
     *
     * @return HasMany<LessonSession, $this>
     */
    public function createdLessonSessions(): HasMany
    {
        return $this->hasMany(
            LessonSession::class,
            'created_by',
        );
    }

    /**
     * このユーザーが登録した出欠記録を返す。
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function recordedAttendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class,
            'recorded_by',
        );
    }

    /**
     * このユーザーが修正した出欠記録を返す。
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function correctedAttendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class,
            'corrected_by',
        );
    }

    /**
     * このユーザーが入力した最終評価を返す。
     *
     * @return HasMany<FinalEvaluation, $this>
     */
    public function evaluatedFinalEvaluations(): HasMany
    {
        return $this->hasMany(
            FinalEvaluation::class,
            'evaluated_by',
        );
    }

    /**
     * このユーザーが作成した面談記録を返す。
     *
     * @return HasMany<InterviewRecord, $this>
     */
    public function createdInterviewRecords(): HasMany
    {
        return $this->hasMany(
            InterviewRecord::class,
            'created_by',
        );
    }

    /**
     * このユーザーが更新した面談記録を返す。
     *
     * @return HasMany<InterviewRecord, $this>
     */
    public function updatedInterviewRecords(): HasMany
    {
        return $this->hasMany(
            InterviewRecord::class,
            'updated_by',
        );
    }

    /**
     * このユーザーの操作ログを返す。
     *
     * @return HasMany<OperationLog, $this>
     */
    public function operationLogs(): HasMany
    {
        return $this->hasMany(OperationLog::class);
    }

    /**
     * このユーザーが作成したお知らせを返す。
     *
     * @return HasMany<Announcement, $this>
     */
    public function createdAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * このユーザーが更新したお知らせを返す。
     *
     * @return HasMany<Announcement, $this>
     */
    public function updatedAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'updated_by');
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }
}
