<?php

namespace Tests\Feature;

use App\Support\OldInput;
use Tests\TestCase;

final class OldInputTest extends TestCase
{
    public function test_text_inputs_render_scalar_values_and_keep_array_values_in_session(): void
    {
        $this->withSession(['_old_input' => [
            'name' => '入力済み',
            'number' => 0,
            'invalid' => ['unexpected'],
            'targets' => ['all', 'role:teacher'],
        ]]);
        $this->app['request']->setLaravelSession($this->app['session.store']);

        $this->assertSame('入力済み', OldInput::text('name'));
        $this->assertSame('0', OldInput::text('number'));
        $this->assertSame('', OldInput::text('invalid'));
        $this->assertSame('既定値', OldInput::text('missing', '既定値'));
        $this->assertSame(['all', 'role:teacher'], old('targets'));
    }
}
