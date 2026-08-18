<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Google Calendar APIの予定を画面表示へ渡す不変データ。
 */
final readonly class GoogleCalendarEvent
{
    /**
     * @param  string  $id  Google Calendar上の予定ID
     * @param  string  $calendarId  所属カレンダーID
     * @param  string  $title  予定タイトル
     * @param  ?string  $description  説明
     * @param  CarbonImmutable  $startsAt  開始日時
     * @param  CarbonImmutable  $endsAt  終了日時
     * @param  bool  $isAllDay  終日予定かどうか
     * @param  ?string  $location  開催場所
     * @param  ?string  $htmlLink  Google Calendarで開くURL
     * @param  ?string  $meetLink  Google Meet参加URL
     * @param  list<string>  $recurrence  繰り返しルール
     * @param  Collection<int, GoogleCalendarAttendee>  $attendees  参加者一覧
     * @param  string  $status  予定状態
     * @param  bool  $canModify  更新可能かどうか
     */
    public function __construct(
        public string $id,
        public string $calendarId,
        public string $title,
        public ?string $description,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public bool $isAllDay,
        public ?string $location,
        public ?string $htmlLink,
        public ?string $meetLink,
        public array $recurrence,
        public Collection $attendees,
        public string $status,
        public bool $canModify,
    ) {}

    /**
     * 開始日と終了日を考慮した画面表示用日時を返す。
     *
     * @return string 日本語の予定日時
     */
    public function scheduleLabel(): string
    {
        if ($this->isAllDay) {
            $inclusiveEnd = $this->endsAt->subDay();

            if ($this->startsAt->isSameDay($inclusiveEnd)) {
                return $this->startsAt->format('Y年n月j日').'（終日）';
            }

            return $this->startsAt->format('Y年n月j日')
                .'〜'.$inclusiveEnd->format('Y年n月j日').'（終日）';
        }

        if ($this->startsAt->isSameDay($this->endsAt)) {
            return $this->startsAt->format('Y年n月j日 H:i')
                .'〜'.$this->endsAt->format('H:i');
        }

        return $this->startsAt->format('Y年n月j日 H:i')
            .'〜'.$this->endsAt->format('Y年n月j日 H:i');
    }

    /**
     * 繰り返し予定かどうかを返す。
     *
     * @return bool 繰り返しルールがある場合はtrue
     */
    public function isRecurring(): bool
    {
        return $this->recurrence !== [];
    }

    /**
     * 画面編集フォームで使用する繰り返し種別を返す。
     *
     * Google CalendarのRRULE全体を簡易フォームへ完全変換できないため、
     * 本実装が作成できる日次・週次・月次・年次だけを判定対象にする。
     *
     * @return string none、daily、weekly、monthly、yearlyのいずれか
     */
    public function recurrenceType(): string
    {
        $rule = $this->recurrence[0] ?? null;

        if (! is_string($rule)) {
            return 'none';
        }

        return match (true) {
            str_contains($rule, 'FREQ=DAILY') => 'daily',
            str_contains($rule, 'FREQ=WEEKLY') => 'weekly',
            str_contains($rule, 'FREQ=MONTHLY') => 'monthly',
            str_contains($rule, 'FREQ=YEARLY') => 'yearly',
            default => 'none',
        };
    }
}
