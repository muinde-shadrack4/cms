/* ============================================================
   auth.js — Authentication Logic
   Handles login, logout, session checking
   Used by login.html and all protected pages
   ============================================================ */

// Role → dashboard URL mapping
const ROLE_DASHBOARDS = {
    'admin':            '/courier_cms_v2/frontend/pages/admin/dashboard.html',
    'customer_service': '/courier_cms_v2/frontend/pages/customer_service/dashboard.html',
    'dispatch':         '/courier_cms_v2/frontend/pages/dispatch/dashboard.html',
    'warehouse':        '/courier_cms_v2/frontend/pages/warehouse/dashboard.html',
    'driver':           '/courier_cms_v2/frontend/pages/driver/dashboard.html',
    'customer':         '/courier_cms_v2/frontend/pages/customer/dashboard.html'
};

// ── CHECK SESSION ─────────────────────────────────────────
// Call on every protected page to verify user is logged in
async function checkAuth(requiredRole = null) {
    try {
        const res = await API.auth.check();

        if (!res.data || !res.data.user_id) {
            redirectToLogin();
            return null;
        }

        // If role required check it matches
        if (requiredRole) {
            const allowed = Array.isArray(requiredRole)
                ? requiredRole
                : [requiredRole];

            if (!allowed.includes(res.data.role)) {
                // Wrong role — go to their own dashboard
                window.location.href = ROLE_DASHBOARDS[res.data.role];
                return null;
            }
        }

        return res.data;

    } catch (err) {
        redirectToLogin();
        return null;
    }
}

// ── REDIRECT TO LOGIN ─────────────────────────────────────
function redirectToLogin() {
    window.location.href = '/courier_cms_v2/frontend/pages/login.html';
}

// ── POPULATE SIDEBAR USER INFO ────────────────────────────
function populateSidebar(user) {
    // Avatar initials
    const avatar = document.getElementById('userAvatar');
    if (avatar) avatar.textContent = getInitials(user.full_name);

    // Name and role
    const name = document.getElementById('userName');
    if (name) name.textContent = user.full_name;

    const role = document.getElementById('userRole');
    if (role) role.textContent = user.role.replace('_', ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

// ── LOGOUT ────────────────────────────────────────────────
async function logout() {
    try {
        await API.auth.logout();
    } catch(e) {
        // Continue regardless
    }
    redirectToLogin();
}

// ── LOGIN FORM HANDLER ────────────────────────────────────
async function handleLogin(event) {
    event.preventDefault();

    const form       = event.target;
    const username   = form.username.value.trim();
    const password   = form.password.value;
    const role       = form.role.value;
    const submitBtn  = form.querySelector('[type="submit"]');
    const alertEl    = document.getElementById('loginAlert');

    // Basic validation
    if (!username || !password || !role) {
        showAlert('loginAlert', 'Please fill in all fields.', 'error');
        return;
    }

    setLoading(submitBtn, true);

    try {
        const res = await API.auth.login(username, password, role);

        if (res.status === 'success') {
            // Redirect to correct dashboard
            window.location.href = ROLE_DASHBOARDS[res.data.role]
                || '/courier_cms_v2/frontend/pages/login.html';
        } else {
            showAlert('loginAlert', res.message || 'Login failed.', 'error');
            setLoading(submitBtn, false);
        }

    } catch (err) {
        showAlert('loginAlert',
            'Invalid username, password or role.', 'error');
        setLoading(submitBtn, false);
    }
}