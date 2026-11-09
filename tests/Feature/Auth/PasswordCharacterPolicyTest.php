<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Support\Auth\LoginCredentialRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class PasswordCharacterPolicyTest extends TestCase
{
    public function test_all_password_entry_points_reject_non_ascii_and_control_characters(): void
    {
        foreach ($this->passwordRules() as $rules) {
            foreach (['StrongPass123!日本', 'StrongPass123!Ａ', 'StrongPass123!é', 'StrongPass123!😀', "StrongPass123!\n", "StrongPass123!\t", "StrongPass123!\0", "StrongPass123!\x7f"] as $password) {
                $validator = Validator::make(['password' => $password, 'password_confirmation' => $password], ['password' => $rules]);
                $this->assertTrue($validator->fails());
                $this->assertContains('パスワードは半角文字で入力してください', $validator->errors()->get('password'));
            }
        }
    }

    public function test_accepted_new_passwords_also_pass_login_validation(): void
    {
        foreach (['StrongPass123!', 'Strong Pass123!', 'StrongPass123!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'] as $password) {
            foreach ($this->passwordRules() as $rules) {
                $this->assertTrue(Validator::make(['password' => $password, 'password_confirmation' => $password], ['password' => $rules])->passes());
            }
        }
    }

    public function test_optional_admin_password_can_remain_empty_and_strength_is_preserved(): void
    {
        foreach ([new UpdateUserRequest, new UpdateTeacherRequest] as $request) {
            $this->assertTrue(Validator::make(['password' => null], ['password' => $request->rules()['password']])->passes());
        }
        $this->assertFalse(Validator::make(['password' => 'Short1!', 'password_confirmation' => 'Short1!'], ['password' => (new StoreUserRequest)->rules()['password']])->passes());
        $this->assertFalse(Validator::make(['password' => 'LongPassword123', 'password_confirmation' => 'LongPassword123'], ['password' => (new StoreTeacherRequest)->rules()['password']])->passes());
    }

    private function passwordRules(): array
    {
        return [
            LoginCredentialRules::rules()['password'],
            (new StoreUserRequest)->rules()['password'],
            (new UpdateUserRequest)->rules()['password'],
            (new StoreTeacherRequest)->rules()['password'],
            (new UpdateTeacherRequest)->rules()['password'],
            (new UpdatePasswordRequest)->rules()['password'],
        ];
    }
}
