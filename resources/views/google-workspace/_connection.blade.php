<section class="gw-connection" aria-label="Google Workspace連携状態">
    <span @class(['gw-connection__status', 'is-ready' => $isAuthorized]) aria-hidden="true"></span>
    <div class="gw-connection__copy">
        <strong>
            @if (! $isConfigured)
                Google Workspaceの設定が必要です
            @elseif (! $isConnected)
                Googleアカウントは未連携です
            @elseif (! $isAuthorized)
                追加権限の再承認が必要です
            @else
                Google Workspaceと連携済み
            @endif
        </strong>
        <p>
            @if (! $isConfigured)
                OAuthクライアントIDとシークレットを設定してください。
            @elseif (! $isConnected)
                Drive・Classroom・Calendar・Chat・Meet・FormsをLMS内で利用できます。
            @elseif (! $isAuthorized)
                Google Cloudで追加した操作権限を反映してください。
            @else
                Googleの権限に応じて作成・編集・共有・削除を利用できます。
            @endif
        </p>
    </div>

    <div class="gw-connection__actions">
        @if ($isConfigured && (! $isConnected || ! $isAuthorized))
            <a class="gw-button gw-button--primary" href="{{ route('google-workspace.connect') }}">
                {{ $isConnected ? '権限を再承認' : 'Google Workspaceと連携' }}
            </a>
        @elseif ($isConnected)
            <a class="gw-icon-button gw-icon-button--text" href="{{ route('google-workspace.connect') }}">
                <x-google-icon name="refresh" :size="18" />
                <span>再承認</span>
            </a>
        @endif

        @if ($isConnected)
            <form
                method="POST"
                action="{{ route('google-workspace.disconnect') }}"
                data-confirm-message="Google Workspace連携を解除しますか？"
            >
                @csrf
                @method('DELETE')
                <button class="gw-icon-button gw-icon-button--danger" type="submit">
                    <span>連携解除</span>
                </button>
            </form>
        @endif
    </div>
</section>
