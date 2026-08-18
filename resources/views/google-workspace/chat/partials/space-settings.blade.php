@if ($space->isNamedSpace())
    <section class="gw-drawer-section">
        <form
            class="gw-form gw-form--compact"
            method="POST"
            action="{{ route('google-workspace.chat.spaces.update', $space->id) }}"
        >
            @csrf
            @method('PATCH')
            <label>
            <span>名称</span>
            <input type="text" name="display_name" value="{{ $space->displayName }}" required>
            </label>
            <label><span>説明</span><textarea name="description" rows="4">{{ $space->description }}</textarea></label>
            <button class="gw-button gw-button--primary" type="submit">保存</button>
        </form>
        <form
            class="gw-danger-zone"
            method="POST"
            action="{{ route('google-workspace.chat.spaces.destroy', $space->id) }}"
            data-confirm-message="スペースとメッセージを完全に削除しますか？"
        >
            @csrf
            @method('DELETE')
            <button class="gw-button gw-button--danger" type="submit">スペースを削除</button>
        </form>
    </section>
@endif
