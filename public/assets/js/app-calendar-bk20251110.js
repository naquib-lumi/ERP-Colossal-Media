/**
 * App Calendar
 */

/**
 * ! If both start and end dates are same Full calendar will nullify the end date value.
 * ! Full calendar will end the event on a day before at 12:00:00AM thus, event won't extend to the end date.
 * ! We are fetching events dynamically from /calendar/events.
 *
 **/

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
    const calendarEl = document.getElementById('calendar');
    const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
    const appOverlay = document.querySelector('.app-overlay');
    const btnToggleSidebar = document.querySelector('.btn-toggle-sidebar');
    const selectAll = document.querySelector('.select-all');
    const filterInputs = document.querySelectorAll('.input-filter') || [];
    const inlineCalendar = document.querySelector('.inline-calendar');
    const reminderSidebar = document.getElementById('addReminderSidebar');
    const meetingSidebar = document.getElementById('addMeetingSidebar');
    const inputSearch = document.getElementById('searchClient');
    const selSalesperson = document.getElementById('filter-salesperson');
    const btnToday = document.getElementById('btnToday');
    const btnReset = document.getElementById('btnReset');

    const bsReminderSidebar = reminderSidebar ? new bootstrap.Offcanvas(reminderSidebar) : null;
    const bsMeetingSidebar = meetingSidebar ? new bootstrap.Offcanvas(meetingSidebar) : null;

    const reminderLeadId = $('#lead_id');
    const meetingLeadId = $('#meetingLeadId');

    // Initialize Select2 for lead selection
    if (reminderLeadId.length) {
      function renderBadges(option) {
        if (!option.id) return option.text;
        return `<span class='badge badge-dot bg-primary me-2'></span>${option.text}`;
      }
      reminderLeadId.select2({
        placeholder: 'Search for a lead',
        dropdownParent: reminderLeadId.parent(),
        templateResult: renderBadges,
        templateSelection: renderBadges,
        minimumInputLength: 2,
        ajax: {
          url: '/leads/search',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              query: params.term,
              _token: $('meta[name="csrf-token"]').attr('content')
            };
          },
          processResults: function (data) {
            return {
              results: data.map(lead => ({
                id: lead.id,
                text: lead.text
              }))
            };
          },
          cache: true
        },
        escapeMarkup: function (markup) {
          return markup;
        }
      });
    }
    if (meetingLeadId.length) {
      function renderBadges(option) {
        if (!option.id) return option.text;
        return `<span class='badge badge-dot bg-primary me-2'></span>${option.text}`;
      }
      meetingLeadId.select2({
        placeholder: 'Search for a lead',
        dropdownParent: meetingLeadId.parent(),
        templateResult: renderBadges,
        templateSelection: renderBadges,
        minimumInputLength: 2,
        ajax: {
          url: '/leads/search',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              query: params.term,
              _token: $('meta[name="csrf-token"]').attr('content')
            };
          },
          processResults: function (data) {
            return {
              results: data.map(lead => ({
                id: lead.id,
                text: lead.text
              }))
            };
          },
          cache: true
        },
        escapeMarkup: function (markup) {
          return markup;
        }
      });
    }

    let inlineCalInstance = null;
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        monthSelectorType: 'static',
        static: true,
        inline: true
      });
    }

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
    return selected;
  }

    const debounce = (fn, ms) => {
      let t;
      return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
      };
    };

    function fetchEvents(info, successCallback) {
      let calendars = selectedCalendars();
      const salespersonId = selSalesperson ? selSalesperson.value : $('meta[name="user-id"]').attr('content');
      const searchQuery = inputSearch ? inputSearch.value : '';
      $.ajax({
        url: '/calendar/events',
        type: 'GET',
        data: {
          start: info.startStr,
          end: info.endStr,
          salesperson_id: salespersonId,
          q: searchQuery
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
          console.log('Fetched events:', data);
          let filteredEvents = data.filter(event => calendars.includes(event.extendedProps.type) || calendars.includes('all'));
          filteredEvents = filteredEvents.map(event => {
            if (event.extendedProps.type === 'reminder') {
              return {
                ...event,
                allDay: false
              };
            }
            return event;
          });
          successCallback(filteredEvents);
        },
        error: function (xhr) {
          console.error('Error fetching events:', xhr.status, xhr.responseText);
          Swal.fire('Error!', 'Failed to load calendar events.', 'error');
        }
      });
    }

    const refetch = debounce(() => calendar.refetchEvents(), 250);

    let calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: fetchEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      editable: false,
      dragScroll: false,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      showNonCurrentDates: false,
      customButtons: {
        sidebarToggle: { text: 'Sidebar' }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: ''
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
      dateClick: function (info) {
        let date = moment(info.date).format('YYYY-MM-DDTHH:mm');
        if (bsReminderSidebar) {
          bsReminderSidebar.show();
          document.getElementById('reminderRemindAt').value = date;
        }
      },
     eventClick: function (info) {
    console.log('Event Click Data:', info.event.extendedProps);
  let modalId = 'eventDetailModal_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '');
  if (!$('#' + modalId).length) {
    console.log(info.event.extendedProps.is_head_salesperson);
    const isReminder = info.event.extendedProps.type === 'reminder';
    const isHeadSalesperson = info.event.extendedProps.is_head_salesperson;
    let extraInfo = '';
    if (isHeadSalesperson && info.event.extendedProps.created_by) {
      extraInfo = `<div class="col-md-6"><div class="mb-2"><i class="bx bx-user me-1"></i>Created By: ${info.event.extendedProps.created_by}</div></div>`;
    }
    let statusBadge = '';
    let statusSelect = '';
    if (isReminder) {
      const statusColor = info.event.extendedProps.status === 'upcoming' ? 'bg-secondary' : info.event.extendedProps.status === 'overdue' ? 'bg-danger' : 'bg-success';
      const statusText = info.event.extendedProps.status ? info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) : 'Upcoming';
      statusBadge = `<span class="badge ${statusColor} me-2">${statusText}</span>`;
      statusSelect = `
        <select id="eventStatusSelect_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" class="form-select form-select-sm">
          <option value="upcoming" ${info.event.extendedProps.status === "upcoming" ? "selected" : ""}>Upcoming</option>
          <option value="overdue" ${info.event.extendedProps.status === "overdue" ? "selected" : ""}>Overdue</option>
          <option value="completed" ${info.event.extendedProps.status === "completed" ? "selected" : ""}>Completed</option>
        </select>`;
    } else {
      const statusColor = info.event.extendedProps.status === 'scheduled' ? 'bg-primary' : info.event.extendedProps.status === 'canceled' ? 'bg-dark' : 'bg-warning';
      const statusText = info.event.extendedProps.status ? info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) : 'Scheduled';
      statusBadge = `<span class="badge ${statusColor} me-2">${statusText}</span>`;
      statusSelect = `
        <select id="eventStatusSelect_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" class="form-select form-select-sm">
          <option value="scheduled" ${info.event.extendedProps.status === "scheduled" ? "selected" : ""}>Scheduled</option>
          <option value="canceled" ${info.event.extendedProps.status === "canceled" ? "selected" : ""}>Canceled</option>
          <option value="postponed" ${info.event.extendedProps.status === "postponed" ? "selected" : ""}>Postponed</option>
        </select>`;
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
          <div class="mb-2">Status: ${statusSelect}</div>
          ${!isReminder ? `<div class="mb-2"><i class="bx bx-video me-1"></i>Meeting Type: <span id="eventMeetingTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>` : ''}
          ${extraInfo}
        </div>
        <div class="col-md-6">
          ${!isReminder ? `<div class="mb-2"><i class="bx bx-stopwatch me-1"></i>Duration: <span id="eventDurationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>
          <div class="mb-2"><i class="bx bx-link-alt me-1"></i>Location / URL: <span id="eventUrlLocationDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></div>` : ''}
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
              <button type="button" class="btn btn-danger btn-delete-event" data-event-id="${info.event.id}" data-event-type="${info.event.extendedProps.type}">Delete</button>
              <button type="button" class="btn btn-primary btn-edit-event" data-event-id="${info.event.id}" data-event-type="${info.event.extendedProps.type}">Edit</button>
            </div>
          </div>
        </div>
      </div>
      ${isReminder ? `
        <div class="modal fade" id="confirmCompleteModal_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" tabindex="-1" aria-labelledby="confirmCompleteLabel_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirmCompleteLabel_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}">Confirm Completion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p id="confirmCompleteText_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}">Is the reminder "${info.event.title || 'Untitled'}" completed?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-primary" id="confirmCompleteBtn_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" data-event-id="${info.event.id}">Yes</button>
              </div>
            </div>
          </div>
        </div>
      ` : ''}
    `);
  }

  const safeId = info.event.id.replace(/[^a-zA-Z0-9]/g, '');
  $('#eventLeadDetail_' + safeId).text(info.event.extendedProps.lead_text || 'N/A');
  const isHeadSalesperson = info.event.extendedProps.is_head_salesperson;
  if (isHeadSalesperson && info.event.extendedProps.created_by) {
    $(`#eventCreatorDetail_${safeId}`).text(info.event.extendedProps.created_by);
  }
  const isReminder = info.event.extendedProps.type === 'reminder';
  if (isReminder) {
    $(`#eventStartTimeDetail_${safeId}`).text(info.event.extendedProps.remind_at ? moment(info.event.extendedProps.remind_at).format('MMM DD, YYYY — hh:mm A') : 'N/A');
    $(`#eventDescriptionDetail_${safeId}`).text(info.event.extendedProps.note || 'N/A');
  } else {
    $(`#eventStartTimeDetail_${safeId}`).text(moment(info.event.start).format('MMM DD, YYYY — hh:mm A') || 'N/A');
    const durationMin = moment(info.event.end).diff(moment(info.event.start), 'minutes');
    const durationFormatted = durationMin >= 60 ? `${Math.floor(durationMin / 60)} hr ${durationMin % 60} min` : `${durationMin} min`;
    $(`#eventDurationDetail_${safeId}`).text(durationFormatted);
    $(`#eventMeetingTypeDetail_${safeId}`).text(info.event.extendedProps.meeting_type ? (info.event.extendedProps.meeting_type.charAt(0).toUpperCase() + info.event.extendedProps.meeting_type.slice(1)) : 'N/A');
    let urlLocation = '';
    if (info.event.extendedProps.url) {
      urlLocation = `<a href="${info.event.extendedProps.url}" target="_blank" class="btn btn-sm btn-outline-primary">Join Meeting</a>`;
    } else if (info.event.extendedProps.location) {
      urlLocation = info.event.extendedProps.location;
    } else {
      urlLocation = 'N/A';
    }
    $(`#eventUrlLocationDetail_${safeId}`).html(urlLocation);
    $(`#eventDescriptionDetail_${safeId}`).text(info.event.extendedProps.note || 'N/A');
  }

  const eventModal = new bootstrap.Modal(document.getElementById(modalId));
  eventModal.show();

  // Rest of the event handlers remain the same...

        $('.btn-edit-event').off('click').on('click', function () {
          eventModal.hide();
          const eventId = $(this).data('event-id');
          const type = $(this).data('event-type');
          const sidebar = type === 'meeting' ? bsMeetingSidebar : bsReminderSidebar;
          const form = type === 'meeting' ? document.getElementById('meetingForm') : document.getElementById('reminderForm');
          const titleElement = sidebar._element.querySelector('.offcanvas-title');
          const submitBtn = form.querySelector('button[type="submit"]');

          if (sidebar) {
            sidebar.show();
            titleElement.innerHTML = `Update ${type.charAt(0).toUpperCase()}${type.slice(1)}`;
            submitBtn.innerHTML = 'Update';
            submitBtn.classList.add('btn-update-event');
            submitBtn.classList.remove('btn-add-event');
            submitBtn.disabled = false;

            const id = eventId.replace(`${type}-`, '');
            form.querySelector('[name="id"]').value = id;
            const $leadSelect = $(form.querySelector('[name="lead_id"]'));
            const leadId = info.event.extendedProps.lead_id;
            const leadText = info.event.extendedProps.lead_text;

            if (leadId) {
              if (leadText && leadText !== 'undefined - undefined') {
                $leadSelect.append(new Option(leadText, leadId, true, true)).trigger('change');
              } else {
                $.ajax({
                  url: `/leads/${leadId}`,
                  type: 'GET',
                  headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  },
                  success: function (data) {
                    if (data && data.company_name && data.name) {
                      const text = `${data.company_name} - ${data.name}`;
                      $leadSelect.append(new Option(text, leadId, true, true)).trigger('change');
                    } else {
                      $leadSelect.append(new Option('Unknown Lead', leadId, true, true)).trigger('change');
                    }
                  },
                  error: function (xhr) {
                    console.error('Failed to fetch lead text:', xhr.status, xhr.responseText);
                    $leadSelect.append(new Option('Unknown Lead', leadId, true, true)).trigger('change');
                    if (xhr.status === 403) {
                      Swal.fire('Error!', 'You do not have permission to access this lead.', 'error');
                    } else {
                      Swal.fire('Error!', 'Failed to load lead data.', 'error');
                    }
                  }
                });
              }
            } else {
              $leadSelect.val(null).trigger('change');
            }

            form.querySelector('[name="title"]').value = info.event.title || '';
            if (type === 'meeting') {
              form.querySelector('[name="start_time"]').value = moment(info.event.start).format('YYYY-MM-DDTHH:mm') || '';
              form.querySelector('[name="duration"]').value = moment(info.event.end).diff(moment(info.event.start), 'minutes') || '';
              const meetingType = info.event.extendedProps.meeting_type || 'online';
              const radios = form.querySelectorAll('[name="type"]');
              radios.forEach(r => r.checked = false);
              const radio = form.querySelector(`[name="type"][value="${meetingType}"]`);
              if (radio) {
                radio.checked = true;
                const onlineUrl = document.getElementById('onlineUrl');
                const offlineLocation = document.getElementById('offlineLocation');
                if (onlineUrl && offlineLocation) {
                  onlineUrl.style.display = meetingType === 'online' ? 'block' : 'none';
                  offlineLocation.style.display = meetingType === 'offline' ? 'block' : 'none';
                }
                radio.dispatchEvent(new Event('change', { bubbles: true }));
              }
              form.querySelector('[name="url"]').value = info.event.extendedProps.url || '';
              form.querySelector('[name="location"]').value = info.event.extendedProps.location || '';
              form.querySelector('[name="note"]').value = info.event.extendedProps.note || '';
            } else {
              form.querySelector('[name="remind_at"]').value = info.event.extendedProps.remind_at ? moment(info.event.extendedProps.remind_at).format('YYYY-MM-DDTHH:mm') : moment(info.event.start).format('YYYY-MM-DDTHH:mm') || '';
              const descriptionField = form.querySelector('[name="description"]');
              if (descriptionField) {
                descriptionField.value = info.event.extendedProps.note || '';
              }
            }
          }
        });

        $('.btn-delete-event').off('click').on('click', function () {
    eventModal.hide();
Swal.fire({
title: 'Are you sure?',
text: "You won't be able to revert this!",
icon: 'warning',
showCancelButton: true,
confirmButtonColor: '#3085d6',
cancelButtonColor: '#d33',
confirmButtonText: 'Yes, delete it!'
          }).then((result) => {
            if (result.isConfirmed) {
              const eventId = $(this).data('event-id');
              const type = $(this).data('event-type');
              const id = eventId.replace(`${type}-`, '');
              $.ajax({
                url: `/calendar/${type}s/${id}`,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                  if (response.success) {
                    calendar.refetchEvents();
                    
                    eventModal.hide();
                    Swal.fire('Deleted!', 'Event has been deleted.', 'success');
                  } else {
                    Swal.fire('Error!', 'Error deleting event', 'error');
                  }
                },
                error: function () {
                  Swal.fire('Error!', 'Failed to delete event', 'error');
                }
              });
            }
          });
        });

        $('.btn-confirm-complete').off('click').on('click', function () {
          const eventId = $(this).data('event-id');
          const confirmModalId = 'confirmCompleteModal_' + eventId.replace(/[^a-zA-Z0-9]/g, '');
          new bootstrap.Modal(document.getElementById(confirmModalId)).show();
        });

        $(`#confirmCompleteBtn_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}`).off('click').on('click', function () {
          const eventId = $(this).data('event-id');
          $.ajax({
            url: '/calendar/reminders/' + eventId.replace('reminder-', '') + '/complete',
            type: 'POST',
            data: {
              _token: $('meta[name="csrf-token"]').attr('content'),
              confirm: 'yes'
            },
            success: function (response) {
              if (response.success) {
                calendar.refetchEvents();
                $('#confirmCompleteModal_' + eventId.replace(/[^a-zA-Z0-9]/g, '')).modal('hide');
                Swal.fire('Success!', response.message || 'Reminder marked as completed.', 'success');
              } else {
                Swal.fire('Error!', 'Error: ' + response.error, 'error');
              }
            },
            error: function (xhr) {
              console.error('Error confirming completion:', xhr.status, xhr.responseText);
              Swal.fire('Error!', 'Failed to confirm completion', 'error');
            }
          });
        });

       $(`#eventStatusSelect_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}`).on('change', function () {
  const newStatus = $(this).val();
  const type = info.event.extendedProps.type;
  const eventId = info.event.id.replace(`${type}-`, '');
  $.ajax({
    url: `/calendar/${type}s/${eventId}/update-status`,
    type: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      status: newStatus
    },
    success: function (response) {
      if (response.success) {
        // Update badge here
        const statusBadgeEl = eventModal._element.querySelector('.badge');
        if (statusBadgeEl) {
          let statusColor, statusText;
          if (type === 'reminder') {
            statusColor = newStatus === 'upcoming' ? 'bg-secondary' : newStatus === 'overdue' ? 'bg-danger' : 'bg-success';
            statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
          } else {
            statusColor = newStatus === 'scheduled' ? 'bg-primary' : newStatus === 'canceled' ? 'bg-dark' : 'bg-warning';
            statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
          }
          statusBadgeEl.className = `badge ${statusColor} me-2`;
          statusBadgeEl.textContent = statusText;
        }
        calendar.refetchEvents();
        eventModal.hide();
        Swal.fire('Updated!', 'Status has been updated.', 'success');
      } else {
        Swal.fire('Error!', 'Error updating status', 'error');
      }
    },
    error: function (xhr) {
      Swal.fire('Error!', 'Failed to update status', 'error');
    }
  });
});
      },
      datesSet: function () {
        modifyToggler();
      },
      viewDidMount: function () {
        modifyToggler();
      }
    });

    calendar.render();

    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        appCalendarSidebar.classList.remove('show');
        appOverlay.classList.remove('show');
      });
    }

    // Toolbar actions
    btnToday?.addEventListener('click', () => {
      calendar.today();
      refetch();
    });
    btnReset?.addEventListener('click', () => {
      if (selSalesperson) selSalesperson.value = '';
      if (inputSearch) inputSearch.value = '';
      if (selectAll) selectAll.checked = true;
      filterInputs.forEach(c => c.checked = true);
      calendar.today();
      refetch();
    });
    selSalesperson?.addEventListener('change', function() {
      const userId = $('meta[name="user-id"]').attr('content');
      const reminderFilter = $('.reminder-filter');
      if (this.value == userId) {
        reminderFilter.show();
      } else {
        reminderFilter.hide();
      }
      refetch();
    });
    inputSearch?.addEventListener('input', refetch);

    let reminderSubmitting = false;
