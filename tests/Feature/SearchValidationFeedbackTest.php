<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SearchValidationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public static function searchPages(): array
    {
        return [
            'users' => ['admin.users.index', UserRole::Admin],
            'students' => ['admin.students.index', UserRole::Admin],
            'teachers' => ['admin.teachers.index', UserRole::Admin],
            'admin interviews' => ['admin.interviews.index', UserRole::Admin],
            'admin announcements' => ['admin.announcements.index', UserRole::Admin],
            'external links' => ['admin.external-links.index', UserRole::Admin],
            'teacher interviews' => ['teacher.interviews.index', UserRole::Teacher],
            'teacher announcements' => ['teacher.announcements.index', UserRole::Teacher],
            'student announcements' => ['student.announcements.index', UserRole::Student],
        ];
    }

    #[DataProvider('searchPages')]
    public function test_rejected_search_keyword_is_visible_after_redirect(string $route, UserRole $role): void
    {
        $user = $this->searchUser($role);
        $url = route($route);

        $this->actingAs($user)->from($url)
            ->get($url.'?'.http_build_query(['keyword' => str_repeat('a', 256)]))
            ->assertRedirect($url)
            ->assertSessionHasErrors('keyword');

        $message = session('errors')->first('keyword');
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get($url);
        $page
            ->assertOk()
            ->assertSee($message)
            ->assertSee('キーワードは255文字以内で入力してください。')
            ->assertSee('value="'.str_repeat('a', 256).'"', false)
            ->assertSee('role="alert"', false);
    }

    #[DataProvider('searchPages')]
    public function test_maximum_length_keyword_is_accepted_without_error_feedback(string $route, UserRole $role): void
    {
        $user = $this->searchUser($role);

        $this->actingAs($user)
            ->get(route($route, ['keyword' => str_repeat('a', 255)]))
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertDontSee('role="alert"', false);
    }

    private function searchUser(UserRole $role): User
    {
        return match ($role) {
            UserRole::Teacher => Teacher::factory()->create()->user,
            UserRole::Student => Student::factory()->create()->user,
            default => User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]),
        };
    }
}
