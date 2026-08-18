<?php

namespace App\Services\GoogleChat\Support;

use App\Exceptions\GoogleChat\GoogleChatResponseException;

/**
 * Google Chatのリソース名とURL用IDを相互変換する。
 * 画面から渡されたIDをAPI URLへ直接連結せず、このクラスで許可文字と所属スペースを
 * 検証することで、別リソース参照やパス組み立ての不整合を防ぐ。
 */
final class GoogleChatResource
{
    /**
     * スペースIDからリソース名を生成する。
     *
     * @param  string  $spaceId  Google ChatスペースID
     * @return string spaces/{space}形式のリソース名
     *
     * @throws GoogleChatResponseException IDが不正な場合
     */
    public function spaceName(string $spaceId): string
    {
        return 'spaces/'.$this->validatedId($spaceId);
    }

    /**
     * スペースIDとメッセージIDからリソース名を生成する。
     *
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @return string spaces/{space}/messages/{message}形式のリソース名
     *
     * @throws GoogleChatResponseException IDが不正な場合
     */
    public function messageName(string $spaceId, string $messageId): string
    {
        return $this->spaceName($spaceId).'/messages/'.$this->validatedId($messageId);
    }

    /**
     * 返信先スレッドが選択中スペース配下であることを確認する。
     *
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $threadName  スレッドリソース名
     * @return string 検証済みスレッドリソース名
     *
     * @throws GoogleChatResponseException 別スペースまたは不正形式の場合
     */
    public function validatedThreadName(string $spaceId, string $threadName): string
    {
        $prefix = $this->spaceName($spaceId).'/threads/';

        if (! str_starts_with($threadName, $prefix)) {
            throw new GoogleChatResponseException('Google Chatの返信先スレッドが不正です。');
        }

        $this->validatedId(substr($threadName, strlen($prefix)));

        return $threadName;
    }

    /**
     * Googleリソース名から末尾IDを抽出する。
     *
     * @param  string  $resourceName  Google APIリソース名
     * @param  string  $separator  ID直前の区切り文字列
     * @return string URLへ安全に埋め込める形式へ変換したリソースID
     *
     * @throws GoogleChatResponseException リソース名が不正な場合
     */
    public function idFromName(string $resourceName, string $separator): string
    {
        $position = strrpos($resourceName, $separator);
        $id = $position === false
            ? ''
            : substr($resourceName, $position + strlen($separator));

        return $this->validatedId($id);
    }

    /**
     * URLへ渡すGoogleリソースIDを許可文字だけへ制限する。
     *
     * @param  string  $id  GoogleリソースID
     * @return string 許可形式を満たすGoogle ChatリソースID
     *
     * @throws GoogleChatResponseException IDが不正な場合
     */
    public function validatedId(string $id): string
    {
        if (preg_match('/\A[A-Za-z0-9_.-]+\z/u', $id) !== 1) {
            throw new GoogleChatResponseException('Google ChatのリソースIDが不正です。');
        }

        return $id;
    }
}