const reminderForm = document.getElementById('reminderForm');
if (reminderForm) {
  $(reminderForm).off('submit').on('submit', function (e) {
    e.preventDefault();
    if (reminderSubmitting) return;
    reminderSubmitting = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    const form = this;
    if (!form.checkValidity()) {
      form.reportValidity();
      reminderSubmitting = false;
      submitBtn.disabled = false;
      return;
    }
    const formData = new FormData(this);
    console.log('Reminder FormData:');
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }
    const isUpdate = this.querySelector('button[type="submit"]').classList.contains('btn-update-event');
    const url = isUpdate ? '/calendar/reminders/' + formData.get('id') : '/calendar/reminders';
    let method = isUpdate ? 'POST' : 'POST';
    if (isUpdate) {
      formData.append('_method', 'PUT');
    }

    $.ajax({
      url: url,
      type: method,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        if (data.success) {
          calendar.refetchEvents();
          bsReminderSidebar.hide();
          reminderForm.reset();
          submitBtn.classList.remove('btn-update-event');
          submitBtn.innerHTML = 'Add';
          document.querySelector('#addReminderSidebar .offcanvas-title').innerHTML = 'Add Reminder';
          $(reminderForm.querySelector('[name="lead_id"]')).val(null).trigger('change');
          Swal.fire('Success!', 'Reminder added/updated successfully.', 'success');
        } else {
          bsReminderSidebar.hide();
          Swal.fire('Error!', data.message || 'Failed to add/update reminder.', 'error');
        }
      },
      error: function (xhr) {
        bsReminderSidebar.hide();
        let errorMsg = 'Failed to add/update reminder.';
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          errorMsg = Object.values(errors).flat().join('\n');
        } else if (xhr.status === 403) {
          errorMsg = xhr.responseJSON.error || 'Unauthorized access.';
        }
        Swal.fire('Error!', errorMsg, 'error');
      },
      complete: function () {
        submitBtn.disabled = false;
        reminderSubmitting = false;
      }
    });
  });
}

    let meetingSubmitting = false;
