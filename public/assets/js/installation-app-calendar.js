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

    const selectAll = document.getElementById('selectAll');
    const filterInputs = document.querySelectorAll('.filter-input');

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

      setTimeout(() => {
        if (typeof calendar !== 'undefined') {
          calendar.updateSize();
        }
      }, 10);
    }

    // Restore previous state
    setSidebar(localStorage.getItem('artistCalSidebarCollapsed') === '1');

    // Wire click
    btnToggleSidebar?.addEventListener('click', () => {
      const collapsed = !wrapper.classList.contains('sidebar-collapsed');

      setSidebar(collapsed);
      localStorage.setItem('artistCalSidebarCollapsed', collapsed ? '1' : '0');
    });

    // Mini calendar
    if (inlineCalendar && window.flatpickr) {
      window.flatpickr(inlineCalendar, {
        monthSelectorType: 'static',
        static: true,
        inline: true,

        onChange: d => {
          if (!d?.length) return;

          calendar.changeView(calendar.view.type, moment(d[0]).format('YYYY-MM-DD'));
          calendar.refetchEvents();
        }
      });
    }

    // --- helpers ---
    const debounce = (fn, ms) => {
      let t;

      return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
      };
    };

    /*
     * Calendar display rule:
     *
     * 1. Only display records where delivery_breakdowns.method is:
     *    - delivery
     *    - installation
     *
     * 2. Do NOT wait for products.taskType to become installation or delivery.
     *
     * Important:
     * The backend route /installation/calendar/events must return the delivery_breakdowns.method
     * value inside extendedProps.method, extendedProps.delivery_breakdown_method,
     * extendedProps.delivery_method, or extendedProps.deliver_install_type.
     */
    const allowedDeliveryBreakdownMethods = ['delivery', 'installation'];

    function normalizeText(value) {
      return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[\s_-]+/g, ' ');
    }

    function getDeliveryBreakdownMethod(eventOrPlainObject) {
      const xp = eventOrPlainObject?.extendedProps || {};

      /*
       * Prefer fields from delivery_breakdowns.
       * Do NOT use products.taskType here.
       */
      return normalizeText(
        xp.method ??
        xp.delivery_breakdown_method ??
        xp.delivery_method ??
        xp.deliveryMethod ??
        xp.deliver_install_type ??
        xp.deliverInstallType ??
        ''
      );
    }

    function isDeliveryOrInstallationMethod(eventOrPlainObject) {
      const method = getDeliveryBreakdownMethod(eventOrPlainObject);

      return allowedDeliveryBreakdownMethods.some(allowed => {
        return method === allowed || method.includes(allowed);
      });
    }

    function getDeliveryBreakdownMethodLabel(eventOrPlainObject) {
      const method = getDeliveryBreakdownMethod(eventOrPlainObject);

      if (method.includes('install')) return 'Installation';
      if (method.includes('delivery')) return 'Delivery';

      return 'Delivery / Installation';
    }

    function renderUpcoming(events) {
      if (!upcomingList) return;

      const items = events
        .filter(ev => isDeliveryOrInstallationMethod(ev))
        .sort((a, b) => {
          const ad = a.start ? new Date(a.start) : new Date('2100-01-01');
          const bd = b.start ? new Date(b.start) : new Date('2100-01-01');

          return ad - bd;
        })
        .slice(0, 5);

      if (!items.length) {
        upcomingList.innerHTML =
          '<div class="text-muted small">No delivery or installation tasks.</div>';

        return;
      }

      const html = items.map(ev => {
        const when = ev.start ? new Date(ev.start) : null;
        const bg = ev.backgroundColor || '#3b82f6';
        const tx = ev.textColor || '#fff';
        const title = ev.extendedProps?.product_name || ev.title || getDeliveryBreakdownMethodLabel(ev);
        const code = ev.extendedProps?.product_code
          ? ` <span class="text-muted">(${ev.extendedProps.product_code})</span>`
          : '';
        const whenStr = when ? when.toLocaleString() : '—';
        const methodLabel = getDeliveryBreakdownMethodLabel(ev);

        return `
          <div class="d-flex align-items-start gap-2 p-2 border-bottom upcoming-item"
              data-event-id="${ev.id}">
            <span class="rounded-circle mt-1 flex-shrink-0" style="width:8px;height:8px;background:${bg}"></span>
            <div class="flex-grow-1">
              <div class="fw-semibold small mb-1" style="font-size:1rem;">${title}${code}</div>
              <div class="text-muted extra-small" style="font-size:0.8rem; margin-bottom:5px;">${whenStr}</div>
              <span class="badge border-0" style="background:${bg};color:${tx}">${methodLabel}</span>
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

        if (id && name) {
          map.set(String(id), String(name));
        }
      });

      const cur = selArtist.value;

      selArtist.innerHTML =
        '<option value="">Select Salesperson</option>' +
        Array.from(map.entries())
          .sort((a, b) => a[1].localeCompare(b[1]))
          .map(([id, name]) => `<option value="${id}">${name}</option>`)
          .join('');

      if (cur) selArtist.value = cur;
    }

    function fetchEvents(info, success, failure) {
      const q = (inputSearch?.value || '').trim().toLowerCase();
      const artistFilter = selArtist?.value || '';

      const startStr = inputStart?.value
        ? inputStart.value + 'T00:00:00'
        : info.startStr;

      const endStr = inputEnd?.value
        ? inputEnd.value + 'T23:59:59'
        : info.endStr;

      $.ajax({
        url: '/installation/calendar/events',
        type: 'GET',
        data: {
          start: startStr,
          end: endStr
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        success: function (data) {
          let evs = data
            .map(ev => ({
              ...ev,
              allDay: ev.allDay ?? false
            }))
            .filter(ev => isDeliveryOrInstallationMethod(ev));

          console.log('Calendar events after delivery/installation method filter:', evs);

          if (artistFilter) {
            evs = evs.filter(ev => {
              return String(ev.extendedProps?.artist_id || '') === String(artistFilter);
            });
          }

          if (q) {
            evs = evs.filter(ev => {
              const xp = ev.extendedProps || {};

              const searchable = [
                ev.title,
                xp.product_name,
                xp.product_code,
                xp.company_name,
                xp.order_title,
                xp.orderTitle,
                xp.method,
                xp.delivery_breakdown_method,
                xp.delivery_method,
                xp.deliver_install_type
              ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

              return searchable.includes(q);
            });
          }

          success(evs);
          renderUpcoming(evs);
          populateSalespeople(evs);
        },

        error: function (xhr) {
          console.error('Fetch events failed:', xhr.status, xhr.responseText);

          if (failure) {
            failure(xhr);
          }
        }
      });
    }

    // Helper only for display
    function formatStatusDisplay(s) {
      if (!s) return '—';

      return s
        .toString()
        .split('_')
        .map(p => p.charAt(0).toUpperCase() + p.slice(1))
        .join(' ');
    }

    // LEFT-SIDEBAR ITEM → MODAL
    function openInstallModalFromEvent(e) {
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

      const deliveryMethod =
        xp.method ||
        xp.delivery_breakdown_method ||
        xp.delivery_method ||
        xp.deliveryMethod ||
        xp.deliver_install_type ||
        xp.deliverInstallType ||
        getDeliveryBreakdownMethodLabel(e) ||
        '—';

      const deliveryLocation =
        xp.delivery_location ||
        xp.location ||
        xp.address ||
        '—';

      const deliveryQty =
        xp.product_qty ??
        xp.quantity ??
        xp.qty ??
        null;

      const productTotalQty =
        xp.product_qty_total ??
        xp.totalQuantity ??
        null;

      const deliveryQtyDisplay =
        deliveryQty === null || deliveryQty === undefined
          ? '—'
          : String(deliveryQty);

      const productTotalQtyDisplay =
        productTotalQty === null || productTotalQty === undefined
          ? '—'
          : String(productTotalQty);

      const productName = xp.product_name || e.title || 'Installation';
      const productCode = xp.product_code || '';
      const company = xp.company_name || '—';
      const assigned = xp.artist_name || 'Not assigned yet';

      const statusRaw = xp.status || 'scheduled';
      const statusText = formatStatusDisplay(statusRaw);

      const bg = e.backgroundColor || '#3b82f6';
      const tx = e.textColor || '#fff';

      const whenDate = deliveryDate
        ? moment(deliveryDate).format('MMM D, YYYY')
        : e.start
          ? moment(e.start).format('MMM D, YYYY')
          : '—';

      const whenTime = deliveryTime
        ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
        : e.start
          ? moment(e.start).format('h:mm A')
          : '—';

      const viewHref = xp.product_id
        ? `/installation/job/${xp.product_id}`
        : '#';

      const statusKey = (xp.status || 'in_progress').toString().toLowerCase();

      const statusPalette = {
        completed: ['#22c55e', '#ffffff'],
        rejected: ['#ef4444', '#ffffff'],
        in_progress: ['#3b82f6', '#ffffff'],
        scheduled: ['#3b82f6', '#ffffff']
      };

      const [statusBg, statusTx] = statusPalette[statusKey] || ['#3b82f6', '#ffffff'];

      const companyName = company;
      const deadlineStr = deadline ? moment(deadline).format('MMM D, YYYY') : '—';

      document.getElementById(modalId)?.remove();

      const html = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;">
              <div class="modal-header border-0 pb-0">
                <div style="margin-bottom: 10px;">
                  <h6 class="text-muted fw-semibold mb-0" style="font-size:1rem;">You have 1 scheduled task</h6>
                  <div class="small text-muted mb-1">Tasks on ${whenDate}</div>
                </div>
                <button style="background:#ef4444;" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body pt-0">
                <div class="border rounded-3 p-3" style="background:#fff;">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <div style="margin-bottom:10px !important;">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-briefcase me-1"></i> Job Title
                      </div>
                      <div class="fw-bold" style="font-size:1.2rem;">${jobTitle}</div>
                    </div>

                    <div class="text-end">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-upc me-1"></i> Product ID
                      </div>
                      <div class="fw-semibold">${productCode || '—'}</div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mb-3" style="margin-bottom:10px !important;">
                    <span class="badge d-inline-flex align-items-center gap-2"
                          style="background:#eef2ff;color:#3730a3;border-radius:999px;padding:.35rem .6rem;font-weight:600">
                      <i class="bi bi-truck"></i> Delivery / Installation
                    </span>

                    <span class="badge" style="background:${statusBg};color:${statusTx};border-radius:999px;padding:.35rem .6rem">
                      ${statusText}
                    </span>
                  </div>

                  <div class="row mb-2" style="margin-bottom:10px !important;">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-buildings me-1"></i> Company
                      </div>
                      <div class="fw-semibold">${companyName || '—'}</div>
                    </div>

                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-calendar2-check me-1"></i> Deadline
                      </div>
                      <div class="fw-semibold">${deadlineStr}</div>
                    </div>
                  </div>

                  <div class="row mb-2 pt-2 border-top" style="margin-bottom:10px !important;">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-calendar-event me-1"></i> Delivery Date
                      </div>
                      <div class="fw-semibold">${whenDate}</div>
                    </div>

                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-clock me-1"></i> Delivery Time
                      </div>
                      <div class="fw-semibold">${whenTime}</div>
                    </div>
                  </div>

                  <div class="row mb-2" style="margin-bottom:10px !important;">
                    <div class="col-8">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-truck me-1"></i> Delivery Method
                      </div>
                      <div class="fw-semibold">${deliveryMethod || '—'}</div>
                    </div>

                    <div class="col-4 text-end">
                      <div class="text-uppercase small text-muted">
                        <i class="bi bi-brush me-1"></i>Assigned artist
                      </div>
                      <div class="fw-semibold">${assigned}</div>
                    </div>
                  </div>

                  <div class="row mb-2" style="margin-bottom:10px !important;">
                    <div class="col-8">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-geo-alt me-1"></i> Delivery Location
                      </div>
                      <div class="fw-semibold">${deliveryLocation}</div>
                    </div>

                    <div class="col-4 text-end">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-123 me-1"></i> Delivery Qty
                      </div>
                      <div class="fw-semibold">${deliveryQtyDisplay}</div>
                    </div>
                  </div>

                  <div class="row mb-2" style="margin-bottom:10px !important;">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-box-seam me-1"></i> Product
                      </div>
                      <div class="fw-semibold">${productName || '—'}</div>
                    </div>

                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted field-title">
                        <i class="bi bi-diagram-3 me-1"></i> Total Qty
                      </div>
                      <div class="fw-semibold">${productTotalQtyDisplay}</div>
                    </div>
                  </div>

                  <a href="${viewHref}" class="btn w-100 mt-3"
                    style="background:#111827;color:#fff;border-radius:10px;">
                    <i class="bi bi-eye me-1"></i> View Job Order
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>`;

      document.body.insertAdjacentHTML('beforeend', html);

      const modalEl = document.getElementById(modalId);
      const modal = new bootstrap.Modal(modalEl);

      modal.show();

      modalEl.addEventListener('hidden.bs.modal', () => {
        modal.dispose();
        modalEl.remove();
      });
    }

    const calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      direction,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      headerToolbar: {
        start: 'prev,next, title',
        end: 'dayGridMonth'
      },
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

        if (dot && e.borderColor) {
          dot.style.borderColor = e.borderColor;
        }
      },

      // CALENDAR EVENT → MODAL
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

        const deliveryMethod =
          xp.method ||
          xp.delivery_breakdown_method ||
          xp.delivery_method ||
          xp.deliveryMethod ||
          xp.deliver_install_type ||
          xp.deliverInstallType ||
          getDeliveryBreakdownMethodLabel(e) ||
          '—';

        const deliveryLocation =
          xp.delivery_location ||
          xp.location ||
          xp.address ||
          '—';

        const deliveryQty =
          xp.product_qty ??
          xp.quantity ??
          xp.qty ??
          null;

        const productTotalQty =
          xp.product_qty_total ??
          xp.totalQuantity ??
          null;

        const deliveryQtyDisplay =
          deliveryQty === null || deliveryQty === undefined
            ? '—'
            : String(deliveryQty);

        const productTotalQtyDisplay =
          productTotalQty === null || productTotalQty === undefined
            ? '—'
            : String(productTotalQty);

        const productName = xp.product_name || e.title || 'Installation';
        const productCode = xp.product_code || '';
        const company = xp.company_name || '—';
        const assigned = xp.artist_name || 'Not assigned yet';

        const statusRaw = xp.status || 'scheduled';
        const statusText = formatStatusDisplay(statusRaw);

        const whenDate = deliveryDate
          ? moment(deliveryDate).format('MMM D, YYYY')
          : e.start
            ? moment(e.start).format('MMM D, YYYY')
            : '—';

        const whenTime = deliveryTime
          ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
          : e.start
            ? moment(e.start).format('h:mm A')
            : '—';

        const viewHref = xp.product_id
          ? `/installation/job/${xp.product_id}`
          : '#';

        const statusKey = (xp.status || 'in_progress').toString().toLowerCase();

        const statusPalette = {
          completed: ['#22c55e', '#ffffff'],
          rejected: ['#ef4444', '#ffffff'],
          in_progress: ['#3b82f6', '#ffffff'],
          scheduled: ['#3b82f6', '#ffffff']
        };

        const [statusBg, statusTx] = statusPalette[statusKey] || ['#3b82f6', '#ffffff'];

        const companyName = company;
        const deadlineStr = deadline ? moment(deadline).format('MMM D, YYYY') : '—';

        document.getElementById(modalId)?.remove();

        const html = `
          <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header border-0 pb-0">
                  <div>
                    <div class="fw-semibold mb-1" style="font-size:1.05rem;">
                      ${productCode || '#ORD-????'}
                    </div>

                    <div class="mb-2" style="font-size:1.1rem;font-weight:600;">
                      ${jobTitle}
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                      <span class="badge d-inline-flex align-items-center gap-2"
                            style="background:#f97316;color:#fff;border-radius:999px;padding:.35rem .7rem;font-weight:600;">
                        <i class="bi bi-truck"></i> Delivery / Installation
                      </span>

                      <span class="badge"
                            style="background:${statusBg};color:${statusTx};border-radius:999px;padding:.35rem .8rem;">
                        ${statusText}
                      </span>
                    </div>
                  </div>

                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                  <hr class="mt-0 mb-3"/>

                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-calendar-event me-1"></i> Job Order Deadline
                      </div>
                      <div class="fw-semibold mb-3">${deadlineStr}</div>

                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-clock me-1"></i> Delivery Date / Time
                      </div>
                      <div class="fw-semibold">${whenDate} • ${whenTime}</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-truck me-1"></i> Delivery Method
                      </div>
                      <div class="fw-semibold mb-3">${deliveryMethod}</div>

                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-person me-1"></i> Assigned Artist
                      </div>
                      <div class="fw-semibold">${assigned}</div>
                    </div>
                  </div>

                  <hr class="my-3"/>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-building me-1"></i> Company
                      </div>
                      <div class="fw-semibold mb-3">${companyName}</div>

                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-geo-alt me-1"></i> Delivery Location
                      </div>
                      <div class="fw-semibold">${deliveryLocation}</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-uppercase small text-muted mb-1 field-title">
                        <i class="bi bi-box-seam me-1"></i> Product
                      </div>
                      <div class="fw-semibold">${productName}</div>

                      <div class="text-muted small mb-2">
                        Product ID: <span class="fw-semibold">${productCode || '—'}</span>
                      </div>

                      <div class="d-flex flex-wrap gap-4">
                        <div>
                          <div class="text-uppercase small text-muted mb-1 field-title">Delivery Qty</div>
                          <div class="fw-semibold">${deliveryQtyDisplay}</div>
                        </div>

                        <div>
                          <div class="text-uppercase small text-muted mb-1 field-title">Total Qty</div>
                          <div class="fw-semibold">${productTotalQtyDisplay}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 d-flex justify-content-end">
                    <a href="${viewHref}" class="btn btn-primary btn-sm">
                      <i class="bi bi-arrow-right-circle me-1"></i> View Job Order
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;

        $('body').append(html);

        const modalEl = document.getElementById(modalId);
        const modal = new bootstrap.Modal(modalEl);

        modal.show();

        modalEl.addEventListener('hidden.bs.modal', () => {
          modal.dispose();
          modalEl.remove();
        });
      },

      datesSet: function () {}
    });

    calendar.render();

    // LEFT panel click → use modal function
    upcomingList?.addEventListener('click', function (e) {
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

    btnToday?.addEventListener('click', () => {
      calendar.today();
      calendar.refetchEvents();
    });

    btnExport?.addEventListener('click', () => {
      window.print();
    });

    btnReset?.addEventListener('click', () => {
      if (inputStart) inputStart.value = '';
      if (inputEnd) inputEnd.value = '';
      if (inputSearch) inputSearch.value = '';
      if (selArtist) selArtist.value = '';

      if (selectAll) selectAll.checked = true;

      filterInputs.forEach(c => {
        c.checked = true;
      });

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
  })();
});