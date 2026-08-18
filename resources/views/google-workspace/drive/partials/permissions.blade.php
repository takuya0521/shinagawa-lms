<section class="gw-section">
    <div class="gw-section__header"><h2>共有権限</h2><span class="gw-count">{{ $permissions->count() }}件</span></div>
    @if ($file->canShare)
        <form
            class="gw-form gw-form--inline"
            method="POST"
            action="{{ route('google-workspace.drive.permissions.store', $file->id) }}"
        >
            @csrf
            <input type="email" name="email" placeholder="メールアドレス" required>
            <select
                name="role"
            >
            <option value="reader">閲覧者</option>
            <option value="commenter">コメント可</option>
            <option value="writer">編集者</option>
            </select>
            <label
                class="gw-check"
            ><input type="checkbox" name="send_notification" value="1" checked><span>通知</span></label>
            <button class="gw-button gw-button--primary" type="submit">共有</button>
        </form>
    @endif
    <ul class="gw-list">
        @foreach ($permissions as $permission)
            <li>
                <div>
                <strong>{{ $permission->displayName ?? $permission->emailAddress ?? $permission->type }}</strong>
                <span>{{ $permission->roleLabel() }}</span>
                </div>
                @if ($permission->role !== 'owner')
                    <form
                        method="POST"
                        action="{{ route('google-workspace.drive.permissions.destroy', [$file->id, $permission->id]) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit">削除</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
</section>