const meetingForm = document.getElementById('meetingForm');
if (meetingForm) {
  $(meetingForm).off('submit').on('submit', function (e) {
    e.preventDefault();
    if (meetingSubmitting) return;
    meetingSubmitting = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    const formData = new FormData(this);
    console.log('Meeting FormData:');
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }
    const isUpdate = this.querySelector('button[type="submit"]').classList.contains('btn-update-event');
    const url = isUpdate ? '/calendar/meetings/' + formData.get('id') : '/calendar/meetings';
    let method = isUpdate ? 'POST' : 'POST';
    if (isUpdate) {
      formData.append('_method', 'PUT');
    }

    const leadId = formData.get('lead_id');
    const title = formData.get('title');
    const startTime = formData.get('start_time');
    const duration = formData.get('duration');
    const type = formData.get('type');
    const urlField = formData.get('url');
    const location = formData.get('location');

    if (!leadId || !title || !startTime || !duration || !type) {
      Swal.fire('Warning!', 'Please fill in all required fields: Lead, Title, Start Time, Duration, and Type.', 'warning');
      meetingSubmitting = false;
      submitBtn.disabled = false;
      return;
    }
    // if (type === 'online' && (!urlField || urlField.trim() === '')) {
    //   Swal.fire('Warning!', 'Please provide a valid URL for online meetings.', 'warning');
    //   meetingSubmitting = false;
    //   submitBtn.disabled = false;
    //   return;
    // }
    // if (type === 'offline' && (!location || location.trim() === '')) {
    //   Swal.fire('Warning!', 'Please provide a location for offline meetings.', 'warning');
    //   meetingSubmitting = false;
    //   submitBtn.disabled = false;
    //   return;
    // }

    $.ajax({
      url: url,
      type: method,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        if (data.success) {
          calendar.refetchEvents();
          bsMeetingSidebar.hide();
          meetingForm.reset();
          submitBtn.classList.remove('btn-update-event');
          submitBtn.innerHTML = 'Add';
         const meetingTitleEl = document.querySelector('#addMeetingSidebar .offcanvas-title');
if (meetingTitleEl) meetingTitleEl.innerHTML = 'Add Meeting';
          $(meetingForm.querySelector('[name="lead_id"]')).val(null).trigger('change');
          document.getElementById('onlineUrl').style.display = 'none';
          document.getElementById('offlineLocation').style.display = 'none';
          Swal.fire('Success!', 'Meeting added/updated successfully.', 'success');
        } else {
          bsMeetingSidebar.hide();
          Swal.fire('Error!', data.message || 'Failed to add/update meeting.', 'error');
        }
      },
      error: function (xhr) {
        bsMeetingSidebar.hide();
        console.error('Error adding/updating meeting:', xhr.status, xhr.responseText);
        let errorMsg = 'Failed to add/update meeting.';
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          errorMsg = Object.values(errors).flat().join('\n');
        } else if (xhr.status === 403) {
          errorMsg = xhr.responseJSON.error || 'You can only manage meetings for leads assigned to you.';
        }
        Swal.fire('Error!', errorMsg, 'error');
      },
      complete: function () {
        submitBtn.disabled = false;
        meetingSubmitting = false;
      }
    });
  });
}

    document.querySelectorAll('input[name="type"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        const onlineUrl = document.getElementById('onlineUrl');
        const offlineLocation = document.getElementById('offlineLocation');
        if (onlineUrl && offlineLocation) {
          onlineUrl.style.display = this.value === 'online' ? 'block' : 'none';
          offlineLocation.style.display = this.value === 'offline' ? 'block' : 'none';
        }
      });
    });

    if (selectAll) {
      selectAll.addEventListener('click', e => {
        const checked = e.currentTarget.checked;
        filterInputs.forEach(c => c.checked = checked);
        refetch();
      });
    }

    if (filterInputs.length > 0) {
      filterInputs.forEach(item => {
        item.addEventListener('click', () => {
          const checkedCount = Array.from(filterInputs).filter(f => f.checked).length;
          const totalCount = filterInputs.length;
          selectAll.checked = checkedCount === totalCount;
          refetch();
        });
      });
    }

    if (inlineCalInstance) {
      inlineCalInstance.config.onChange.push(function (date) {
        if (!date?.length) return;
        calendar.gotoDate(moment(date[0]).format('YYYY-MM-DD'));
        modifyToggler();
        appCalendarSidebar.classList.remove('show');
        appOverlay.classList.remove('show');
        refetch();
      });
    }

    reminderSidebar.addEventListener('hidden.bs.offcanvas', function () {
      reminderForm.reset();
const reminderTitleEl = document.querySelector('#addReminderSidebar .offcanvas-title');
if (reminderTitleEl) reminderTitleEl.innerHTML = 'Add Reminder';
      const submitBtn = reminderForm.querySelector('button[type="submit"]');
      submitBtn.innerHTML = 'Add';
      submitBtn.classList.remove('btn-update-event');
      submitBtn.disabled = false;
      reminderLeadId.val(null).trigger('change');
      reminderForm.querySelector('[name="id"]').value = '';
    });
    
    meetingSidebar.addEventListener('hidden.bs.offcanvas', function () {
      meetingForm.reset();
      document.querySelector('#addMeetingSidebar .offcanvas-title').innerHTML = 'Add Meeting';
      const submitBtn = meetingForm.querySelector('button[type="submit"]');
      submitBtn.innerHTML = 'Add';
      submitBtn.classList.remove('btn-update-event');
      submitBtn.disabled = false;
      meetingLeadId.val(null).trigger('change');
      document.getElementById('onlineUrl').style.display = 'none';
      document.getElementById('offlineLocation').style.display = 'none';
      meetingForm.querySelector('[name="id"]').value = '';
    });

    // Initial reminder filter visibility
    if (selSalesperson) {
      const userId = $('meta[name="user-id"]').attr('content');
      const reminderFilter = $('.reminder-filter');
      if (selSalesperson.value == userId) {
        reminderFilter.show();
      } else {
        reminderFilter.hide();
      }
    }
  })();
});