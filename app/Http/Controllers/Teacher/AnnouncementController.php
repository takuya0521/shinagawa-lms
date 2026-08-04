<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AnnouncementNoticeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\VisibleAnnouncementIndexRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Queries\Announcement\VisibleAnnouncementQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnnouncementController extends Controller
{
    /**
     * 教員が閲覧できるお知らせ一覧を表示する。
     *
     * @param  VisibleAnnouncementIndexRequest  $request  HTTPリクエスト
     * @param  VisibleAnnouncementQuery  $visibleAnnouncementQuery  データ取得処理
     * @return View 表示する画面
     */
    public function index(
        VisibleAnnouncementIndexRequest $request,
        VisibleAnnouncementQuery $visibleAnnouncementQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('teacher.announcements.index', [
            'announcements' => $visibleAnnouncementQuery->paginate(
                $user,
                $request->keyword(),
                $request->noticeType(),
                $request->importantOnly(),
            ),
            'noticeTypes' => AnnouncementNoticeType::cases(),
            'keyword' => $request->keyword(),
            'selectedNoticeType' => $request->noticeType()?->value,
            'importantOnly' => $request->importantOnly() === true,
        ]);
    }

    /**
     * 教員が閲覧できるお知らせ詳細を表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  Announcement  $announcement  対象お知らせ
     * @param  VisibleAnnouncementQuery  $visibleAnnouncementQuery  データ取得処理
     * @return View 表示する画面
     */
    public function show(
        Request $request,
        Announcement $announcement,
        VisibleAnnouncementQuery $visibleAnnouncementQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $visibleAnnouncement = $visibleAnnouncementQuery->findOrFail(
            $user,
            $announcement->id,
        );

        return view('teacher.announcements.show', [
            'announcement' => $visibleAnnouncement,
        ]);
    }
}
