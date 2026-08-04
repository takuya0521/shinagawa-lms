<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Featureテストで共通利用するLaravel基底テストケース。
 *
 * アプリケーション起動やHTTPテストの共通機能をLaravelの基底クラスから継承する。
 */
abstract class TestCase extends BaseTestCase
{
    // アプリケーション起動やHTTPテストの共通処理はLaravelの基底クラスへ委譲する。
}
