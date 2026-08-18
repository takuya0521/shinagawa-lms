<?php

namespace App\Services\GoogleClassroom;

use App\Data\GoogleClassroomAnnouncement;
use App\Data\GoogleClassroomCourse;
use App\Data\GoogleClassroomCourseDetail;
use App\Data\GoogleClassroomCourseWork;
use App\Exceptions\GoogleClassroom\GoogleClassroomResponseException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Models\User;
use App\Services\GoogleClassroom\Support\GoogleClassroomConfiguration;
use App\Services\GoogleWorkspace\Support\GoogleApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Google Classroom APIからクラス、課題、お知らせを取得する。
 */
final class GoogleClassroomService
{
    /**
     * @param  GoogleClassroomConfiguration  $configuration  Classroom API設定
     * @param  GoogleApiClient  $client  Google共通HTTPクライアント
     */
    public function __construct(
        private readonly GoogleClassroomConfiguration $configuration,
        private readonly GoogleApiClient $client,
    ) {}

    /**
     * Classroom参照へ要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> Classroom APIに必要なOAuthスコープ
     */
    public function requiredScopes(): array
    {
        return $this->configuration->requiredScopes();
    }

    /**
     * 利用者が閲覧できるGoogle Classroomクラスを取得する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  ?string  $keyword  クラス名またはセクションの絞り込み文字列
     * @param  string  $state  ACTIVE、ARCHIVED、ALLのいずれか
     * @return Collection<int, GoogleClassroomCourse> クラス一覧
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleClassroomResponseException Classroom API応答が不正な場合
     */
    public function courses(User $user, ?string $keyword, string $state): Collection
    {
        $query = [
            'pageSize' => $this->configuration->pageSize(),
        ];

        if ($state !== 'ALL') {
            $query['courseStates'] = $state;
        }

        $response = $this->client->send(
            $user,
            $this->requiredScopes(),
            'GET',
            $this->configuration->apiUrl('courses'),
            ['query' => $query],
        );
        $items = $response->json('courses', []);

        if (! is_array($items)) {
            throw new GoogleClassroomResponseException('Google Classroomのクラス一覧を読み取れませんでした。');
        }

        $courses = collect($items)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleClassroomCourse => $this->mapCourse($item));

        if ($keyword !== null && $keyword !== '') {
            $normalizedKeyword = mb_strtolower($keyword);
            $courses = $courses->filter(
                static fn (GoogleClassroomCourse $course): bool => str_contains(
                    mb_strtolower($course->name.' '.($course->section ?? '')),
                    $normalizedKeyword,
                ),
            );
        }

