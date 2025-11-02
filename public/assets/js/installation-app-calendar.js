'use strict';

document.addEventListener('DOMContentLoaded', function () {
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
        const code  = ev.extendedProps?.product_code ? ` <span class="text-muted">(${ev.extendedProps.product_code})</span>` : '';
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

      const deliveryMethod =
        xp.method ||
        xp.delivery_method ||
        xp.deliver_install_type ||
        '—';

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
        : (e.start ? moment(e.start).format('MMM D, YYYY') : '—');

      const whenTime = deliveryTime
        ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
        : (e.start ? moment(e.start).format('h:mm A') : '—');

      const viewHref = xp.product_id
        ? (`/installation/job/${xp.product_id}`)
        : '#';

      // remove old
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
                    <div>
                      <div class="text-uppercase small text-muted mb-1" style="letter-spacing:.04em;">Job Title</div>
                      <div class="fw-bold" style="font-size:1.2rem;">${jobTitle}</div>
                    </div>
                    <div class="text-end">
                      <div class="text-uppercase small text-muted mb-1">Product ID</div>
                      <div class="fw-semibold">${productCode}</div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge d-inline-flex align-items-center gap-2"
                          style="background:#eef2ff;color:#3730a3;border-radius:999px;padding:.35rem .6rem;font-weight:600">
                      <i class="bi bi-truck"></i> Delivery / Installation
                    </span>
                    <span class="badge"
                          style="background:${bg};color:${tx};border-radius:999px;padding:.35rem .6rem">
                      ${statusText}
                    </span>
                  </div>

                  <div class="row mb-2">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted">Company</div>
                      <div class="fw-semibold">${company}</div>
                    </div>
                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted">Deadline</div>
                      <div class="fw-semibold">${deadline ? moment(deadline).format('MMM D, YYYY') : '—'}</div>
                    </div>
                  </div>

                  <div class="row mb-2 pt-2 border-top">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted">Delivery date</div>
                      <div class="fw-semibold">${whenDate}</div>
                    </div>
                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted">Delivery time</div>
                      <div class="fw-semibold">${whenTime}</div>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <div class="col-12">
                      <div class="text-uppercase small text-muted">Delivery method</div>
                      <div class="fw-semibold">${deliveryMethod}</div>
                    </div>
                  </div>

                  <div class="row mb-1">
                    <div class="col-6">
                      <div class="text-uppercase small text-muted">Product</div>
                      <div class="fw-semibold">${productName}</div>
                    </div>
                    <div class="col-6 text-end">
                      <div class="text-uppercase small text-muted">Assigned artist</div>
                      <div class="fw-semibold">${assigned}</div>
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
          xp.delivery_method ||
          xp.deliver_install_type ||
          '—';

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
          : (e.start ? moment(e.start).format('MMM D, YYYY') : '—');

        const whenTime = deliveryTime
          ? moment(`1970-01-01 ${deliveryTime}`).format('h:mm A')
          : (e.start ? moment(e.start).format('h:mm A') : '—');

        const viewHref = xp.product_id
          ? (`/installation/job/${xp.product_id}`)
          : '#';

        document.getElementById(modalId)?.remove();

        const html = `
          <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header border-0 pb-0">
                  <div style="margin-bottom: 10px;">
                    <h6 class="text-muted fw-semibold mb-0" style="font-size:1rem; ">You have 1 scheduled task</h6>
                    <div class="small text-muted mb-1">Tasks on ${whenDate}</div>
                  </div>
                  <button style="background:#ef4444;" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-0">
                  <div class="border rounded-3 p-3" style="background:#fff;">

                    <div class="d-flex align-items-start justify-content-between mb-2">
                      <div>
                        <div class="text-uppercase small text-muted mb-1" style="letter-spacing:.04em;">Job Title</div>
                        <div class="fw-bold" style="font-size:1.2rem;">${jobTitle}</div>
                      </div>
                      <div class="text-end">
                        <div class="text-uppercase small text-muted mb-1">Product ID</div>
                        <div class="fw-semibold">${productCode}</div>
                      </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-3">
                      <span class="badge d-inline-flex align-items-center gap-2"
                            style="background:#eef2ff;color:#3730a3;border-radius:999px;padding:.35rem .6rem;font-weight:600">
                        <i class="bi bi-truck"></i> Delivery / Installation
                      </span>
                      <span class="badge"
                            style="background:${bg};color:${tx};border-radius:999px;padding:.35rem .6rem">
                        ${statusText}
                      </span>
                    </div>

                    <div class="row mb-2">
                      <div class="col-6">
                        <div class="text-uppercase small text-muted">Company</div>
                        <div class="fw-semibold">${company}</div>
                      </div>
                      <div class="col-6 text-end">
                        <div class="text-uppercase small text-muted">Deadline</div>
                        <div class="fw-semibold">${deadline ? moment(deadline).format('MMM D, YYYY') : '—'}</div>
                      </div>
                    </div>

                    <div class="row mb-2 pt-2 border-top">
                      <div class="col-6">
                        <div class="text-uppercase small text-muted">Delivery date</div>
                        <div class="fw-semibold">${whenDate}</div>
                      </div>
                      <div class="col-6 text-end">
                        <div class="text-uppercase small text-muted">Delivery time</div>
                        <div class="fw-semibold">${whenTime}</div>
                      </div>
                    </div>

                    <div class="row mb-2">
                      <div class="col-12">
                        <div class="text-uppercase small text-muted">Delivery method</div>
                        <div class="fw-semibold">${deliveryMethod}</div>
                      </div>
                    </div>

                    <div class="row mb-1">
                      <div class="col-6">
                        <div class="text-uppercase small text-muted">Product</div>
                        <div class="fw-semibold">${productName}</div>
                      </div>
                      <div class="col-6 text-end">
                        <div class="text-uppercase small text-muted">Assigned artist</div>
                        <div class="fw-semibold">${assigned}</div>
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
});
