import './bootstrap';
import { initDashboardPage } from './pages/dashboard';
import { initAttendancePage } from './pages/attendance';
import { initLeavePage } from './pages/leave';
import { initClaimsPage } from './pages/claims';
import { initProfilePage } from './pages/profile';
import { initTasksPage } from './pages/tasks';
import { initTeamPage } from './pages/team';
import { initAssetsPage } from './pages/assets';
import { initReportsPage } from './pages/reports';
import { initAdminPage } from './pages/admin';
import { initAuthLoginPage } from './pages/auth-login';
import { initAuthForgotPasswordPage } from './pages/auth-forgot-password';
import { initAuthResetPasswordPage } from './pages/auth-reset-password';
import { initAuthLogoutPage } from './pages/auth-logout';
import { clearGlobalFeedback, showGlobalFeedback } from './ui/feedback';

document.addEventListener('DOMContentLoaded', () => {
    const themeStorageKey = 'workpulse-theme';
    const root = document.documentElement;
    const themeToggles = document.querySelectorAll('[data-theme-toggle]');

    const applyTheme = (theme) => {
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
        localStorage.setItem(themeStorageKey, theme);
        document.querySelectorAll('[data-theme-toggle-label]').forEach((label) => {
            label.textContent = theme === 'light' ? 'Light Mode' : 'Dark Mode';
        });
    };

    themeToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'light' ? 'dark' : 'light';
            applyTheme(nextTheme);
        });
    });

    applyTheme(root.dataset.theme === 'light' ? 'light' : 'dark');

    const sidebar = document.querySelector('#appSidebar');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');

    if (sidebar && toggles.length > 0) {
        const closeSidebar = () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('wp-sidebar-hidden');
                return;
            }

            sidebar.classList.add('wp-sidebar-hidden');
            toggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
        };

        const openSidebar = () => {
            sidebar.classList.remove('wp-sidebar-hidden');
            toggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'true'));
        };

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                if (sidebar.classList.contains('wp-sidebar-hidden')) {
                    openSidebar();
                    return;
                }

                closeSidebar();
            });
        });

        window.addEventListener('resize', closeSidebar);
        closeSidebar();
    }

    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target)) {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const focusFirstInModal = (modal) => {
        const focusable = Array.from(modal.querySelectorAll(focusableSelector));
        (focusable[0] || modal).focus();
    };

    const setModalState = (modal, isOpen) => {
        modal.classList.toggle('is-open', isOpen);
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen) {
            modal.dataset.lastActiveElement = document.activeElement instanceof HTMLElement ? '1' : '';
            focusFirstInModal(modal);
        }
    };

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => setModalState(modal, false));
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                setModalState(modal, false);
            }
        });
    });

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.getAttribute('data-modal-open'));
            if (target) {
                setModalState(target, true);
            }
        });
    });

    document.querySelectorAll('[data-tab-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const key = trigger.getAttribute('data-tab-trigger');
            const group = trigger.closest('[role="tablist"]');

            if (!group || !key) {
                return;
            }

            group.querySelectorAll('[data-tab-trigger]').forEach((item) => {
                item.classList.toggle('wp-tab-active', item === trigger);
                item.setAttribute('aria-selected', item === trigger ? 'true' : 'false');
            });

            const parent = group.parentElement;
            parent?.querySelectorAll('[data-tab-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== key);
            });
        });
    });

    document.addEventListener('keydown', (event) => {
        const openModal = document.querySelector('[data-modal].is-open');
        if (!(openModal instanceof HTMLElement)) {
            return;
        }

        if (event.key === 'Escape') {
            setModalState(openModal, false);
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(openModal.querySelectorAll(focusableSelector));
        if (!focusable.length) {
            event.preventDefault();
            openModal.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    });

    const page = document.body.dataset.page || '';

    if (page === 'dashboard') {
        initDashboardPage();
    }

    if (page === 'attendance') {
        initAttendancePage();
    }

    if (page === 'leave') {
        initLeavePage();
    }

    if (page === 'claims') {
        initClaimsPage();
    }

    if (page === 'profile') {
        initProfilePage();
    }

    if (page === 'tasks') {
        initTasksPage();
    }

    if (page === 'team') {
        initTeamPage();
    }

    if (page === 'assets') {
        initAssetsPage();
    }

    if (page === 'report') {
        initReportsPage();
    }

    if (page === 'admin') {
        initAdminPage();
    }

    if (page === 'login') {
        initAuthLoginPage();
    }

    if (page === 'forgot-password') {
        initAuthForgotPasswordPage();
    }

    if (page === 'reset-password') {
        initAuthResetPasswordPage();
    }

    if (page === 'logout') {
        initAuthLogoutPage();
    }

    window.addEventListener('workpulse:unauthorized', () => {
        showGlobalFeedback('Your session has expired. Please sign in again.', 'warning', {
            persist: true,
            clearAfter: 0,
        });
    });

    window.addEventListener('workpulse:slow-network', () => {
        showGlobalFeedback('The connection is slow. WorkPulse is still trying to load data.', 'warning', {
            persist: true,
            clearAfter: 0,
        });
    });

    window.addEventListener('workpulse:network-restored', () => {
        clearGlobalFeedback();
    });
});