        return $courses->values();
    }

    /**
     * クラス情報、課題、お知らせを並列取得して詳細画面用データへ変換する。
     *
     * @param  User  $user  Google Workspace連携済みユーザー
     * @param  string  $courseId  ClassroomクラスID
     * @return GoogleClassroomCourseDetail クラス詳細
     *
     * @throws ConnectionException Google APIへ接続できない場合
     * @throws RequestException Google APIがエラーを返した場合
     * @throws GoogleWorkspaceException OAuth設定または再連携が必要な場合
     * @throws GoogleClassroomResponseException Classroom API応答が不正な場合
     */
    public function courseDetail(User $user, string $courseId): GoogleClassroomCourseDetail
    {
        $encodedCourseId = rawurlencode($courseId);
        $responses = $this->client->sendMany(
            $user,
            $this->requiredScopes(),
            [
                'course' => [
                    'method' => 'GET',
                    'url' => $this->configuration->apiUrl('courses/'.$encodedCourseId),
                ],
                'course_work' => [
                    'method' => 'GET',
                    'url' => $this->configuration->apiUrl(
                        'courses/'.$encodedCourseId.'/courseWork',
                    ),
                    'options' => [
                        'query' => [
                            'orderBy' => 'dueDate asc,updateTime desc',
                            'pageSize' => $this->configuration->pageSize(),
                        ],
                    ],
                ],
                'announcements' => [
                    'method' => 'GET',
                    'url' => $this->configuration->apiUrl(
                        'courses/'.$encodedCourseId.'/announcements',
                    ),
                    'options' => [
                        'query' => [
                            'orderBy' => 'updateTime desc',
                            'pageSize' => $this->configuration->pageSize(),
                        ],
                    ],
                ],
            ],
        );

        $course = $responses['course']->json();
        $courseWork = $responses['course_work']->json('courseWork', []);
        $announcements = $responses['announcements']->json('announcements', []);

        if (! is_array($course) || ! is_array($courseWork) || ! is_array($announcements)) {
            throw new GoogleClassroomResponseException('Google Classroomのクラス詳細を読み取れませんでした。');
        }

        /** @var list<GoogleClassroomCourseWork> $courseWorkItems */
        $courseWorkItems = collect($courseWork)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleClassroomCourseWork => $this->mapCourseWork($item))
            ->values()
            ->all();

        /** @var list<GoogleClassroomAnnouncement> $announcementItems */
        $announcementItems = collect($announcements)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): GoogleClassroomAnnouncement => $this->mapAnnouncement($item))
            ->values()
            ->all();

        return new GoogleClassroomCourseDetail(
            course: $this->mapCourse($course),
            courseWork: $courseWorkItems,
            announcements: $announcementItems,
        );
    }

    /**
     * ClassroomのCourse応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Classroom API応答
     * @return GoogleClassroomCourse 変換済みクラス情報
     */
    private function mapCourse(array $payload): GoogleClassroomCourse
    {
        $id = $payload['id'] ?? null;
        $name = $payload['name'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($name) || $name === '') {
            throw new GoogleClassroomResponseException('Google Classroomのクラス応答に必須項目がありません。');
        }

        return new GoogleClassroomCourse(
            id: $id,
            name: $name,
            section: $this->optionalString($payload['section'] ?? null),
            description: $this->optionalString($payload['description'] ?? null),
            room: $this->optionalString($payload['room'] ?? null),
            state: $this->optionalString($payload['courseState'] ?? null) ?? 'UNKNOWN',
            alternateLink: $this->optionalString($payload['alternateLink'] ?? null),
            enrollmentCode: $this->optionalString($payload['enrollmentCode'] ?? null),
            updatedAt: $this->optionalDateTime($payload['updateTime'] ?? null),
        );
    }

    /**
     * ClassroomのCourseWork応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Classroom API応答
     * @return GoogleClassroomCourseWork 変換済み課題情報
     */
    private function mapCourseWork(array $payload): GoogleClassroomCourseWork
    {
        $id = $payload['id'] ?? null;
        $title = $payload['title'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($title) || $title === '') {
            throw new GoogleClassroomResponseException('Google Classroomの課題応答に必須項目がありません。');
        }

        $maxPoints = $payload['maxPoints'] ?? null;

        return new GoogleClassroomCourseWork(
            id: $id,
            title: $title,
            description: $this->optionalString($payload['description'] ?? null),
            state: $this->optionalString($payload['state'] ?? null) ?? 'UNKNOWN',
            workType: $this->optionalString($payload['workType'] ?? null) ?? 'UNKNOWN',
            alternateLink: $this->optionalString($payload['alternateLink'] ?? null),
            dueAt: $this->dueDateTime($payload['dueDate'] ?? null, $payload['dueTime'] ?? null),
            maxPoints: is_numeric($maxPoints) ? (float) $maxPoints : null,
            updatedAt: $this->optionalDateTime($payload['updateTime'] ?? null),
        );
    }

    /**
     * ClassroomのAnnouncement応答を画面表示用DTOへ変換する。
     *
     * @param  array<string, mixed>  $payload  Classroom API応答
     * @return GoogleClassroomAnnouncement 変換済みお知らせ情報
     */
    private function mapAnnouncement(array $payload): GoogleClassroomAnnouncement
    {
        $id = $payload['id'] ?? null;
        $text = $payload['text'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($text) || $text === '') {
            throw new GoogleClassroomResponseException('Google Classroomのお知らせ応答に必須項目がありません。');
        }

        return new GoogleClassroomAnnouncement(
            id: $id,
            text: $text,
            state: $this->optionalString($payload['state'] ?? null) ?? 'UNKNOWN',
            alternateLink: $this->optionalString($payload['alternateLink'] ?? null),
            updatedAt: $this->optionalDateTime($payload['updateTime'] ?? null),
        );
    }

    /**
     * Classroomの提出期限をUTCからアプリケーションタイムゾーンへ変換する。
     *
     * @param  mixed  $date  Google Date形式
     * @param  mixed  $time  Google TimeOfDay形式
     * @return CarbonImmutable|null 変換済み提出期限
     */
    private function dueDateTime(mixed $date, mixed $time): ?CarbonImmutable
    {
        if (! is_array($date) || ! is_array($time)) {
            return null;
        }

        $year = $date['year'] ?? null;
        $month = $date['month'] ?? null;
        $day = $date['day'] ?? null;
        $hours = $time['hours'] ?? 0;
        $minutes = $time['minutes'] ?? 0;
        $seconds = $time['seconds'] ?? 0;

        foreach ([$year, $month, $day, $hours, $minutes, $seconds] as $value) {
            if (! is_numeric($value)) {
                return null;
            }
        }

        return CarbonImmutable::create(
            (int) $year,
            (int) $month,
            (int) $day,
            (int) $hours,
            (int) $minutes,
            (int) $seconds,
            'UTC',
        )->setTimezone((string) config('app.timezone'));
    }

    /**
     * RFC3339日時をアプリケーションタイムゾーンへ変換する。
     *
     * @param  mixed  $value  RFC3339日時またはnull
     * @return CarbonImmutable|null 変換済み日時
     */
    private function optionalDateTime(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->setTimezone((string) config('app.timezone'));
    }

    /**
     * APIの任意文字列をnull許容文字列へ正規化する。
     *
     * @param  mixed  $value  API値
     * @return string|null 空文字を除外した文字列
     */
    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
