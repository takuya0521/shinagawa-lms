<?php

namespace App\Data;

/**
 * Google Forms APIのフォーム詳細を画面へ渡す不変データ。
 */
final readonly class GoogleFormDetail
{
    /**
     * @param  string  $id  GoogleフォームID
     * @param  string  $title  回答者に表示されるフォーム名
     * @param  string  $documentTitle  Google Drive上の文書名
     * @param  ?string  $description  フォーム説明
     * @param  ?string  $responderUri  回答者向けURL
     * @param  int  $itemCount  質問・説明・画像などの項目数
     * @param  bool  $isQuiz  テスト形式の場合はtrue
     * @param  bool  $supportsPublishing  公開設定APIを利用できる場合はtrue
     * @param  bool  $isPublished  公開中の場合はtrue
     * @param  bool  $isAcceptingResponses  回答受付中の場合はtrue
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $documentTitle,
        public ?string $description,
        public ?string $responderUri,
        public int $itemCount,
        public bool $isQuiz,
        public bool $supportsPublishing,
        public bool $isPublished,
        public bool $isAcceptingResponses,
    ) {}

    /**
     * Google Forms編集画面URLを返す。
     *
     * @return string Google Forms編集画面URL
     */
    public function editorUri(): string
    {
        return 'https://docs.google.com/forms/d/'.rawurlencode($this->id).'/edit';
    }
}
