/**
 * 画面遷移開始を示す細い進捗バーを表示する。
 *
 * @returns {void}
 */
function showNavigationProgress() {
    let progress = document.querySelector('[data-workspace-progress]');

    if (!(progress instanceof HTMLElement)) {
        progress = document.createElement('div');
        progress.className = 'gw-navigation-progress';
        progress.dataset.workspaceProgress = '';
        document.body.append(progress);
    }

    window.requestAnimationFrame(() => progress.classList.add('is-active'));
}

/**
 * 共通進捗バーを非表示にする。
 *
 * @returns {void}
 */
function hideNavigationProgress() {
    const progress = document.querySelector('[data-workspace-progress]');

    if (progress instanceof HTMLElement) {
        progress.classList.remove('is-active');
    }
}

/**
 * 開いているポップアップ型detailsを、対象外クリックまたはEscで閉じる。
 *
 * @param {EventTarget|null} target
 * @returns {void}
 */
function closeOpenDisclosures(target = null) {
    document.querySelectorAll('details[data-disclosure][open]').forEach((details) => {
        if (!(details instanceof HTMLDetailsElement)) {
            return;
        }

        if (target instanceof Node && details.contains(target)) {
            return;
        }

        details.open = false;
    });
}

/**
 * Chat入力欄を本文量に合わせて伸縮し、送信欄内の不要なスクロールを減らす。
 *
 * @param {HTMLTextAreaElement} textarea
 * @returns {void}
 */
function resizeChatTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(textarea.scrollHeight, 150)}px`;
}

/**
 * 非同期挿入されたGoogle Workspace要素へ、表示後に必要な初期処理を適用する。
 *
 * @param {ParentNode} scope
 * @returns {void}
 */
function initializeWorkspaceContent(scope = document) {
    scope.querySelectorAll('.gw-chat-compose textarea').forEach((textarea) => {
        if (!(textarea instanceof HTMLTextAreaElement) || textarea.dataset.workspaceInitialized === '1') {
            return;
        }

        textarea.dataset.workspaceInitialized = '1';
        textarea.addEventListener('input', () => resizeChatTextarea(textarea));
        resizeChatTextarea(textarea);
    });

    const messagePane = scope.querySelector('[data-chat-messages]');

    if (messagePane instanceof HTMLElement) {
        messagePane.scrollTop = messagePane.scrollHeight;
    }
}

/**
 * 通信自体に失敗した場合の最低限のエラー表示を生成する。
 * サーバー側でGoogle API例外をHTMLへ変換できた場合はこの表示を使用しない。
 *
 * @returns {string}
 */
function networkErrorMarkup() {
    return `
        <section class="gw-async-state gw-async-state--error" role="alert">
            <div>
                <strong>Googleデータを読み込めませんでした</strong>
                <p>通信状態を確認して、ページを再読み込みしてください。</p>
            </div>
        </section>
    `;
}


/**
 * 非同期領域のHTMLを置換し、Chatのスクロール位置を必要に応じて維持する。
 *
 * @param {HTMLElement} region
 * @param {string} html
 * @param {boolean} preserveMessageScroll
 * @returns {void}
 */
function replaceWorkspaceRegion(region, html, preserveMessageScroll = false) {
    const oldPane = region.querySelector('[data-chat-messages]');
    const oldScrollTop = oldPane instanceof HTMLElement ? oldPane.scrollTop : 0;
    const wasNearBottom = oldPane instanceof HTMLElement
        ? oldPane.scrollHeight - oldPane.clientHeight - oldPane.scrollTop < 80
        : false;

    region.innerHTML = html;
    initializeWorkspaceContent(region);

    if (!preserveMessageScroll) {
        return;
    }

    const newPane = region.querySelector('[data-chat-messages]');

    if (newPane instanceof HTMLElement) {
        newPane.scrollTop = wasNearBottom ? newPane.scrollHeight : oldScrollTop;
    }
}

/**
 * stale表示後に同じ断片をfresh-onlyで再取得する。
 * 再検証に失敗した場合は、利用可能なstale表示を消さずそのまま維持する。
 *
 * @param {HTMLElement} region
 * @param {string} url
 * @returns {Promise<void>}
 */
async function revalidateWorkspaceRegion(region, url) {
    const refreshUrl = new URL(url, window.location.origin);
    refreshUrl.searchParams.delete('refresh');
    refreshUrl.searchParams.set('swr_refresh', '1');
    region.dataset.workspaceRevalidating = '1';

    try {
        const response = await window.fetch(refreshUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Google-Workspace-Async': '1',
                'X-Google-Workspace-SWR': '1',
            },
        });

        if (response.redirected) {
            window.location.assign(response.url);
            return;
        }

        if (!response.ok || response.headers.get('X-Google-Workspace-Async-Error') === '1') {
            return;
        }

        replaceWorkspaceRegion(region, await response.text(), true);
    } catch (error) {
        // stale値は既に表示済みなので、バックグラウンド再検証失敗で画面をエラーへ置換しない。
        console.warn('Google Workspace SWR refresh failed.', error);
    } finally {
        delete region.dataset.workspaceRevalidating;
    }
}

/**
 * Google APIを伴う表示領域だけを別リクエストで読み込み、ページ本体の初期表示を待たせない。
 *
 * @param {HTMLElement} region
 * @returns {Promise<void>}
 */
async function loadWorkspaceRegion(region) {
    const url = region.dataset.workspaceAsyncUrl;

    if (url === undefined || url === '') {
        return;
    }

    region.setAttribute('aria-busy', 'true');

    try {
        const response = await window.fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Google-Workspace-Async': '1',
            },
        });

        if (response.redirected) {
            window.location.assign(response.url);
            return;
        }

        if (!response.ok) {
            throw new Error(`Google Workspace async request failed: ${response.status}`);
        }

        const html = await response.text();
        replaceWorkspaceRegion(region, html);

        if (response.headers.get('X-Google-Workspace-Stale') === '1') {
            void revalidateWorkspaceRegion(region, url);
        }

        const currentUrl = new URL(window.location.href);

        if (currentUrl.searchParams.has('refresh')) {
            currentUrl.searchParams.delete('refresh');
            window.history.replaceState(window.history.state, '', currentUrl);
        }
    } catch (error) {
        console.error(error);
        region.innerHTML = networkErrorMarkup();
    } finally {
        region.setAttribute('aria-busy', 'false');
        hideNavigationProgress();
    }
}

document.querySelectorAll('[data-workspace-async-region]').forEach((region) => {
    if (region instanceof HTMLElement) {
        void loadWorkspaceRegion(region);
    }
});

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element
        ? event.target.closest('a[href]')
        : null;

    if (link instanceof HTMLAnchorElement
        && link.origin === window.location.origin
        && !event.defaultPrevented
        && event.button === 0
        && !event.metaKey
        && !event.ctrlKey
        && !event.shiftKey
        && link.target !== '_blank') {
        showNavigationProgress();
    }

    closeOpenDisclosures(event.target);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeOpenDisclosures();
    }
});

initializeWorkspaceContent();

window.addEventListener('pageshow', hideNavigationProgress);
