<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExternalLinkType;
use App\Http\Controllers\Controller;
use App\Models\ExternalLink;
use App\Models\Student;
use App\Models\User;
use App\Queries\ExternalLink\ExternalLinkResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ExternalServiceController extends Controller
{
    /**
     * 年間予定・学校行事のGoogle Calendarリンクを表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param ExternalLinkResolver $externalLinkResolver 外部リンク解決処理
     * @return View 表示する画面
     */
    public function calendar(
        Request $request,
        ExternalLinkResolver $externalLinkResolver,
    ): View {
        $student = $this->resolveStudent($request);
        $links = $externalLinkResolver->forStudent(
            $student,
            ExternalLinkType::Calendar,
        );

        return view('student.external-services.index', [
            'screenId' => 'S-006',
            'title' => '年間予定・学校行事',
            'description' => '学校が案内するGoogle Calendarを確認します。',
            'links' => $links,
            'embeddableLink' => $this->embeddableCalendar($links),
        ]);
    }

    /**
     * 面談希望申込のGoogle Formsリンクを表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param ExternalLinkResolver $externalLinkResolver 外部リンク解決処理
     * @return View 表示する画面
     */
    public function interviewForm(
        Request $request,
        ExternalLinkResolver $externalLinkResolver,
    ): View {
        $student = $this->resolveStudent($request);

        return view('student.external-services.index', [
            'screenId' => 'S-007',
            'title' => '面談希望申込',
            'description' => '学校が案内するGoogle Formsから面談希望を送信します。',
            'links' => $externalLinkResolver->forStudent(
                $student,
                ExternalLinkType::Forms,
            ),
            'embeddableLink' => null,
        ]);
    }

    /**
     * 生徒向けのGoogle Chat・Driveリンクを表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param ExternalLinkResolver $externalLinkResolver 外部リンク解決処理
     * @return View 表示する画面
     */
    public function resources(
        Request $request,
        ExternalLinkResolver $externalLinkResolver,
    ): View {
        $student = $this->resolveStudent($request);
        $links = $externalLinkResolver->forStudent($student)
            ->filter(
                static fn (ExternalLink $link): bool => in_array(
                    $link->link_type,
                    [
                        ExternalLinkType::Chat,
                        ExternalLinkType::Drive,
                        ExternalLinkType::Meet,
                    ],
                    true,
                ),
            )
            ->values();

        return view('student.external-services.index', [
            'screenId' => 'S-008',
            'title' => 'Googleサービス',
            'description' => '連絡・共有資料など、学校が案内するGoogleサービスを開きます。',
            'links' => $links,
            'embeddableLink' => null,
        ]);
    }

    /**
     * ログインユーザーに紐付く生徒情報を返す。
     *
     * @param Request $request HTTPリクエスト
     * @return Student 処理結果
     */
    private function resolveStudent(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $student = Student::query()
            ->where('user_id', $user->id)
            ->first();
        abort_if($student === null, 403);

        return $student;
    }

    /**
     * 埋め込み表示可能なGoogle Calendar URLを返す。
     *
     * @param  Collection<int, ExternalLink>  $links
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function embeddableCalendar(Collection $links): ?string
    {
        foreach ($links as $link) {
            $host = parse_url($link->url, PHP_URL_HOST);
            $path = parse_url($link->url, PHP_URL_PATH);

            if ($host === 'calendar.google.com'
                && is_string($path)
                && str_contains($path, '/calendar/embed')) {
                return $link->url;
            }
        }

        return null;
    }
}
