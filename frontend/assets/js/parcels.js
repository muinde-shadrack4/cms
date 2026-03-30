/* ============================================================
   parcels.js — Parcel UI Logic
   Used by: customer_service/register_parcel.html
   ============================================================ */

// ── PRICING ALGORITHM ─────────────────────────────────────
// Mirrors backend Parcel::calculatePrice()
function calculatePrice(weight, zone, service) {
    let base = 0;

    if (weight <= 1)        base = 200;
    else if (weight <= 5)   base = 200 + ((weight - 1) * 50);
    else if (weight <= 10)  base = 400 + ((weight - 5) * 80);
    else                    base = 800 + ((weight - 10) * 100);

    const zoneMultipliers = {
        CBD: 1.0, Westlands: 1.2, Eastlands: 1.3, Satellite: 1.5
    };
    base *= zoneMultipliers[zone] || 1.0;

    if (service === 'express')  base *= 1.5;
    if (service === 'same-day') base *= 1.3;

    return Math.max(Math.round(base * 100) / 100, 150);
}

// ── LOAD PARCELS INTO TABLE ───────────────────────────────
async function loadParcels(tbodyId = 'parcelsTable',
                           filters = {}) {
    try {
        const res    = await API.parcels.getAll(filters);
        const tbody  = document.getElementById(tbodyId);
        const parcels = res.data || [];

        if (!parcels.length) {
            tbody.innerHTML = `<tr><td colspan="6">
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>No parcels found</h3>
                </div></td></tr>`;
            return parcels;
        }

        tbody.innerHTML = parcels.map(p => `
            <tr>
                <td>${trackingBadge(p.tracking_number)}</td>
                <td>${p.customer_name || '—'}</td>
                <td>${p.recipient_name}</td>
                <td>${formatKES(p.price)}</td>
                <td>${statusBadge(p.status)}</td>
                <td>${formatDate(p.date_registered)}</td>
            </tr>
        `).join('');

        return parcels;
    } catch(e) {
        console.error('loadParcels error:', e);
        return [];
    }
}

// ── LOAD CUSTOMERS INTO SELECT ────────────────────────────
async function loadCustomersSelect(selectId = 'customerSelect') {
    try {
        const res = await API.customers.getAll();
        const sel = document.getElementById(selectId);
        if (!sel) return;

        sel.innerHTML = '<option value="">-- Select Customer --</option>';
        (res.data || []).forEach(c => {
            const opt       = document.createElement('option');
            opt.value       = c.customer_id;
            opt.textContent = `${c.name} (${c.phone})`;
            sel.appendChild(opt);
        });
    } catch(e) {
        console.error('loadCustomersSelect error:', e);
    }
}

// ── HANDLE PARCEL BOOKING FORM ────────────────────────────
async function handleBookParcel(event,
                                 successAlertId = 'alertSuccess',
                                 errorAlertId   = 'alertError',
                                 btnId          = 'bookBtn') {
    event.preventDefault();
    const form = event.target;
    const btn  = document.getElementById(btnId);

    setLoading(btn, true);

    try {
        const res = await API.parcels.create({
            customer_id:       parseInt(form.customer_id.value),
            recipient_name:    form.recipient_name.value.trim(),
            recipient_phone:   form.recipient_phone.value.trim(),
            recipient_address: form.recipient_address.value.trim(),
            weight:            parseFloat(form.weight.value),
            zone:              form.zone.value,
            service_type:      form.service_type.value
        });

        if (res.status === 'success') {
            showAlert(successAlertId,
                `Parcel booked! Tracking: ${res.data.tracking_number}
                 — ${formatKES(res.data.price)}`,
                'success');
            form.reset();
            hidePricePreview();
            await loadParcels();
        } else {
            showAlert(errorAlertId, res.message, 'error');
        }
    } catch(e) {
        showAlert(errorAlertId, 'Failed to book parcel.', 'error');
    }

    setLoading(btn, false);
}

// ── PRICE PREVIEW ─────────────────────────────────────────
function updatePricePreview(weightId   = 'weightInput',
                             zoneId     = 'zoneSelect',
                             serviceId  = 'serviceSelect',
                             previewId  = 'pricePreview',
                             valueId    = 'priceValue') {
    const weight  = parseFloat(
        document.getElementById(weightId)?.value || 0);
    const zone    = document.getElementById(zoneId)?.value;
    const service = document.getElementById(serviceId)?.value;
    const preview = document.getElementById(previewId);
    const value   = document.getElementById(valueId);

    if (!weight || weight <= 0 || !zone || !service) {
        if (preview) preview.style.display = 'none';
        return;
    }

    const price = calculatePrice(weight, zone, service);
    if (value)   value.textContent     = formatKES(price);
    if (preview) preview.style.display = 'block';
}

function hidePricePreview(previewId = 'pricePreview') {
    const el = document.getElementById(previewId);
    if (el) el.style.display = 'none';
}

// ── RENDER PARCEL ROW ─────────────────────────────────────
function renderParcelRow(parcel) {
    return `
        <tr>
            <td>${trackingBadge(parcel.tracking_number)}</td>
            <td>${parcel.customer_name || '—'}</td>
            <td>${parcel.recipient_name}</td>
            <td>${parcel.zone}</td>
            <td>${formatKES(parcel.price)}</td>
            <td>${statusBadge(parcel.status)}</td>
            <td>${formatDate(parcel.date_registered)}</td>
        </tr>
    `;
}