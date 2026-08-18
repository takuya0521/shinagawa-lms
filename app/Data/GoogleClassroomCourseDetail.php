<?php

namespace App\Data;

/**
 * Classroomクラス詳細と、そのクラスに属する課題・お知らせをまとめる。
 */
final readonly class GoogleClassroomCourseDetail
{
    /**
     * @param  GoogleClassroomCourse  $course  クラス情報
     * @param  list<GoogleClassroomCourseWork>  $courseWork  課題一覧
     * @param  list<GoogleClassroomAnnouncement>  $announcements  お知らせ一覧
     */
    public function __construct(
        public GoogleClassroomCourse $course,
        public array $courseWork,
        public array $announcements,
    ) {}
}
