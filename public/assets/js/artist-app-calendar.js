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
    const selectAll = document.getElementById('selectAll');
    const filterInputs = document.querySelectorAll('.input-filter');
    const inlineCalendar = document.querySelector('.inline-calendar');
    const upcomingList = document.getElementById('upcomingList');

    // Toolbar
    const inputStart  = document.getElementById('filterStart');
    const inputEnd    = document.getElementById('filterEnd');
    const inputSearch = document.getElementById('searchClient');
    const selArtist   = document.getElementById('filterSalesperson');
    const btnToday    = document.getElementById('btnToday');
    const btnReset    = document.getElementById('btnReset');
    const btnExport   = document.getElementById('btnExport');

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
        monthSelectorType:'static', static:true, inline:true,
        onChange: d => {
          if (!d?.length) return;
          calendar.changeView(calendar.view.type, moment(d[0]).format('YYYY-MM-DD'));
          calendar.refetchEvents();
        }
      });
    }

    // --- helpers ---
    const debounce = (fn, ms) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
    function selectedCalendars() {
      const sel = [];
      filterInputs.forEach(i => i.checked && sel.push(i.getAttribute('data-value')));
      return sel.length ? sel : ['meeting','reminder'];
    }

    function renderUpcoming(events) {
      if (!upcomingList) return;
      const now = new Date();
      const items = events
        .filter(e => e.extendedProps?.type === 'meeting' && e.start && new Date(e.start) >= now)
        .sort((a,b) => new Date(a.start) - new Date(b.start))
        .slice(0, 10);

      if (!items.length) {
        upcomingList.innerHTML = '<div class="text-muted small">No upcoming items.</div>';
        return;
      }
      const html = items.map(ev => {
        const when = new Date(ev.start);
        const bg = ev.backgroundColor || '#6c757d';
        const tx = ev.textColor || '#fff';
        const status = (ev.extendedProps?.status || 'scheduled').toLowerCase();
        return `
          <div class="d-flex align-items-start gap-2 p-2 border-bottom">
            <span class="rounded-circle mt-1 flex-shrink-0" style="width:8px;height:8px;background:${bg}"></span>
            <div class="flex-grow-1">
              <div class="fw-semibold small mb-1" style="font-size:1rem;">${ev.title || 'Untitled'}</div>
              <div class="text-muted extra-small" style="font-size:0.8rem; margin-bottom:5px;">${when.toLocaleString()}</div>
              <span class="badge border-0" style="background:${bg};color:${tx}; margin-bottom:10px;">${status}</span>
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
        Array.from(map.entries()).sort((a,b)=>a[1].localeCompare(b[1]))
          .map(([id,name]) => `<option value="${id}">${name}</option>`).join('');
      if (cur) selArtist.value = cur;
    }

    function fetchEvents(info, success, failure) {
      const calendars = selectedCalendars();
      const q = (inputSearch?.value || '').trim().toLowerCase();
      const artistFilter = selArtist?.value || '';

      // If date range fields are empty, just use FullCalendar's visible window.
      const startStr = inputStart?.value ? (inputStart.value + 'T00:00:00') : info.startStr;
      const endStr   = inputEnd?.value   ? (inputEnd.value   + 'T23:59:59') : info.endStr;

      $.ajax({
        url: '/artist/calendar/events',
        type: 'GET',
        data: { start: startStr, end: endStr },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          // We trust server colors – just ensure DOM gets them
          let evs = data.map(ev => ({ ...ev, allDay: ev.allDay ?? false }));

          // Filters
          evs = evs.filter(ev => calendars.includes(ev.extendedProps.type) || calendars.includes('all'));
          if (artistFilter) evs = evs.filter(ev => String(ev.extendedProps?.artist_id||'') === String(artistFilter));
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
      headerToolbar: { start: 'prev,next, title', end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' },
      height: 'auto',
      dayMaxEvents: 2,
      editable: false,
      events: fetchEvents,
      dateClick: null,

      // Enforce server colors on the element (prevents theme overrides)
      eventDidMount: function (info) {
        const e = info.event;
        if (e.backgroundColor)  info.el.style.backgroundColor = e.backgroundColor;
        if (e.borderColor)      info.el.style.borderColor     = e.borderColor;
        if (e.textColor)        info.el.style.color           = e.textColor;
        const dot = info.el.querySelector('.fc-daygrid-event-dot');
        if (dot && e.borderColor) dot.style.borderColor = e.borderColor;
      },

      // Read-only detail modal
      eventClick: function (info) {
        const e = info.event;
        const safeId = (e.id || 'evt').toString().replace(/[^a-zA-Z0-9]/g, '');
        const modalId = 'eventDetailModal_' + safeId;
        const bg = e.backgroundColor || '#6c757d';
        const tx = e.textColor || '#fff';
        const status = (e.extendedProps?.status || 'scheduled').toLowerCase();

        if (!$('#' + modalId).length) {
          $('body').append(`
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="${modalId}Label">${e.extendedProps?.type === 'reminder' ? 'Reminder' : 'Meeting'} Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p><strong>Title:</strong> ${e.title || 'N/A'}</p>
                  <p><strong>Salesperson:</strong> ${e.extendedProps?.artist_name || ('ID '+(e.extendedProps?.artist_id||''))}</p>
                  <p><strong>Status:</strong> <span class="badge" style="background:${bg};color:${tx}">${status}</span></p>
                  <p><strong>Start:</strong> ${e.start ? moment(e.start).format('YYYY-MM-DD HH:mm') : 'N/A'}</p>
                  <p><strong>End:</strong> ${e.end ? moment(e.end).format('YYYY-MM-DD HH:mm') : 'N/A'}</p>
                  ${e.extendedProps?.meeting_type ? `<p><strong>Type:</strong> ${e.extendedProps.meeting_type}</p>` : ''}
                  ${e.extendedProps?.url ? `<p><strong>URL:</strong> <a href="${e.extendedProps.url}" target="_blank" rel="noopener">Open</a></p>` : ''}
                  ${e.extendedProps?.location ? `<p><strong>Location:</strong> ${e.extendedProps.location}</p>` : ''}
                  ${e.extendedProps?.note ? `<p><strong>Note:</strong> ${e.extendedProps.note}</p>` : ''}
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div></div></div>
          `);
        }
        new bootstrap.Modal(document.getElementById(modalId)).show();
      },

      // IMPORTANT: leave the date inputs EMPTY by default (no auto-fill)
      datesSet: function () { /* intentionally empty */ }
    });

    calendar.render();

    // Toolbar actions
    const refetch = debounce(() => calendar.refetchEvents(), 250);
    btnToday?.addEventListener('click', () => { calendar.today(); calendar.refetchEvents(); });
    btnExport?.addEventListener('click', () => window.print());

    btnReset?.addEventListener('click', () => {
      // Clear EVERYTHING including date range
      if (inputStart) inputStart.value = '';
      if (inputEnd)   inputEnd.value = '';
      if (inputSearch) inputSearch.value = '';
      if (selArtist)  selArtist.value = '';
      if (selectAll)  selectAll.checked = true;
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

    // Sidebar filters
    selectAll?.addEventListener('click', e => {
      const checked = e.currentTarget.checked;
      filterInputs.forEach(c => c.checked = checked);
      calendar.refetchEvents();
    });
    filterInputs.forEach(i => i.addEventListener('click', () => {
      const total = filterInputs.length;
      const checked = Array.from(filterInputs).filter(f => f.checked).length;
      if (selectAll) selectAll.checked = checked === total;
      calendar.refetchEvents();
    }));

    // Sidebar collapse (remember)
    function setSidebar(collapsed) {
      if (!wrapper) return;
      if (collapsed) {
        wrapper.classList.add('sidebar-collapsed');
        btnToggleSidebar?.querySelector('span')?.replaceChildren(document.createTextNode('Show Sidebar'));
        btnToggleSidebar?.querySelector('i')?.classList.replace('bx-chevron-left','bx-chevron-right');
      } else {
        wrapper.classList.remove('sidebar-collapsed');
        btnToggleSidebar?.querySelector('span')?.replaceChildren(document.createTextNode('Hide Sidebar'));
        btnToggleSidebar?.querySelector('i')?.classList.replace('bx-chevron-right','bx-chevron-left');
      }
      setTimeout(() => calendar.updateSize(), 10);
    }
    const stored = localStorage.getItem('artistCalSidebarCollapsed') === '1';
    setSidebar(stored);
    btnToggleSidebar?.addEventListener('click', () => {
      const collapsed = !wrapper.classList.contains('sidebar-collapsed');
      setSidebar(collapsed);
      localStorage.setItem('artistCalSidebarCollapsed', collapsed ? '1':'0');
    });
  })();
});
