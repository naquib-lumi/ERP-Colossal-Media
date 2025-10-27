/**
 * Admin Calendar
 */

/**
 * ! Manages two calendars: Salesperson Calendar (meetings/reminders) and Orders Calendar.
 * ! Events are fetched dynamically from /calendar/events and /calendar/order-events.
 * ! Supports filtering by salesperson and event type.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  console.log('jQuery:', typeof jQuery);
  console.log('Bootstrap:', typeof bootstrap);
  console.log('Calendar:', typeof Calendar);
  console.log('dayGridPlugin:', typeof dayGridPlugin);
  console.log('interactionPlugin:', typeof interactionPlugin);
  console.log('listPlugin:', typeof listPlugin);
  console.log('timegridPlugin:', typeof timegridPlugin);
  console.log('Select2:', typeof Select2);
  console.log('flatpickr:', typeof flatpickr);

  const direction = isRtl ? 'rtl' : 'ltr';
  (function () {
    const salespersonCalendarEl = document.getElementById('salespersonCalendar');
    const orderCalendarEl = document.getElementById('orderCalendar');
    const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
    const appOverlay = document.querySelector('.app-overlay');
    const btnToggleSidebar = document.querySelector('.fc-sidebarToggle-button');
    const selectAll = document.querySelector('.select-all');
    const filterInputs = document.querySelectorAll('.input-filter') || [];
    const inlineCalendar = document.querySelector('.inline-calendar');
    const selSalesperson = document.getElementById('filter-salesperson');
    const btnToday = document.getElementById('btnToday');
    const btnReset = document.getElementById('btnReset');
    const orderBtnToday = document.getElementById('orderBtnToday');
    const orderBtnReset = document.getElementById('orderBtnReset');

    let inlineCalInstance = null;
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        monthSelectorType: 'static',
        static: true,
        inline: true
      });
    }

    function modifyToggler(calendar) {
      const fcSidebarToggleButton = calendar.el.querySelector('.fc-sidebarToggle-button');
      if (fcSidebarToggleButton) {
        fcSidebarToggleButton.classList.remove('fc-button-primary');
        fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');
        while (fcSidebarToggleButton.firstChild) {
          fcSidebarToggleButton.firstChild.remove();
        }
        fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
        fcSidebarToggleButton.setAttribute('data-overlay', '');
        fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
        fcSidebarToggleButton.insertAdjacentHTML(
          'beforeend',
          '<i class="icon-base bx bx-menu icon-lg text-heading"></i>'
        );
      }
    }

    function selectedCalendars() {
      let selected = [];
      if (filterInputs.length > 0) {
        filterInputs.forEach(item => {
          if (item.checked) {
            selected.push(item.getAttribute('data-value'));
          }
        });
      }
      return selected.length ? selected : ['meeting', 'reminder', 'order'];
    }

    const debounce = (fn, ms) => {
      let t;
      return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
      };
    };

    function fetchSalespersonEvents(info, successCallback) {
      let calendars = selectedCalendars();
      const salespersonId = selSalesperson ? selSalesperson.value : $('meta[name="user-id"]').attr('content');
      $.ajax({
        url: '/calendar/admin-events',
        type: 'GET',
        data: {
          start: info.startStr,
          end: info.endStr,
          salesperson_id: salespersonId
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
          console.log('Fetched salesperson events:', data);
          let filteredEvents = data.filter(event => calendars.includes(event.extendedProps.type) || calendars.includes('all'));
          filteredEvents = filteredEvents.map(event => {
            if (event.extendedProps.type === 'reminder') {
              return { ...event, allDay: false };
            }
            return event;
          });
          successCallback(filteredEvents);
        },
        error: function (xhr) {
          console.error('Error fetching salesperson events:', xhr.status, xhr.responseText);
          alert('Failed to load salesperson calendar events.');
        }
      });
    }

    function fetchOrderEvents(info, successCallback) {
      let calendars = selectedCalendars();
      $.ajax({
        url: '/calendar/order-events',
        type: 'GET',
        data: {
          start: info.startStr,
          end: info.endStr
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
          console.log('Fetched order events:', data);
          let filteredEvents = data.filter(event => calendars.includes(event.extendedProps.type) || calendars.includes('all'));
          successCallback(filteredEvents);
        },
        error: function (xhr) {
          console.error('Error fetching order events:', xhr.status, xhr.responseText);
          alert('Failed to load order calendar events.');
        }
      });
    }

    const refetchSalesperson = debounce(() => salespersonCalendar.refetchEvents(), 250);
    const refetchOrder = debounce(() => orderCalendar.refetchEvents(), 250);

    let salespersonCalendar = new Calendar(salespersonCalendarEl, {
      initialView: 'dayGridMonth',
      events: fetchSalespersonEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      editable: false,
      dragScroll: false,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: { text: 'Sidebar' }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true,
      eventDidMount: function (info) {
        info.el.style.backgroundColor = info.event.backgroundColor;
        info.el.style.borderColor = info.event.borderColor;
        info.el.style.color = info.event.textColor || '#fff';
        const dotEl = info.el.querySelector('.fc-daygrid-event-dot');
        if (dotEl) {
          dotEl.style.borderColor = info.event.borderColor;
        }
      },
      eventClick: function (info) {
        console.log('Event Click Data:', info.event.extendedProps);
        let modalId = 'eventDetailModal_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        if (!$('#' + modalId).length) {
          const isReminder = info.event.extendedProps.type === 'reminder';
          let modalBody = `
<p><strong>Title:</strong> <span id="eventTitleDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Type:</strong> <span id="eventTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Created by:</strong> <span id="eventCreatorDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Status:</strong> <span id="eventStatusDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>`;

          if (isReminder) {
            modalBody += `
<p><strong>Remind At:</strong> <span id="eventRemindAtDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Description:</strong> <span id="eventDescriptionDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>`;
          } else {
            modalBody += `
<p><strong>Start Time:</strong> <span id="eventStartTimeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Duration:</strong> <span id="eventDurationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Meeting Type:</strong> <span id="eventMeetingTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>URL/Location:</strong> <span id="eventUrlLocationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Description:</strong> <span id="eventDescriptionDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>`;
          }

          $('body').append(`
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="${modalId}Label">${isReminder ? 'Reminder' : 'Meeting'} Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    ${modalBody}
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          `);
        }

        const safeId = info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        $('#eventTitleDetail_' + safeId).text(info.event.title || 'N/A');
        $('#eventTypeDetail_' + safeId).text(info.event.extendedProps.type || 'N/A');
        $('#eventCreatorDetail_' + safeId).text(info.event.extendedProps.created_by || 'N/A');
        $('#eventStatusDetail_' + safeId).text(info.event.extendedProps.status || 'N/A');
        if (info.event.extendedProps.type === 'reminder') {
          $('#eventRemindAtDetail_' + safeId).text(info.event.extendedProps.remind_at ? moment(info.event.extendedProps.remind_at).format('YYYY-MM-DD HH:mm') : 'N/A');
          $('#eventDescriptionDetail_' + safeId).text(info.event.extendedProps.note || 'N/A');
        } else {
          $('#eventStartTimeDetail_' + safeId).text(moment(info.event.start).format('YYYY-MM-DD HH:mm') || 'N/A');
          $('#eventDurationDetail_' + safeId).text(moment(info.event.end).diff(moment(info.event.start), 'minutes') + ' minutes' || 'N/A');
          $('#eventMeetingTypeDetail_' + safeId).text(info.event.extendedProps.meeting_type || 'N/A');
          $('#eventUrlLocationDetail_' + safeId).text(info.event.extendedProps.url || info.event.extendedProps.location || 'N/A');
          $('#eventDescriptionDetail_' + safeId).text(info.event.extendedProps.note || 'N/A');
        }

        const eventModal = new bootstrap.Modal(document.getElementById(modalId));
        eventModal.show();
      },
      datesSet: function () {
        modifyToggler(salespersonCalendar);
      },
      viewDidMount: function () {
        modifyToggler(salespersonCalendar);
      }
    });

    let orderCalendar = new Calendar(orderCalendarEl, {
      initialView: 'dayGridMonth',
      events: fetchOrderEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      editable: false,
      dragScroll: false,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: { text: 'Sidebar' }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true,
      eventDidMount: function (info) {
        info.el.style.backgroundColor = info.event.backgroundColor;
        info.el.style.borderColor = info.event.borderColor;
        info.el.style.color = info.event.textColor || '#fff';
        const dotEl = info.el.querySelector('.fc-daygrid-event-dot');
        if (dotEl) {
          dotEl.style.borderColor = info.event.borderColor;
        }
      },
      eventClick: function (info) {
        console.log('Order Event Click Data:', info.event.extendedProps);
        let modalId = 'orderDetailModal_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        if (!$('#' + modalId).length) {
          const modalBody = `
<p><strong>Order Number:</strong> <span id="orderNumberDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Title:</strong> <span id="orderTitleDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Assigned to:</strong> <span id="orderAssignedToDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Status:</strong> <span id="orderStatusDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Order Date:</strong> <span id="orderStartDateDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Deadline:</strong> <span id="orderEndDateDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Description:</strong> <span id="orderDescriptionDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Approval:</strong> <span id="orderApprovalDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>`;


          $('body').append(`
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="${modalId}Label">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    ${modalBody}
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          `);
        }

        const safeId = info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        $('#orderNumberDetail_' + safeId).text(info.event.extendedProps.order_number || 'N/A');
        $('#orderTitleDetail_' + safeId).text(info.event.title || 'N/A');
        $('#orderTypeDetail_' + safeId).text(info.event.extendedProps.type || 'N/A');
        $('#orderAssignedToDetail_' + safeId).text(info.event.extendedProps.assigned_to || 'N/A');
        $('#orderStatusDetail_' + safeId).text(info.event.extendedProps.status || 'N/A');
        $('#orderStartDateDetail_' + safeId).text(moment(info.event.extendedProps.order_date).format('YYYY-MM-DD') || 'N/A');
        $('#orderEndDateDetail_' + safeId).text(moment(info.event.start).format('YYYY-MM-DD') || 'N/A');
        $('#orderDescriptionDetail_' + safeId).text(info.event.extendedProps.description || 'N/A');
        $('#orderApprovalDetail_' + safeId).text(info.event.extendedProps.approval ? 'Yes' : 'No');
        $('#orderDraftDetail_' + safeId).text(info.event.extendedProps.draft ? 'Yes' : 'No');
        $('#orderPendingDetail_' + safeId).text(info.event.extendedProps.pending ? 'Yes' : 'No');

        const eventModal = new bootstrap.Modal(document.getElementById(modalId));
        eventModal.show();
      },
      datesSet: function () {
        modifyToggler(orderCalendar);
      },
      viewDidMount: function () {
        modifyToggler(orderCalendar);
      }
    });

    salespersonCalendar.render();
    orderCalendar.render();

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
  if ($(e.target).attr('href') === '#order-calendar') {
    orderCalendar.updateSize(); //Need to folow prev calendar size
  }
});


 

if (btnToggleSidebar) {
  btnToggleSidebar.addEventListener('click', e => {
    appCalendarSidebar.classList.remove('show');
    appOverlay.classList.remove('show');
  });
}

    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        appCalendarSidebar.classList.remove('show');
        appOverlay.classList.remove('show');
      });
    }

    // Toolbar actions for Salesperson Calendar
    btnToday?.addEventListener('click', () => {
      salespersonCalendar.today();
      refetchSalesperson();
    });
    btnReset?.addEventListener('click', () => {
      if (selSalesperson) selSalesperson.value = '';
      if (selectAll) selectAll.checked = true;
      filterInputs.forEach(c => c.checked = true);
      salespersonCalendar.today();
      refetchSalesperson();
    });
    selSalesperson?.addEventListener('change', refetchSalesperson);

    // Toolbar actions for Orders Calendar
    orderBtnToday?.addEventListener('click', () => {
      orderCalendar.today();
      refetchOrder();
    });
    orderBtnReset?.addEventListener('click', () => {
      if (selectAll) selectAll.checked = true;
      filterInputs.forEach(c => c.checked = true);
      orderCalendar.today();
      refetchOrder();
    });

    if (selectAll) {
      selectAll.addEventListener('click', e => {
        const checked = e.currentTarget.checked;
        filterInputs.forEach(c => c.checked = checked);
        refetchSalesperson();
        refetchOrder();
      });
    }

    if (filterInputs.length > 0) {
      filterInputs.forEach(item => {
        item.addEventListener('click', () => {
          const checkedCount = Array.from(filterInputs).filter(f => f.checked).length;
          const totalCount = filterInputs.length;
          selectAll.checked = checkedCount === totalCount;
          refetchSalesperson();
          refetchOrder();
        });
      });
    }

    if (inlineCalInstance) {
      inlineCalInstance.config.onChange.push(function (date) {
        if (!date?.length) return;
        salespersonCalendar.gotoDate(moment(date[0]).format('YYYY-MM-DD'));
        orderCalendar.gotoDate(moment(date[0]).format('YYYY-MM-DD'));
        modifyToggler(salespersonCalendar);
        modifyToggler(orderCalendar);
        appCalendarSidebar.classList.remove('show');
        appOverlay.classList.remove('show');
        refetchSalesperson();
        refetchOrder();
      });
    }
  })();
});