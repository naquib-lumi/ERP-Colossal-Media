'use strict';

document.addEventListener('DOMContentLoaded', function () {
  if (window.__installCalBooted) return;
window.__installCalBooted = true;
  if (typeof Calendar === 'undefined') return;

  const isRtl = (document.documentElement.dir || '').toLowerCase() === 'rtl';
  const direction = isRtl ? 'rtl' : 'ltr';

  (function () {
    const wrapper = document.getElementById('artistCalendarWrapper');
    const calendarEl = document.getElementById('calendar');

    // Sidebar
    const btnToggleSidebar =
      document.querySelector('#btnToggleSidebar') ||
      document.querySelector('#app-calendar-sidebar .btn-toggle-sidebar');

    const inlineCalendar = document.querySelector('.inline-calendar');
    const upcomingList = document.getElementById('upcomingList');

    // Toolbar
    const inputStart = document.getElementById('filterStart');
    const inputEnd = document.getElementById('filterEnd');
    const inputSearch = document.getElementById('searchClient');
    const selArtist = document.getElementById('filterSalesperson');
    const btnToday = document.getElementById('btnToday');
    const btnReset = document.getElementById('btnReset');
    const btnExport = document.getElementById('btnExport');

    function setSidebar(collapsed) {
      if (!wrapper) return;
      if (collapsed) {
        wrapper.classList.add('sidebar-collapsed');
        if (btnToggleSidebar) {
          const icon = btnToggleSidebar.querySelector('i');
          const text = btnToggleSidebar.querySelector('span');
          icon?.classList.replace('bx-chevron-left', 'bx-chevron-right');
          if (text) text.textContent = 'Show Sidebar';
        }
      } else {
        wrapper.classList.remove('sidebar-collapsed');
        if (btnToggleSidebar) {
          const icon = btnToggleSidebar.querySelector('i');
          const text = btnToggleSidebar.querySelector('span');
          icon?.classList.replace('bx-chevron-right', 'bx-chevron-left');
          if (text) text.textContent = 'Hide Sidebar';
        }
      }
      setTimeout(() => calendar.updateSize(), 10);
    }

    // restore previous state
    setSidebar(localStorage.getItem('artistCalSidebarCollapsed') === '1');

    // wire click
    btnToggleSidebar?.addEventListener('click', () => {
      const collapsed = !wrapper.classList.contains('sidebar-collapsed');
      setSidebar(collapsed);
      localStorage.setItem('artistCalSidebarCollapsed', collapsed ? '1' : '0');
    });

    // Mini calendar
    if (inlineCalendar && window.flatpickr) {
      window.flatpickr(inlineCalendar, {
        monthSelectorType: 'static', static: true, inline: true,
        onChange: d => {
          if (!d?.length) return;
          calendar.changeView(calendar.view.type, moment(d[0]).format('YYYY-MM-DD'));
          calendar.refetchEvents();
        }
      });
    }

    // --- helpers ---
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

    function renderUpcoming(events) {
      if (!upcomingList) return;

      const items = events
        .filter(ev =>
          (ev?.extendedProps?.type === 'installation') &&
          (String(ev?.extendedProps?.status).toLowerCase() === 'in_progress')
        )
        .sort((a, b) => {
          const ad = a.start ? new Date(a.start) : new Date('2100-01-01');
          const bd = b.start ? new Date(b.start) : new Date('2100-01-01');
          return ad - bd;
        })
        .slice(0, 5);

      if (!items.length) {
        upcomingList.innerHTML =
          '<div class="text-muted small">No in-progress installations.</div>';
        return;
      }

      const html = items.map(ev => {
        const when = ev.start ? new Date(ev.start) : null;
        const bg = ev.backgroundColor || '#3b82f6';
        const tx = ev.textColor || '#fff';
        const title = ev.extendedProps?.product_name || ev.title || 'Installation';
        const code = ev.extendedProps?.product_code ? ` <span class="text-muted">(${ev.extendedProps.product_code})</span>` : '';
        const whenStr = when ? when.toLocaleString() : '—';

        return `
          <div class="d-flex align-items-start gap-2 p-2 border-bottom upcoming-item"
              data-event-id="${ev.id}">
            <span class="rounded-circle mt-1 flex-shrink-0" style="width:8px;height:8px;background:${bg}"></span>
            <div class="flex-grow-1">
              <div class="fw-semibold small mb-1" style="font-size:1rem;">${title}${code}</div>
              <div class="text-muted extra-small" style="font-size:0.8rem; margin-bottom:5px;">${whenStr}</div>
              <span class="badge border-0" style="background:${bg};color:${tx}">In&nbsp;Progress</span>
            </div>
          </div>`;
      }).join('');

      upcomingList.innerHTML = html;
    }

    function populateSalespeople(events) {
      if (!selArtist) return;
      const map = new Map();
      events.forEach(ev => {
        const id = ev.extendedProps?.artist_id;
        const name = ev.extendedProps?.artist_name;
        if (id && name) map.set(String(id), String(name));
      });
      const cur = selArtist.value;
      selArtist.innerHTML = '<option value="">Select Salesperson</option>' +
        Array.from(map.entries()).sort((a, b) => a[1].localeCompare(b[1]))
          .map(([id, name]) => `<option value="${id}">${name}</option>`).join('');
      if (cur) selArtist.value = cur;
    }

    function fetchEvents(info, success, failure) {
      const q = (inputSearch?.value || '').trim().toLowerCase();
      const artistFilter = selArtist?.value || '';

      const startStr = inputStart?.value ? (inputStart.value + 'T00:00:00') : info.startStr;
      const endStr = inputEnd?.value ? (inputEnd.value + 'T23:59:59') : info.endStr;

      $.ajax({
        url: '/installation/calendar/events',
        type: 'GET',
        data: { start: startStr, end: endStr },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          let evs = data.map(ev => ({ ...ev, allDay: ev.allDay ?? false }));
          console.log('Calendar events (raw):', evs);
          if (artistFilter) evs = evs.filter(ev => String(ev.extendedProps?.artist_id || '') === String(artistFilter));
          if (q) evs = evs.filter(ev => (ev.title || '').toLowerCase().includes(q));

          success(evs);
          renderUpcoming(evs);
          populateSalespeople(evs);
        },
        error: function (xhr) { console.error('Fetch events failed:', xhr.status, xhr.responseText); failure && failure(xhr); }
      });
    }

    // 🔴 helper ONLY for display
    function formatStatusDisplay(s) {
      if (!s) return '—';
      return s
        .toString()
        .split('_')
        .map(p => p.charAt(0).toUpperCase() + p.slice(1))
        .join(' ');
    }

    // 🔴 1) LEFT-SIDEBAR ITEM → MODAL
    function openInstallModalFromEvent(e) {
      const safeId = (e.id || 'evt').toString().replace(/[^a-zA-Z0-9]/g, '');
      const modalId = 'eventDetailModal_' + safeId;

      // --- pull data from extendedProps, prefer orderTitle etc ---
      const xp = e.extendedProps || {};

        const jobTitle =
          xp.order_title ||
          xp.orderTitle ||
          xp.product_name ||
          e.title ||
          'Installation';

        const deadline =
          xp.deadline ||
          xp.order_deadline ||
          '';

        const deliveryDate =
          xp.delivery_date ||
          xp.date ||
          (e.start ? moment(e.start).format('YYYY-MM-DD') : '');

        const deliveryTime =
          xp.delivery_time ||
          xp.time ||
          (e.start ? moment(e.start).format('HH:mm:ss') : '');

        const deliveryLocation =
          xp.delivery_location || xp.location || xp.address || '—';

        const deliveryQty =
          (xp.product_qty ?? xp.quantity ?? xp.qty ?? null);

        const deliveryQtyDisplay =
          (deliveryQty === null || deliveryQty === undefined) ? '—' : String(deliveryQty);

        const productName  = xp.product_name || e.title || 'Installation';
        const productCode  = xp.product_code || '';
        const company      = xp.company_name || '—';
        const leadName     = xp.lead_name || xp.leadName || '—';

        // 1️⃣ Lead info "Company - Lead Name"
        const companyName = company;
        const leadText =
          xp.lead_text ||
          ((companyName && companyName !== '—') || (leadName && leadName !== '—')
            ? `${companyName !== '—' ? companyName : ''}${
                (companyName !== '—' && leadName !== '—') ? ' - ' : ''
              }${leadName !== '—' ? leadName : ''}`
            : '—');

        const statusRaw  = xp.status || 'scheduled';
        const statusText = formatStatusDisplay(statusRaw);

        const whenDate = deliveryDate
          ? moment(deliveryDate).format('MMM D, YYYY')
          : (e.start ? moment(e.start).format('MMM D, YYYY') : '—');

        const whenTime = deliveryTime
          ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
          : (e.start ? moment(e.start).format('h:mm A') : '—');

        const viewHref = xp.product_id
          ? (`/installation/job/${xp.product_id}`)
          : '#';

        // status colors
        const statusKey = (xp.status || 'in_progress').toString().toLowerCase();
        const statusPalette = {
          completed:   ['#22c55e', '#ffffff'],
          rejected:    ['#ef4444', '#ffffff'],
          in_progress: ['#3b82f6', '#ffffff'],
          scheduled:   ['#3b82f6', '#ffffff'] // fallback
        };
        const [statusBg, statusTx] = statusPalette[statusKey] || ['#3b82f6', '#ffffff'];

        const deadlineStr = deadline ? moment(deadline).format('MMM D, YYYY') : '—';

        // 3️⃣ Deliver/Install Type from delivery_breakdowns.deliver_install_type
        const deliverInstallRaw =
          xp.deliver_install_type ||
          xp.delivery_install_type ||
          xp.delivery_install_type_raw ||
          '';

        const deliverInstallType = (() => {
          const v = (deliverInstallRaw || '').toString().trim().toLowerCase();
          if (!v) return '—';
          if (v === 'delivery_installation' || v === 'delivery & installation')
            return 'Delivery / Installation';
          if (v === 'delivery')       return 'Delivery';
          if (v === 'installation')   return 'Installation';
          if (v === 'self_pickup' || v === 'self-pickup' || v === 'self pickup')
            return 'Self Pickup';

          // fallback: title-case with spaces instead of _/-
          return deliverInstallRaw
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
        })();

        // optional: outsource cost & permit
        const outsourceCost    = xp.outsource_cost ?? 'RM 0.00';
        const permitAttachment = xp.permit_attachment || null;

        // 🔹 filename only for display
let permitFilename = null;
if (permitAttachment) {
  const raw = permitAttachment.toString();          // "path|originalName" or just "path"
  const parts = raw.split('|');
  permitFilename = (parts[1] || parts[0] || '').split(/[\\/]/).pop();
}

        // 🔹 download URL via new route (by product_id)
        let permitHref = null;
if (window.permitDownloadRoute && xp.product_id) {
    permitHref = window.permitDownloadRoute.replace(':id', xp.product_id);
}

        // remove existing modal if any
        document.getElementById(modalId)?.remove();

        const html = `
          <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header">
                  <!-- Title bar shows Product ID -->
                  <h5 class="modal-title" id="${modalId}Label">${productCode || 'Scheduled Task'}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background:red;"></button>
                </div>

                <div class="modal-body">
                  <!-- TOP BLOCK -->
                  <div class="row mb-3">
                    <div class="col-12">
                      <h4 class="mb-1 fw-bold">${jobTitle}</h4>
                      <div class="mb-1 text-muted">
                        <span class="badge d-inline-flex align-items-center me-2"
                              style="background:#eef2ff;color:#3730a3;border-radius:999px;font-weight:600;">
                          <i class="bi bi-truck"></i> Delivery / Installation
                        </span>
                        <span class="badge d-inline-flex align-items-center me-2"
                              style="background:${statusBg};color:${statusTx};border-radius:999px;font-weight:600;">
                          ${statusText}
                        </span>
                        <!-- 1️⃣ Lead info here -->
                        | ${leadText}
                      </div>
                      <div class="text-muted">
                        <i class="bi bi-calendar2-check me-1"></i>
                        Job Order Deadline:
                        <span class="fw-semibold">${deadlineStr}</span>
                      </div>
                    </div>
                  </div>

                  <!-- MAIN INFO – TWO COLUMNS -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <i class="bi bi-upc me-1"></i>
                        Product ID:
                        <span class="fw-semibold">${productCode || '—'}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-box-seam me-1"></i>
                        Product:
                        <span class="fw-semibold">${productName || '—'}</span>
                      </div>
                      <div class="mb-2">
                        <!-- 2️⃣ Lead Name row -->
                        <i class="bi bi-person me-1"></i>
                        Lead Name:
                        <span class="fw-semibold">${leadName}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-buildings me-1"></i>
                        Company:
                        <span class="fw-semibold">${companyName || '—'}</span>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="mb-2">
                        <i class="bi bi-list-ol me-1"></i>
                        Quantity:
                        <span class="fw-semibold">${deliveryQtyDisplay}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-geo-alt me-1"></i>
                        Location:
                        <span class="fw-semibold">${deliveryLocation}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-wrench me-1"></i>
                        Deliver/Install Type:
                        <span class="fw-semibold">${deliverInstallType}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-cash-coin me-1"></i>
                        Outsource Cost:
                        <span class="fw-semibold">${outsourceCost}</span>
                      </div>
                    </div>
                  </div>

                  <hr class="my-3">

                  <!-- PERMIT SECTION -->
                  <div class="row">
                    <div class="col-12">
                      <div class="mb-2"><strong>Permit Attachment:</strong></div>
                      <p class="mb-0">
  ${
    permitHref
      ? `<a href="${permitHref}" class="text-decoration-underline">
            ${permitFilename || ''}
         </a>`
      : 'No Permit Attached'
  }
</p>
                    </div>
                  </div>

                  <!-- View Job Order button -->
                  <div class="row mt-3">
                    <div class="col-12">
                      <a href="${viewHref}" class="btn w-100"
                        style="background:#111827;color:#fff;border-radius:10px;">
                        <i class="bi bi-eye me-1"></i> View Job Order
                      </a>
                    </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        `;
      document.body.insertAdjacentHTML('beforeend', html);
      new bootstrap.Modal(document.getElementById(modalId)).show();
    }

    const calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      direction,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      headerToolbar: { start: 'prev,next, title', end: 'dayGridMonth' },
      height: 'auto',
      dayMaxEvents: 2,
      editable: false,
      events: fetchEvents,
      dateClick: null,

      eventDidMount: function (info) {
        const e = info.event;
        if (e.backgroundColor) info.el.style.backgroundColor = e.backgroundColor;
        if (e.borderColor) info.el.style.borderColor = e.borderColor;
        if (e.textColor) info.el.style.color = e.textColor;
        const dot = info.el.querySelector('.fc-daygrid-event-dot');
        if (dot && e.borderColor) dot.style.borderColor = e.borderColor;
      },

      // 🔴 2) CALENDAR EVENT → MODAL (same info as above)
      eventClick: function (info) {

        info.jsEvent?.preventDefault();
        info.jsEvent?.stopPropagation();
        info.jsEvent?.stopImmediatePropagation();

        const e = info.event;
        const safeId = (e.id || 'evt').toString().replace(/[^a-zA-Z0-9]/g, '');
        const modalId = 'eventDetailModal_' + safeId;

        const xp = e.extendedProps || {};

        const jobTitle =
          xp.order_title ||
          xp.orderTitle ||
          xp.product_name ||
          e.title ||
          'Installation';

        const deadline =
          xp.deadline ||
          xp.order_deadline ||
          '';

        const deliveryDate =
          xp.delivery_date ||
          xp.date ||
          (e.start ? moment(e.start).format('YYYY-MM-DD') : '');

        const deliveryTime =
          xp.delivery_time ||
          xp.time ||
          (e.start ? moment(e.start).format('HH:mm:ss') : '');

        const deliveryLocation =
          xp.delivery_location || xp.location || xp.address || '—';

        const deliveryQty =
          (xp.product_qty ?? xp.quantity ?? xp.qty ?? null);

        const deliveryQtyDisplay =
          (deliveryQty === null || deliveryQty === undefined) ? '—' : String(deliveryQty);

        const productName  = xp.product_name || e.title || 'Installation';
        const productCode  = xp.product_code || '';
        const company      = xp.company_name || '—';
        const leadName     = xp.lead_name || xp.leadName || '—';

        // 1️⃣ Lead info "Company - Lead Name"
        const companyName = company;
        const leadText =
          xp.lead_text ||
          ((companyName && companyName !== '—') || (leadName && leadName !== '—')
            ? `${companyName !== '—' ? companyName : ''}${
                (companyName !== '—' && leadName !== '—') ? ' - ' : ''
              }${leadName !== '—' ? leadName : ''}`
            : '—');

        const statusRaw  = xp.status || 'scheduled';
        const statusText = formatStatusDisplay(statusRaw);

        const whenDate = deliveryDate
          ? moment(deliveryDate).format('MMM D, YYYY')
          : (e.start ? moment(e.start).format('MMM D, YYYY') : '—');

        const whenTime = deliveryTime
          ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
          : (e.start ? moment(e.start).format('h:mm A') : '—');

        const viewHref = xp.product_id
          ? (`/installation/job/${xp.product_id}`)
          : '#';

        // status colors
        const statusKey = (xp.status || 'in_progress').toString().toLowerCase();
        const statusPalette = {
          completed:   ['#22c55e', '#ffffff'],
          rejected:    ['#ef4444', '#ffffff'],
          in_progress: ['#3b82f6', '#ffffff'],
          scheduled:   ['#3b82f6', '#ffffff'] // fallback
        };
        const [statusBg, statusTx] = statusPalette[statusKey] || ['#3b82f6', '#ffffff'];

        const deadlineStr = deadline ? moment(deadline).format('MMM D, YYYY') : '—';

        // 3️⃣ Deliver/Install Type from delivery_breakdowns.deliver_install_type
        const deliverInstallRaw =
          xp.deliver_install_type ||
          xp.delivery_install_type ||
          xp.delivery_install_type_raw ||
          '';

        const deliverInstallType = (() => {
          const v = (deliverInstallRaw || '').toString().trim().toLowerCase();
          if (!v) return '—';
          if (v === 'delivery_installation' || v === 'delivery & installation')
            return 'Delivery / Installation';
          if (v === 'delivery')       return 'Delivery';
          if (v === 'installation')   return 'Installation';
          if (v === 'self_pickup' || v === 'self-pickup' || v === 'self pickup')
            return 'Self Pickup';

          // fallback: title-case with spaces instead of _/-
          return deliverInstallRaw
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
        })();

        // optional: outsource cost & permit
        const outsourceCost    = xp.outsource_cost ?? 'RM 0.00';
        const permitAttachment = xp.permit_attachment || null;

        // 🔹 filename only for display
let permitFilename = null;
if (permitAttachment) {
  const raw = permitAttachment.toString();          // "path|originalName" or just "path"
  const parts = raw.split('|');
  permitFilename = (parts[1] || parts[0] || '').split(/[\\/]/).pop();
}

// 🔹 download URL via new route (by product_id)
let permitHref = null;
if (window.permitDownloadRoute && xp.product_id) {
    permitHref = window.permitDownloadRoute.replace(':id', xp.product_id);
}

        // remove existing modal if any
        document.getElementById(modalId)?.remove();

        const html = `
          <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header">
                  <!-- Title bar shows Product ID -->
                  <h5 class="modal-title" id="${modalId}Label">${productCode || 'Scheduled Task'}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background:red;"></button>
                </div>

                <div class="modal-body">
                  <!-- TOP BLOCK -->
                  <div class="row mb-3">
                    <div class="col-12">
                      <h4 class="mb-1 fw-bold">${jobTitle}</h4>
                      <div class="mb-1 text-muted">
                        <span class="badge d-inline-flex align-items-center me-2"
                              style="background:#eef2ff;color:#3730a3;border-radius:999px;font-weight:600;">
                          <i class="bi bi-truck"></i> Delivery / Installation
                        </span>
                        <span class="badge d-inline-flex align-items-center me-2"
                              style="background:${statusBg};color:${statusTx};border-radius:999px;font-weight:600;">
                          ${statusText}
                        </span>
                        <!-- 1️⃣ Lead info here -->
                        | ${leadText}
                      </div>
                      <div class="text-muted">
                        <i class="bi bi-calendar2-check me-1"></i>
                        Job Order Deadline:
                        <span class="fw-semibold">${deadlineStr}</span>
                      </div>
                    </div>
                  </div>

                  <!-- MAIN INFO – TWO COLUMNS -->
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <i class="bi bi-upc me-1"></i>
                        Product ID:
                        <span class="fw-semibold">${productCode || '—'}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-box-seam me-1"></i>
                        Product:
                        <span class="fw-semibold">${productName || '—'}</span>
                      </div>
                      <div class="mb-2">
                        <!-- 2️⃣ Lead Name row -->
                        <i class="bi bi-person me-1"></i>
                        Lead Name:
                        <span class="fw-semibold">${leadName}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-buildings me-1"></i>
                        Company:
                        <span class="fw-semibold">${companyName || '—'}</span>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="mb-2">
                        <i class="bi bi-list-ol me-1"></i>
                        Quantity:
                        <span class="fw-semibold">${deliveryQtyDisplay}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-geo-alt me-1"></i>
                        Location:
                        <span class="fw-semibold">${deliveryLocation}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-wrench me-1"></i>
                        Deliver/Install Type:
                        <span class="fw-semibold">${deliverInstallType}</span>
                      </div>
                      <div class="mb-2">
                        <i class="bi bi-cash-coin me-1"></i>
                        Outsource Cost:
                        <span class="fw-semibold">${outsourceCost}</span>
                      </div>
                    </div>
                  </div>

                  <hr class="my-3">

                  <!-- PERMIT SECTION -->
                  <div class="row">
                    <div class="col-12">
                      <div class="mb-2"><strong>Permit Attachment:</strong></div>
<p class="mb-0">
  ${
    permitHref
      ? `<a href="${permitHref}" class="text-decoration-underline">
            ${permitFilename || ''}
         </a>`
      : 'No Permit Attached'
  }
</p>
                    </div>
                  </div>

                  <!-- View Job Order button -->
                  <div class="row mt-3">
                    <div class="col-12">
                      <a href="${viewHref}" class="btn w-100"
                        style="background:#111827;color:#fff;border-radius:10px;">
                        <i class="bi bi-eye me-1"></i> View Job Order
                      </a>
                    </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
        new bootstrap.Modal(document.getElementById(modalId)).show();
      },

      datesSet: function () { }
    });

    calendar.render();

    // LEFT panel click -> use our first modal function
    upcomingList?.addEventListener('click', function (e) {
      // prevent bubbling / duplicate invocations
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      const item = e.target.closest('.upcoming-item');
      if (!item) return;

      const eventId = item.getAttribute('data-event-id');
      if (!eventId) return;

      const ev = calendar.getEventById(eventId);
      if (!ev) return;

      openInstallModalFromEvent(ev);
    });

    // Toolbar actions
    const refetch = debounce(() => calendar.refetchEvents(), 250);
    btnToday?.addEventListener('click', () => { calendar.today(); calendar.refetchEvents(); });
    btnExport?.addEventListener('click', () => window.print());

    btnReset?.addEventListener('click', () => {
      if (inputStart) inputStart.value = '';
      if (inputEnd) inputEnd.value = '';
      if (inputSearch) inputSearch.value = '';
      if (selArtist) selArtist.value = '';
      if (selectAll) selectAll.checked = true;
      filterInputs.forEach(c => c.checked = true);

      if (inlineCalendar && inlineCalendar._flatpickr) {
        inlineCalendar._flatpickr.clear();
        inlineCalendar._flatpickr.setDate(new Date(), true);
      }

      calendar.today();
      calendar.refetchEvents();
    });

    inputStart?.addEventListener('change', refetch);
    inputEnd?.addEventListener('change', refetch);
    inputSearch?.addEventListener('input', refetch);
    selArtist?.addEventListener('change', () => calendar.refetchEvents());

    // (this second setSidebar at the end is from your original file – left intact)
    function setSidebar2(collapsed) {
      if (!wrapper) return;
      if (collapsed) {
        wrapper.classList.add('sidebar-collapsed');
        btnToggleSidebar?.querySelector('span')?.replaceChildren(document.createTextNode('Show Sidebar'));
        btnToggleSidebar?.querySelector('i')?.classList.replace('bx-chevron-left', 'bx-chevron-right');
      } else {
        wrapper.classList.remove('sidebar-collapsed');
        btnToggleSidebar?.querySelector('span')?.replaceChildren(document.createTextNode('Hide Sidebar'));
        btnToggleSidebar?.querySelector('i')?.classList.replace('bx-chevron-right', 'bx-chevron-left');
      }
      setTimeout(() => calendar.updateSize(), 10);
    }
    const stored = localStorage.getItem('artistCalSidebarCollapsed') === '1';
    setSidebar2(stored);
    btnToggleSidebar?.addEventListener('click', () => {
      const collapsed = !wrapper.classList.contains('sidebar-collapsed');
      setSidebar2(collapsed);
      localStorage.setItem('artistCalSidebarCollapsed', collapsed ? '1' : '0');
    });
  })();

  let __modalOpening = false;

function openInstallModalFromEvent(e) {
  if (__modalOpening) return;
  __modalOpening = true;

  // your existing code that builds + shows modal...
  const modalEl = document.getElementById(modalId);
  const bsModal = new bootstrap.Modal(modalEl);
  bsModal.show();

  // reset guard when hidden
  modalEl.addEventListener('hidden.bs.modal', () => { __modalOpening = false; }, { once: true });
}
});
