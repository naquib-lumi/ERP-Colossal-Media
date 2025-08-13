
/**
 * App Calendar
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Dependency checks
  if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded');
    return;
  }
  if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap is not loaded');
    return;
  }
  if (typeof FullCalendar === 'undefined') {
    console.error('FullCalendar is not loaded');
    return;
  }
  if (typeof flatpickr === 'undefined') {
    console.error('Flatpickr is not loaded');
    return;
  }
  if (typeof Select2 === 'undefined') {
    console.error('Select2 is not loaded');
    return;
  }

  // DOM Elements
  const calendarEl = document.getElementById('calendar');
  const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
  const appOverlay = document.querySelector('.app-overlay');
  const reminderSidebar = document.getElementById('addReminderSidebar');
  const meetingSidebar = document.getElementById('addMeetingSidebar');
  const reminderForm = document.getElementById('reminderForm');
  const meetingForm = document.getElementById('meetingForm');
  const selectAll = document.querySelector('.select-all');
  const filterInputs = document.querySelectorAll('.input-filter');
  const inlineCalendar = document.querySelector('.inline-calendar');

  // Offcanvas Instances
  const bsReminderSidebar = reminderSidebar ? new bootstrap.Offcanvas(reminderSidebar) : null;
  const bsMeetingSidebar = meetingSidebar ? new bootstrap.Offcanvas(meetingSidebar) : null;

  // Initialize Select2 for dropdowns
  $('.select2').select2({
    placeholder: 'Select an option',
    allowClear: true,
    dropdownParent: $('.offcanvas-body') // Render within offcanvas
  });

  // Initialize Flatpickr for inline calendar
  let inlineCalInstance = null;
  if (inlineCalendar) {
    inlineCalInstance = flatpickr(inlineCalendar, {
      monthSelectorType: 'static',
      static: true,
      inline: true,
      onChange: function (selectedDates) {
        if (selectedDates.length) {
          calendar.gotoDate(selectedDates[0]);
          appCalendarSidebar.classList.remove('show');
          appOverlay.classList.remove('show');
        }
      }
    });
  }

  // Modify sidebar toggler
  function modifyToggler() {
    const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
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
        '<i class="bx bx-menu icon-lg text-heading"></i>'
      );
    }
  }

  // Filter events by type
  function selectedCalendars() {
    return Array.from(filterInputs)
      .filter(item => item.checked)
      .map(item => item.getAttribute('data-value'));
  }

  // Initialize FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: '/calendar/events', // Fetch from Laravel endpoint
    plugins: ['dayGrid', 'interaction', 'list', 'timeGrid'],
    editable: true,
    dragScroll: true,
    dayMaxEvents: 2,
    eventResizableFromStart: true,
    customButtons: {
      sidebarToggle: {
        text: 'Sidebar',
        click: function () {
          appCalendarSidebar.classList.toggle('show');
          appOverlay.classList.toggle('show');
        }
      }
    },
    headerToolbar: {
      start: 'sidebarToggle, prev,next, title',
      end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    direction: isRtl ? 'rtl' : 'ltr',
    navLinks: true,
    eventClassNames: function ({ event }) {
      const type = event.extendedProps.type;
      const color = type === 'meeting' ? 'primary' : 'warning';
      return ['bg-label-' + color];
    },
    eventClick: function (info) {
      alert('Event: ' + info.event.title + '\nType: ' + info.event.extendedProps.type + '\nStatus: ' + info.event.extendedProps.status);
    },
    dateClick: function (info) {
      if (bsReminderSidebar) {
        bsReminderSidebar.show();
        document.getElementById('reminderDueDate').value = moment(info.date).format('YYYY-MM-DDTHH:mm');
      }
    },
    datesSet: function () {
      modifyToggler();
    },
    viewDidMount: function () {
      modifyToggler();
    }
  });
  calendar.render();

  // Reminder form submission
  if (reminderForm) {
    reminderForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('/calendar/reminders', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          calendar.refetchEvents();
          bsReminderSidebar.hide();
          reminderForm.reset();
          $('.select2').select2('destroy').select2({ // Reinitialize Select2
            placeholder: 'Select an option',
            allowClear: true,
            dropdownParent: $('.offcanvas-body')
          });
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to add reminder');
      });
    });
  }

  // Meeting form submission
  if (meetingForm) {
    meetingForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('/calendar/meetings', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          calendar.refetchEvents();
          bsMeetingSidebar.hide();
          meetingForm.reset();
          $('.select2').select2('destroy').select2({ // Reinitialize Select2
            placeholder: 'Select an option',
            allowClear: true,
            dropdownParent: $('.offcanvas-body')
          });
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to add meeting');
      });
    });
  }

  // Toggle URL/Location fields for meeting form
  document.querySelectorAll('input[name="type"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      document.getElementById('onlineUrl').style.display = this.value === 'online' ? 'block' : 'none';
      document.getElementById('offlineLocation').style.display = this.value === 'offline' ? 'block' : 'none';
    });
  });

  // Filter events
  if (filterInputs) {
    filterInputs.forEach(item => {
      item.addEventListener('click', () => {
        const checkedCount = document.querySelectorAll('.input-filter:checked').length;
        selectAll.checked = checkedCount === filterInputs.length;
        calendar.getEvents().forEach(event => {
          const eventType = event.extendedProps.type.toLowerCase();
          event.setProp('display', selectedCalendars().includes(eventType) || selectAll.checked ? 'auto' : 'none');
        });
      });
    });
  }

  if (selectAll) {
    selectAll.addEventListener('click', e => {
      filterInputs.forEach(c => (c.checked = e.currentTarget.checked));
      calendar.getEvents().forEach(event => {
        event.setProp('display', e.currentTarget.checked ? 'auto' : 'none');
      });
    });
  }
});
