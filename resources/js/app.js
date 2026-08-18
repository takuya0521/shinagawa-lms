const shell = document.querySelector('[data-app-shell]');
const sidebarNav = shell instanceof HTMLElement ? shell.querySelector('.lms-nav') : null;
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const mobileLayout = window.matchMedia('(max-width: 1023px)');
const roleClass = Array.from(document.body.classList).find((className) => className.startsWith('lms-role-'));
const sidebarScrollKey = `lms-sidebar-scroll:${roleClass ?? 'default'}`;

function setSidebarOpen(isOpen) {
    if (!(shell instanceof HTMLElement) || !(sidebarOpenButton instanceof HTMLButtonElement)) {
        return;
    }

    shell.classList.toggle('is-sidebar-open', isOpen);
    sidebarOpenButton.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('is-menu-locked', isOpen && mobileLayout.matches);
}

function saveSidebarScroll() {
    if (!(sidebarNav instanceof HTMLElement)) {
        return;
    }

    try {
        window.sessionStorage.setItem(sidebarScrollKey, String(sidebarNav.scrollTop));
    } catch {
        return;
    }
}

function restoreSidebarScroll() {
    if (!(sidebarNav instanceof HTMLElement)) {
        return;
    }

    let storedValue = null;

    try {
        const parsedValue = Number.parseFloat(window.sessionStorage.getItem(sidebarScrollKey) ?? '');
        storedValue = Number.isFinite(parsedValue) ? parsedValue : null;
    } catch {
        storedValue = null;
    }

    if (storedValue === null) {
        return;
    }

    const maximumScrollTop = Math.max(0, sidebarNav.scrollHeight - sidebarNav.clientHeight);
    sidebarNav.scrollTop = Math.min(storedValue, maximumScrollTop);
}

function togglePasswordVisibility(button) {
    const passwordInput = document.querySelector('#password');

    if (!(button instanceof HTMLButtonElement) || !(passwordInput instanceof HTMLInputElement)) {
        return;
    }

    const shouldShow = passwordInput.type === 'password';

    passwordInput.type = shouldShow ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(shouldShow));
    button.setAttribute(
        'aria-label',
        shouldShow ? 'パスワードを非表示にする' : 'パスワードを表示する',
    );
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const passwordToggle = event.target.closest('[data-password-toggle]');

    if (passwordToggle instanceof HTMLButtonElement) {
        togglePasswordVisibility(passwordToggle);
        return;
    }

    if (event.target.closest('[data-history-back]') instanceof HTMLButtonElement) {
        window.history.back();
        return;
    }

    if (event.target.closest('[data-sidebar-open]') instanceof HTMLButtonElement) {
        setSidebarOpen(true);
        return;
    }

    if (event.target.closest('[data-sidebar-close]') instanceof HTMLElement) {
        setSidebarOpen(false);
        return;
    }

    if (event.target.closest('.lms-sidebar a[href]') instanceof HTMLAnchorElement) {
        saveSidebarScroll();
    }
});

document.addEventListener('submit', (event) => {
    if (!(event.target instanceof HTMLFormElement)) {
        return;
    }

    const form = event.target;
    const message = form.dataset.confirmMessage;

    if (message === undefined) {
        return;
    }

    const selectName = form.dataset.confirmEmptySelect;

    if (selectName !== undefined) {
        const select = form.elements.namedItem(selectName);

        if (!(select instanceof HTMLSelectElement) || select.value !== '') {
            return;
        }
    }

    if (!window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarOpen(false);
    }
});

mobileLayout.addEventListener('change', (event) => {
    if (!event.matches) {
        setSidebarOpen(false);
    }
});

window.requestAnimationFrame(restoreSidebarScroll);
