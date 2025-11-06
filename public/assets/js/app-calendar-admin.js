/**
 * Admin Calendar
 */

/**
 * ! Manages two calendars: Salesperson Calendar (meetings) and Delivery Calendar.
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
  const fcDayGridMonthButton = calendar.el.querySelector('.fc-dayGridMonth-button');
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
  if (fcDayGridMonthButton) {
    fcDayGridMonthButton.classList.remove('fc-button-primary');
    fcDayGridMonthButton.classList.add('btn-gray');
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
      return selected.length ? selected : ['meeting', 'delivery'];
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
          console.log('Fetched delivery events:', data);
          let filteredEvents = data.filter(event => calendars.includes(event.extendedProps.type) || calendars.includes('all'));
          successCallback(filteredEvents);
        },
        error: function (xhr) {
          console.error('Error fetching delivery events:', xhr.status, xhr.responseText);
          alert('Failed to load delivery calendar events.');
        }
      });
    }

    const refetchSalesperson = debounce(() => salespersonCalendar.refetchEvents(), 250);
    const refetchOrder = debounce(() => orderCalendar.refetchEvents(), 250);

    let salespersonCalendar = new Calendar(salespersonCalendarEl, {
      initialView: 'dayGridMonth',
      events: fetchSalespersonEvents,
      plugins: [dayGridPlugin, interactionPlugin],
      editable: false,
      dragScroll: false,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: { text: 'Sidebar' }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: ''
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: false,
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
    let extraInfo = '';
    if (info.event.extendedProps.created_by) {
      extraInfo = `<div class="col-md-6"><div class="mb-2"><i class="bx bx-user me-1"></i>Created By: ${info.event.extendedProps.created_by}</div></div>`;
    }
    let statusBadge = '';
    if (isReminder) {
      const statusColor = info.event.extendedProps.status === 'upcoming' ? 'bg-secondary' : info.event.extendedProps.status === 'overdue' ? 'bg-danger' : 'bg-success';
      const statusText = info.event.extendedProps.status ? info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) : 'Upcoming';
      statusBadge = `<span class="badge ${statusColor} me-2">${statusText}</span>`;
    } else {
      const statusColor = info.event.extendedProps.status === 'scheduled' ? 'bg-primary' : info.event.extendedProps.status === 'canceled' ? 'bg-dark' : 'bg-warning';
      const statusText = info.event.extendedProps.status ? info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) : 'Scheduled';
      statusBadge = `<span class="badge ${statusColor} me-2">${statusText}</span>`;
    }
    let modalBody = `
      <div class="row mb-3">
        <div class="col-12">
          <h4 class="mb-1 fw-bold">${info.event.title || 'Untitled'}</h4>
          <div class="text-muted">${statusBadge}${isReminder ? '🔔 Reminder' : '🗂 Meeting'} | Lead: <span id="eventLeadDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="mb-2"><i class="bx bx-time-five me-1"></i>Start Time: <span id="eventStartTimeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>
          <div class="mb-2">Status: <span id="eventStatusDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>
          ${!isReminder ? `<div class="mb-2"><i class="bx bx-video me-1"></i>Meeting Type: <span id="eventMeetingTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>` : ''}
          ${extraInfo}
        </div>
        <div class="col-md-6">
          ${!isReminder ? `<div class="mb-2"><i class="bx bx-stopwatch me-1"></i>Duration: <span id="eventDurationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>
          <div class="mb-2"><i class="bx bx-location-plus me-1"></i>Location / URL: <span id="eventUrlLocationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>` : ''}
        </div>
      </div>
      <hr class="my-3">
      <div class="row">
        <div class="col-12">
          <div class="mb-3"><strong>Description:</strong></div>
          <p id="eventDescriptionDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></p>
        </div>
      </div>`;

    $('body').append(`
      <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
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
  const isReminder = info.event.extendedProps.type === 'reminder';
  $('#eventLeadDetail_' + safeId).text(info.event.extendedProps.lead_text || 'N/A');
  $('#eventStatusDetail_' + safeId).text(info.event.extendedProps.status ? info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) : 'N/A');
  if (isReminder) {
    $('#eventStartTimeDetail_' + safeId).text(info.event.extendedProps.remind_at ? moment(info.event.extendedProps.remind_at).format('MMM DD, YYYY — hh:mm A') : 'N/A');
  } else {
    $('#eventStartTimeDetail_' + safeId).text(moment(info.event.start).format('MMM DD, YYYY — hh:mm A') || 'N/A');
    const durationMin = moment(info.event.end).diff(moment(info.event.start), 'minutes');
    const durationFormatted = durationMin >= 60 ? `${Math.floor(durationMin / 60)} hr ${durationMin % 60} min` : `${durationMin} min`;
    $('#eventDurationDetail_' + safeId).text(durationFormatted);
    $('#eventMeetingTypeDetail_' + safeId).text(info.event.extendedProps.meeting_type ? (info.event.extendedProps.meeting_type.charAt(0).toUpperCase() + info.event.extendedProps.meeting_type.slice(1)) : 'N/A');
    let urlLocation = '';
    if (info.event.extendedProps.url) {
      urlLocation = `<a href="${info.event.extendedProps.url}" target="_blank" class="btn btn-sm btn-outline-primary">Join Meeting</a>`;
    } else if (info.event.extendedProps.location) {
      urlLocation = info.event.extendedProps.location;
    } else {
      urlLocation = 'N/A';
    }
    $('#eventUrlLocationDetail_' + safeId).html(urlLocation);
  }
  $('#eventDescriptionDetail_' + safeId).text(info.event.extendedProps.note || 'N/A');

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
      plugins: [dayGridPlugin, interactionPlugin],
      editable: false,
      dragScroll: false,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: { text: 'Sidebar' }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: ''
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: false,
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
        console.log('Delivery Event Click Data:', info.event.extendedProps);
        let modalId = 'orderDetailModal_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        if (!$('#' + modalId).length) {
          const modalBody = `
<p><strong>Order Number:</strong> <span id="orderNumberDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Product:</strong> <span id="orderProductDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Method:</strong> <span id="orderMethodDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Quantity:</strong> <span id="orderQuantityDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Location:</strong> <span id="orderLocationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Deliver/Install Type:</strong> <span id="orderDeliverTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Outsource Cost:</strong> <span id="orderOutsourceCostDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Lead Name:</strong> <span id="orderLeadNameDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Lead Company:</strong> <span id="orderLeadCompanyDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
<p><strong>Description:</strong> <span id="orderDescriptionDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>`;

          $('body').append(`
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="${modalId}Label">Delivery Details</h5>
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
        $('#orderProductDetail_' + safeId).text(info.event.extendedProps.product_name || 'N/A');
        $('#orderMethodDetail_' + safeId).text(info.event.extendedProps.method ? info.event.extendedProps.method.replace('_', ' ') : 'N/A');
        $('#orderQuantityDetail_' + safeId).text(info.event.extendedProps.quantity || 'N/A');
        $('#orderLocationDetail_' + safeId).text(info.event.extendedProps.location || 'N/A');
        $('#orderDeliverTypeDetail_' + safeId).text(info.event.extendedProps.deliver_install_type || 'N/A');
        $('#orderOutsourceCostDetail_' + safeId).text(info.event.extendedProps.outsource_cost ? '$' + info.event.extendedProps.outsource_cost : 'N/A');
        const orderLeadText = info.event.extendedProps.lead_text || 'N/A';
        let orderLeadName = 'N/A', orderLeadCompany = 'N/A';
        if (orderLeadText !== 'N/A' && orderLeadText !== 'Unknown') {
          const parts = orderLeadText.split(' - ');
          if (parts.length === 2) {
            orderLeadCompany = parts[0];
            orderLeadName = parts[1];
          }
        }
        $('#orderLeadNameDetail_' + safeId).text(orderLeadName);
        $('#orderLeadCompanyDetail_' + safeId).text(orderLeadCompany);
        $('#orderDescriptionDetail_' + safeId).text(info.event.extendedProps.description || 'N/A');

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
  const target = $(e.target).attr('href');

  if (target === '#order-calendar') {
    orderCalendar.updateSize(); // Adjust after showing Delivery calendar
  } 
  else if (target === '#salesperson-calendar') {
    salespersonCalendar.updateSize(); // Adjust after showing Salesperson calendar
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

    // Toolbar actions for Delivery Calendar
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