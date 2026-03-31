const ROLE_DASHBOARDS = {
    'admin':            '/courier_cms/frontend/pages/admin/dashboard.html',
    'customer_service': '/courier_cms/frontend/pages/customer_service/dashboard.html',
    'dispatch':         '/courier_cms/frontend/pages/dispatch/dashboard.html',
    'warehouse':        '/courier_cms/frontend/pages/warehouse/dashboard.html',
    'driver':           '/courier_cms/frontend/pages/driver/dashboard.html',
    'customer':         '/courier_cms/frontend/pages/customer/dashboard.html'
};

async function checkAuth(requiredRole = null) {
    try {
        const res = await API.auth.check();

        if (!res.data || !res.data.user_id) {
            redirectToLogin();
            return null;
        }

        if (requiredRole) {
            const allowed = Array.isArray(requiredRole)
                ? requiredRole
                : [requiredRole];

            if (!allowed.includes(res.data.role)) {
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

function redirectToLogin() {
    window.location.href = '/courier_cms/frontend/pages/login.html';
}

function populateSidebar(user) {
    const avatar = document.getElementById('userAvatar');
    if (avatar) avatar.textContent = getInitials(user.full_name);

    const name = document.getElementById('userName');
    if (name) name.textContent = user.full_name;

    const role = document.getElementById('userRole');
    if (role) role.textContent = user.role.replace('_', ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

async function logout() {
    try {
        await API.auth.logout();
    } catch(e) {
        // Continue regardless
    }
    redirectToLogin();
}

async function handleLogin(event) {
    event.preventDefault();

    const form      = event.target;
    const username  = form.username.value.trim();
    const password  = form.password.value;
    const role      = form.role.value;
    const submitBtn = form.querySelector('[type="submit"]');

    if (!username || !password || !role) {
        showAlert('loginAlert', 'Please fill in all fields.', 'error');
        return;
    }

    setLoading(submitBtn, true);

    try {
        const res = await API.auth.login(username, password, role);

        if (res.status === 'success') {
            window.location.href = ROLE_DASHBOARDS[res.data.role]
                || '/courier_cms/frontend/pages/login.html';
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