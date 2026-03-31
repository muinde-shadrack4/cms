const API = {

    // ✅ Fixed project name
    BASE: '/courier_cms/api/endpoints',

    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json'
            },
            credentials: 'include'
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.BASE}/${endpoint}`, options);
            const json     = await response.json();

            if (!response.ok) {
                throw new Error(json.message || 'Server error');
            }

            return json;

        } catch (error) {
            console.error(`API Error [${method} ${endpoint}]:`, error);
            throw error;
        }
    },

    auth: {
        login(username, password, role) {
            return API.request('auth.php', 'POST', { username, password, role });
        },
        logout() {
            return API.request('auth.php?action=logout', 'POST');
        },
        check() {
            return API.request('auth.php?action=check', 'GET');
        }
    },

    customers: {
        getAll() {
            return API.request('customers.php', 'GET');
        },
        create(data) {
            return API.request('customers.php', 'POST', data);
        },
        getById(id) {
            return API.request(`customers.php?id=${id}`, 'GET');
        }
    },

    parcels: {
        getAll(filters = {}) {
            const params = new URLSearchParams(filters).toString();
            return API.request(`parcels.php${params ? '?' + params : ''}`, 'GET');
        },
        create(data) {
            return API.request('parcels.php', 'POST', data);
        },
        getByTracking(tracking) {
            return API.request(`parcels.php?tracking=${tracking}`, 'GET');
        },
        getByCustomer(customerId) {
            return API.request(`parcels.php?customer_id=${customerId}`, 'GET');
        }
    },

    tracking: {
        getHistory(parcelId) {
            return API.request(`tracking.php?parcel_id=${parcelId}`, 'GET');
        },
        update(data) {
            return API.request('tracking.php', 'POST', data);
        }
    },

    users: {
        getAll(role = null) {
            const query = role ? `?role=${role}` : '';
            return API.request(`users.php${query}`, 'GET');
        },
        create(data) {
            return API.request('users.php', 'POST', data);
        },
        updateStatus(userId, status) {
            return API.request('users.php', 'PUT', { user_id: userId, status });
        },
        getDrivers() {
            return API.request('users.php?role=driver&status=active', 'GET');
        }
    },

    vehicles: {
        getAll() {
            return API.request('vehicles.php', 'GET');
        },
        create(data) {
            return API.request('vehicles.php', 'POST', data);
        },
        updateStatus(vehicleId, status) {
            return API.request('vehicles.php', 'PUT', { vehicle_id: vehicleId, status });
        }
    },

    inventory: {
        getAll(search = '') {
            const query = search ? `?search=${search}` : '';
            return API.request(`inventory.php${query}`, 'GET');
        },
        add(parcelId, shelfLocation) {
            return API.request('inventory.php', 'POST',
                { parcel_id: parcelId, shelf_location: shelfLocation });
        },
        remove(inventoryId) {
            return API.request('inventory.php', 'DELETE', { inventory_id: inventoryId });
        },
        getAvailable() {
            return API.request('inventory.php?available=true', 'GET');
        }
    },

    dispatch: {
        getPending() {
            return API.request('dispatch.php?status=pending', 'GET');
        },
        assign(data) {
            return API.request('dispatch.php', 'POST', data);
        },
        getRecent() {
            return API.request('dispatch.php?recent=true', 'GET');
        },
        getByDriver(driverId) {
            return API.request(`dispatch.php?driver_id=${driverId}`, 'GET');
        }
    },

    reports: {
        getSummary(from, to) {
            return API.request(`reports.php?from=${from}&to=${to}`, 'GET');
        },
        getByZone(from, to) {
            return API.request(`reports.php?type=zone&from=${from}&to=${to}`, 'GET');
        },
        getTopDrivers() {
            return API.request('reports.php?type=drivers', 'GET');
        }
    }
};

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-KE', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}

function formatKES(amount) {
    return 'KES ' + parseFloat(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2
    });
}

function statusBadge(status) {
    const cls = status.toLowerCase().replace(/\s+/g, '-');
    return `<span class="badge badge-${cls}">${status}</span>`;
}

function roleBadge(role) {
    return `<span class="badge badge-${role.replace('_','-')}">${
        role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
    }</span>`;
}

function showAlert(elementId, message, type = 'success') {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.className = `alert alert-${type} show`;
    el.textContent = message;
    setTimeout(() => el.classList.remove('show'), 5000);
}

function setLoading(btn, loading = true) {
    if (loading) {
        btn.dataset.original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span> Loading...';
        btn.disabled = true;
    } else {
        btn.innerHTML = btn.dataset.original;
        btn.disabled = false;
    }
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function trackingBadge(tracking) {
    return `<span class="tracking-no">${tracking}</span>`;
}
