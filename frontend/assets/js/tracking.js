/* ============================================================
   tracking.js — Parcel Tracking Logic
   Used by: driver/update_status.html
            customer/track.html
            customer_service/track.html
   ============================================================ */

// ── SEARCH AND DISPLAY PARCEL ─────────────────────────────
async function searchParcel(tracking,
                             resultId  = 'resultSection',
                             notFoundId = 'notFound',
                             errorId   = 'alertError') {
    if (!tracking) return;

    tracking = tracking.toUpperCase().trim();

    // Hide previous results
    const result   = document.getElementById(resultId);
    const notFound = document.getElementById(notFoundId);
    if (result)   result.style.display   = 'none';
    if (notFound) notFound.style.display = 'none';

    try {
        const res = await API.parcels.getByTracking(tracking);

        if (!res.data) {
            if (notFound) notFound.style.display = 'block';
            return null;
        }

        const parcel = res.data;

        // Render parcel details
        renderParcelDetails(parcel);

        // Load tracking history
        const histRes = await API.tracking.getHistory(parcel.parcel_id);
        renderTimeline(histRes.data || []);

        if (result) result.style.display = 'block';
        return parcel;

    } catch(e) {
        if (notFound) notFound.style.display = 'block';
        return null;
    }
}

// ── RENDER PARCEL DETAILS CARD ────────────────────────────
function renderParcelDetails(parcel,
                              detailsId = 'parcelDetails',
                              badgeId   = 'statusBadgeEl') {
    const details = document.getElementById(detailsId);
    const badge   = document.getElementById(badgeId);

    if (badge) {
        badge.innerHTML = `
            <span class="badge badge-${
                parcel.status.toLowerCase().replace(/\s+/g, '-')}"
                  style="font-size:13px;padding:6px 14px;">
                ${parcel.status}
            </span>`;
    }

    if (details) {
        details.innerHTML = `
            <div class="detail-item">
                <div class="detail-label">Tracking Number</div>
                <div class="detail-value"
                     style="font-family:var(--font-mono);font-size:13px;">
                    ${parcel.tracking_number}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Recipient</div>
                <div class="detail-value">${parcel.recipient_name}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Delivery Address</div>
                <div class="detail-value">${parcel.recipient_address}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Zone</div>
                <div class="detail-value">${parcel.zone}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Service Type</div>
                <div class="detail-value">
                    ${parcel.service_type.charAt(0).toUpperCase()
                      + parcel.service_type.slice(1)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Price</div>
                <div class="detail-value">
                    <strong>${formatKES(parcel.price)}</strong></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Booked On</div>
                <div class="detail-value">
                    ${formatDate(parcel.date_registered)}</div>
            </div>
            ${parcel.customer_name ? `
            <div class="detail-item">
                <div class="detail-label">Sender</div>
                <div class="detail-value">${parcel.customer_name}</div>
            </div>` : ''}
        `;
    }
}

// ── RENDER TRACKING TIMELINE ──────────────────────────────
function renderTimeline(updates, timelineId = 'timeline') {
    const timeline = document.getElementById(timelineId);
    if (!timeline) return;

    if (!updates.length) {
        timeline.innerHTML = `
            <p class="text-muted" style="padding:20px 0;">
                No tracking updates yet.
            </p>`;
        return;
    }

    timeline.innerHTML = updates.map((u, i) => `
        <div class="tl-item">
            <div class="tl-dot ${
                i === updates.length - 1 ? 'active' : ''}">
            </div>
            <div class="tl-content">
                <div class="tl-status">${u.status}</div>
                ${u.location
                    ? `<div class="tl-loc">📍 ${u.location}</div>`
                    : ''}
                ${u.notes && u.notes !== u.status
                    ? `<div class="tl-loc">${u.notes}</div>`
                    : ''}
                <div class="tl-meta">
                    By ${u.updated_by || 'System'} —
                    ${formatDate(u.updated_at)}
                </div>
            </div>
        </div>
    `).join('');
}

// ── HANDLE STATUS UPDATE (Driver) ─────────────────────────
async function handleStatusUpdate(event,
                                   currentUser,
                                   successId = 'alertSuccess',
                                   errorId   = 'alertError',
                                   btnId     = 'updateBtn') {
    event.preventDefault();
    const form = event.target;
    const btn  = document.getElementById(btnId);

    setLoading(btn, true);

    try {
        const res = await API.tracking.update({
            parcel_id:  parseInt(form.parcel_id.value),
            new_status: form.new_status.value,
            location:   form.location?.value?.trim() || '',
            notes:      form.notes?.value?.trim()    || '',
            updated_by: currentUser.full_name
        });

        if (res.status === 'success') {
            showAlert(successId,
                `Status updated to '${form.new_status.value}'!`,
                'success');
            form.reset();
        } else {
            showAlert(errorId, res.message, 'error');
        }
    } catch(e) {
        showAlert(errorId, 'Failed to update status.', 'error');
    }

    setLoading(btn, false);
}

// ── LOAD DRIVER'S ACTIVE PARCELS INTO SELECT ──────────────
async function loadDriverParcels(driverId,
                                  selectId = 'parcelSelect',
                                  tbodyId  = 'parcelsTable') {
    try {
        const res     = await API.dispatch.getByDriver(driverId);
        const active  = (res.data || []).filter(p =>
            !['Delivered', 'Failed'].includes(p.status));

        const sel = document.getElementById(selectId);
        if (sel) {
            sel.innerHTML =
                '<option value="">-- Select Your Parcel --</option>';
            active.forEach(p => {
                const opt       = document.createElement('option');
                opt.value       = p.parcel_id;
                opt.textContent =
                    `${p.tracking_number} — ${p.recipient_name}`;
                sel.appendChild(opt);
            });
        }

        const tbody = document.getElementById(tbodyId);
        if (tbody) {
            if (!active.length) {
                tbody.innerHTML = `<tr><td colspan="4">
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <h3>No active deliveries</h3>
                    </div></td></tr>`;
                return active;
            }
            tbody.innerHTML = active.map(p => `
                <tr>
                    <td>${trackingBadge(p.tracking_number)}</td>
                    <td>${p.recipient_name}</td>
                    <td>${p.zone}</td>
                    <td>${statusBadge(p.status)}</td>
                </tr>
            `).join('');
        }

        return active;
    } catch(e) {
        console.error('loadDriverParcels error:', e);
        return [];
    }
}
