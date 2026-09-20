// assets/js/auth.js

function toggleModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.toggle('hidden');
}

function switchTab(type) {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const loginTab = document.getElementById('loginTabBtn');
    const signupTab = document.getElementById('signupTabBtn');

    if (type === 'login') {
        loginForm.classList.remove('hidden');
        signupForm.classList.add('hidden');
        loginTab.className = 'w-1/2 pb-3 font-bold text-blue-600 border-b-2 border-blue-600 text-center cursor-pointer';
        signupTab.className = 'w-1/2 pb-3 font-medium text-gray-400 text-center cursor-pointer';
    } else {
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');
        signupTab.className = 'w-1/2 pb-3 font-bold text-blue-600 border-b-2 border-blue-600 text-center cursor-pointer';
        loginTab.className = 'w-1/2 pb-3 font-medium text-gray-400 text-center cursor-pointer';
    }
}

function showAuthToast(title, message, isError = false) {
    const toast = document.getElementById('authToast');
    if (!toast) return;

    document.getElementById('authToastTitle').textContent = title;
    document.getElementById('authToastMessage').textContent = message;
    document.getElementById('authToastIcon').textContent = isError ? '!' : '✓';
    toast.classList.toggle('is-error', isError);
    toast.classList.remove('hidden');

    window.clearTimeout(window.authToastTimer);
    window.authToastTimer = window.setTimeout(() => toast.classList.add('hidden'), 5000);
}

async function submitAuthForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Please wait...';

    try {
        const response = await fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        });

        // A non-AJAX PHP response may redirect during fetch without navigating the page.
        if (
            response.redirected &&
            response.url !== new URL(form.getAttribute('action'), window.location.href).href
        ) {
            window.location.replace(response.url);
            return;
        }

        const responseText = await response.text();
        let data;

        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            const serverMessage = responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            throw new Error(serverMessage || `Server returned HTTP ${response.status}.`);
        }

        if (!response.ok || !data.success) {
            showAuthToast('Unable to continue', data.message || 'Please check your details and try again.', true);
            return;
        }

        showAuthToast(data.title || 'Success', data.message, false);

        if (data.redirect) {
            window.setTimeout(() => { window.location.replace(data.redirect); }, 1400);
        } else if (form.id === 'signupForm') {
            form.reset();
            window.setTimeout(() => switchTab('login'), 1400);
        }
    } catch (error) {
        console.error('EduLend authentication error:', error);
        showAuthToast(
            'Authentication problem',
            error.message || 'We could not reach EduLend. Please try again.',
            true
        );
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.textContent = showing ? '◉' : '○';
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    });

    const roleInput = document.getElementById('roleInput');
    const roleHint = document.getElementById('roleHint');
    if (roleInput && roleHint) {
        roleInput.addEventListener('change', () => {
            roleHint.textContent = roleInput.value === 'student'
                ? 'Request education loans, build your trust profile, and manage repayments.'
                : roleInput.value === 'lender'
                    ? 'Fund verified student loans and monitor your investment returns.'
                    : 'Choose how you will use EduLend.';
        });
    }

    document.querySelectorAll('#loginForm, #signupForm').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            submitAuthForm(form);
        });
    });

    const toastClose = document.getElementById('authToastClose');
    if (toastClose) toastClose.addEventListener('click', () => document.getElementById('authToast').classList.add('hidden'));

    window.addEventListener('click', event => {
        const modal = document.getElementById('loginModal');
        if (event.target === modal) modal.classList.add('hidden');
    });
});
