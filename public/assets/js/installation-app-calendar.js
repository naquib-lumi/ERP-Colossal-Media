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
      // Let FullCalendar recompute width
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

  // Top 5 in-progress installation events, ordered by start date (soonest first).
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
      <div class="d-flex align-items-start gap-2 p-2 border-bottom">
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
      const map = new Map(); // id -> name
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

      // If date range fields are empty, just use FullCalendar's visible window.
      const startStr = inputStart?.value ? (inputStart.value + 'T00:00:00') : info.startStr;
      const endStr = inputEnd?.value ? (inputEnd.value + 'T23:59:59') : info.endStr;

      $.ajax({
        url: '/installation/calendar/events',
        type: 'GET',
        data: { start: startStr, end: endStr },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          // We trust server colors – just ensure DOM gets them
          let evs = data.map(ev => ({ ...ev, allDay: ev.allDay ?? false }));
          console.log('Calendar events (raw):', evs);
          // Filters
          if (artistFilter) evs = evs.filter(ev => String(ev.extendedProps?.artist_id || '') === String(artistFilter));
          if (q) evs = evs.filter(ev => (ev.title || '').toLowerCase().includes(q));

          success(evs);
          renderUpcoming(evs);
          populateSalespeople(evs);
        },
        error: function (xhr) { console.error('Fetch events failed:', xhr.status, xhr.responseText); failure && failure(xhr); }
      });
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

      // Enforce server colors on the element (prevents theme overrides)
      eventDidMount: function (info) {
        const e = info.event;
        if (e.backgroundColor) info.el.style.backgroundColor = e.backgroundColor;
        if (e.borderColor) info.el.style.borderColor = e.borderColor;
        if (e.textColor) info.el.style.color = e.textColor;
        const dot = info.el.querySelector('.fc-daygrid-event-dot');
        if (dot && e.borderColor) dot.style.borderColor = e.borderColor;
      },

      // Read-only detail modal
      eventClick: function (info) {
        const e = info.event;
        const safeId = (e.id || 'evt').toString().replace(/[^a-zA-Z0-9]/g, '');
        const modalId = 'eventDetailModal_' + safeId;

        const bg = e.backgroundColor || '#3b82f6';
        const tx = e.textColor || '#fff';
        const status = (e.extendedProps?.status || 'scheduled').toLowerCase();

        // date + time
        const whenDate = e.start ? moment(e.start).format('MMMM D, YYYY') : '—';
        const whenTime = e.start ? moment(e.start).format('h:mm A') : '—';

        // data
        const productName = e.extendedProps?.product_name || e.title || 'Installation';
        const productCode = e.extendedProps?.product_code || '';
        const company = e.extendedProps?.company_name || '—';
        const assigned = e.extendedProps?.artist_name || 'Not assigned yet'; // ✅ Actual artist if provided

        const viewHref = e.extendedProps?.product_id
          ? (`/installation/orders/${e.extendedProps.product_id}`)
          : '#';

        // remove old modal if exists
        document.getElementById(modalId)?.remove();

        const html = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
          <div class="modal-header border-0 pb-0">
            <div style="margin-bottom: 10px;">
              <h6 class="text-muted fw-semibold mb-0" style="font-size:1rem; ">You have 1 scheduled task</h6> <!-- smaller -->
              <div class="small text-muted mb-1">Tasks on ${whenDate}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body pt-0">
            <div class="border rounded-3 p-3" style="background:#fff;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge d-inline-flex align-items-center gap-2"
                      style="background:#eef2ff;color:#3730a3;border-radius:999px;padding:.35rem .6rem;font-weight:600">
                  <i class="bi bi-truck"></i> Installation
                </span>
                <div class="text-muted small">${productCode}</div>
              </div>

              <div class="fw-bold mb-1" style="font-size:1.2rem; margin-bottom: 5px;">${productName}</div> <!-- bigger font -->

              <div class="d-flex flex-column gap-2 mt-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-clock-history text-muted"></i>
                  <span>${whenTime}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-building text-muted"></i>
                  <span>${company}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-person text-muted"></i>
                  <span>Assigned: ${assigned}</span> <!-- actual artist -->
                  <span class="ms-auto badge"
                        style="background:${bg};color:${tx};border-radius:999px;padding:.35rem .6rem">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
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

      datesSet: function () { /* keep empty */ }
    });

    calendar.render();

    // Toolbar actions
    const refetch = debounce(() => calendar.refetchEvents(), 250);
    btnToday?.addEventListener('click', () => { calendar.today(); calendar.refetchEvents(); });
    btnExport?.addEventListener('click', () => window.print());

    btnReset?.addEventListener('click', () => {
      // Clear EVERYTHING including date range
      if (inputStart) inputStart.value = '';
      if (inputEnd) inputEnd.value = '';
      if (inputSearch) inputSearch.value = '';
      if (selArtist) selArtist.value = '';
      if (selectAll) selectAll.checked = true;
      filterInputs.forEach(c => c.checked = true);

      // Optional: reset mini calendar visual
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

    // Sidebar collapse (remember)
    function setSidebar(collapsed) {
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
    setSidebar(stored);
    btnToggleSidebar?.addEventListener('click', () => {
      const collapsed = !wrapper.classList.contains('sidebar-collapsed');
      setSidebar(collapsed);
      localStorage.setItem('artistCalSidebarCollapsed', collapsed ? '1' : '0');
    });
  })();
});
