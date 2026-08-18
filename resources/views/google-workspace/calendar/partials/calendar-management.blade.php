@if ($selectedCalendar !== null && $selectedCalendar->canManage())
    <details class="gw-panel">
        <summary>選択カレンダーを管理</summary>
        <form
            class="gw-form"
            method="POST"
            action="{{ route('google-workspace.calendar.calendars.update', $selectedCalendar->id) }}"
        >
            @csrf
            @method('PUT')
            <label>
            <span>名称</span>
            <input type="text" name="summary" value="{{ $selectedCalendar->summary }}" required>
            </label>
            <label>
            <span>説明</span>
            <textarea name="description" rows="3">{{ $selectedCalendar->description }}</textarea>
            </label>
            <label>
            <span>タイムゾーン</span>
            <input
                type="text"
                name="time_zone"
                value="{{ $selectedCalendar->timeZone ?? config('app.timezone') }}"
                required
            >
            </label>
            <button class="gw-button gw-button--primary" type="submit">更新</button>
        </form>
        @unless ($selectedCalendar->primary)
            <form
                class="gw-danger-zone"
                method="POST"
                action="{{ route('google-workspace.calendar.calendars.destroy', $selectedCalendar->id) }}"
                data-confirm-message="このカレンダーを完全に削除しますか？"
            >
                @csrf
                @method('DELETE')
                <button class="gw-button gw-button--danger" type="submit">カレンダーを削除</button>
            </form>
        @endunless
    </details>

    <details class="gw-panel">
        <summary>共有設定</summary>
        <form
            class="gw-form"
            method="POST"
            action="{{ route('google-workspace.calendar.acl.store', $selectedCalendar->id) }}"
        >
            @csrf
            <label><span>メールアドレス</span><input type="email" name="email" required></label>
            <label>
                <span>権限</span>
                <select name="role" required>
                    <option value="freeBusyReader">予定あり・なしのみ</option>
                    <option value="reader">閲覧者</option>
                    <option value="writer">編集者</option>
                    <option value="owner">管理者</option>
                </select>
            </label>
            <label
                class="gw-check"
            ><input type="checkbox" name="send_notification" value="1" checked><span>通知メールを送る</span></label>
            <button class="gw-button gw-button--primary" type="submit">共有を追加</button>
        </form>
        <div class="gw-compact-list">
            @forelse ($aclRules as $rule)
                <div>
                    <span>
                    <strong>{{ $rule->scopeValue ?? $rule->scopeType }}</strong>
                    <small>{{ $rule->roleLabel() }}</small>
                    </span>
                    <form
                        method="POST"
                        action="{{
                            route('google-workspace.calendar.acl.destroy', [$selectedCalendar->id, $rule->id])
                        }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit">削除</button>
                    </form>
                </div>
            @empty
                <p class="gw-empty">追加の共有設定はありません。</p>
            @endforelse
        </div>
    </details>
@endif
