const shell = document.querySelector('[data-app-shell]');
const sidebarNav = shell instanceof HTMLElement ? shell.querySelector('.lms-nav') : null;
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const mobileLayout = window.matchMedia('(max-width: 1023px)');
const roleClass = Array.from(document.body.classList).find((className) => className.startsWith('lms-role-'));
const sidebarScrollPrefix = 'lms-sidebar-scroll:';
const sidebarScrollKey = `${sidebarScrollPrefix}${roleClass ?? 'default'}`;

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
    const inputId = button.getAttribute('aria-controls');
    const passwordInput = inputId ? document.getElementById(inputId) : null;

    if (!(button instanceof HTMLButtonElement) || !(passwordInput instanceof HTMLInputElement)) {
        return;
    }

    const shouldShow = passwordInput.type === 'password';
    const label = button.dataset.passwordLabel ?? 'パスワード';

    passwordInput.type = shouldShow ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(shouldShow));
    button.setAttribute(
        'aria-label',
        shouldShow ? `${label}を非表示にする` : `${label}を表示する`,
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

    if (form.matches('.auth-page .auth-form')) {
        try {
            const storage = window.sessionStorage;

            for (const key of Object.keys(storage)) {
                if (key.startsWith(sidebarScrollPrefix)) {
                    storage.removeItem(key);
                }
            }
        } catch {
            // ストレージが使えない環境でもログインを続ける。
        }
    }

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

const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
if (sidebarToggle instanceof HTMLButtonElement && shell instanceof HTMLElement) {
    sidebarToggle.addEventListener('click', () => {
        const collapsed = shell.classList.toggle('is-sidebar-collapsed');
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.setAttribute('aria-label', collapsed ? 'メニューを展開する' : 'メニューを折りたたむ');
    });
}

function animateDashboardNumber(element, delay) {
    const text = Array.from(element.childNodes).find(node =>
        node.nodeType === Node.TEXT_NODE && /^\s*[\d,]+(?:\.\d+)?\s*$/.test(node.textContent)
    );
    if (!text) return;

    const original = text.textContent;
    const target = Number(original.trim().replaceAll(',', ''));
    const decimals = original.trim().split('.')[1]?.length ?? 0;
    const formatter = new Intl.NumberFormat('ja-JP', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    text.textContent = formatter.format(0);

    window.setTimeout(() => {
        const started = performance.now();
        const update = now => {
            const progress = Math.min(1, (now - started) / 700);
            const eased = 1 - Math.pow(1 - progress, 3);
            text.textContent = progress === 1 ? original : formatter.format(target * eased);
            if (progress < 1) window.requestAnimationFrame(update);
        };
        window.requestAnimationFrame(update);
    }, delay);
}

const dashboard = document.querySelector('.dashboard-workspace, .student-dashboard');
if (dashboard && !window.matchMedia('(prefers-reduced-motion: reduce)').matches &&
    typeof IntersectionObserver !== 'undefined') {
    const cards = dashboard.querySelectorAll(
        '.dashboard-stat-grid > a, .dashboard-analytics > section, .dashboard-detail-grid > section, .dashboard-workspace > section.lms-panel, ' +
        '.student-dashboard-overview, .student-dashboard-summary, .student-dashboard-panel, .student-dashboard-services'
    );
    const observer = new IntersectionObserver(entries => {
        let stagger = 0;
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const card = entry.target;
            observer.unobserve(card);
            const delay = stagger++ * 65;
            card.animate([
                { opacity: 0, transform: 'translateY(16px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ], { duration: 420, delay, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'backwards' });

            for (const bar of card.querySelectorAll('.dashboard-bar-track > span')) {
                bar.animate([
                    { transform: 'scaleY(0)' },
                    { transform: 'scaleY(1)' },
                ], { duration: 650, delay: delay + 100, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'backwards' });
                bar.style.transformOrigin = 'bottom';
            }
            for (const ring of card.querySelectorAll('.dashboard-progress-ring, .dashboard-attendance-ring')) {
                const target = ring.style.getPropertyValue('--dashboard-rate').trim();
                ring.animate([
                    { '--dashboard-fill': '0%' },
                    { '--dashboard-fill': target },
                ], { duration: 850, delay: delay + 120, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'backwards' });
            }
            const numbers = card.querySelectorAll(
                '.dashboard-progress-ring > strong, .student-dashboard-evaluation__main > strong, ' +
                '.student-dashboard-attendance__main > strong'
            );
            for (const number of numbers) animateDashboardNumber(number, delay + 100);
            if (card.matches('.dashboard-stat-grid > a')) {
                const number = card.querySelector('p:nth-child(2)');
                if (number) animateDashboardNumber(number, delay + 100);
            }
        }
    }, { threshold: 0.1 });
    cards.forEach(card => observer.observe(card));
}

const dashboardThemeToggle = document.querySelector('[data-dashboard-theme]');
if (dashboardThemeToggle instanceof HTMLButtonElement) {
    const applyDashboardTheme = dark => {
        document.body.classList.toggle('dashboard-dark', dark);
        dashboardThemeToggle.setAttribute('aria-pressed', String(dark));
        dashboardThemeToggle.setAttribute('aria-label', dark ? 'ライト表示に切り替える' : 'ダーク表示に切り替える');
        dashboardThemeToggle.querySelector('[data-dashboard-theme-label]').textContent = dark ? 'ライト表示' : 'ダーク表示';
    };
    try {
        applyDashboardTheme(localStorage.getItem('lms-dashboard-theme') === 'dark');
    } catch { applyDashboardTheme(false); }
    dashboardThemeToggle.addEventListener('click', () => {
        const dark = !document.body.classList.contains('dashboard-dark');
        applyDashboardTheme(dark);
        try { localStorage.setItem('lms-dashboard-theme', dark ? 'dark' : 'light'); } catch {}
    });
}
