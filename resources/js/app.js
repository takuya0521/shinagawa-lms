document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-app-shell]');
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeTargets = document.querySelectorAll('[data-sidebar-close]');

    // ログイン画面やエラー画面にはサイドバーがないため、対象要素がない場合は処理しない。
    if (!(shell instanceof HTMLElement) || !(openButton instanceof HTMLButtonElement)) {
        return;
    }

    const setOpen = (isOpen) => {
        shell.classList.toggle('is-sidebar-open', isOpen);
        openButton.setAttribute('aria-expanded', String(isOpen));

        // サイドバー表示中の背景スクロールを止め、操作対象が移動しないようにする。
        document.body.classList.toggle('is-menu-locked', isOpen);
    };

    openButton.addEventListener('click', () => setOpen(true));
    closeTargets.forEach((target) => target.addEventListener('click', () => setOpen(false)));

    // 画面遷移前に開閉状態を戻し、ブラウザ履歴から復帰した際の表示崩れを防ぐ。
    shell.querySelectorAll('.lms-nav-link').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    // PC幅へ戻った際にモバイル用の開閉状態を残さない。
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            setOpen(false);
        }
    });
});
