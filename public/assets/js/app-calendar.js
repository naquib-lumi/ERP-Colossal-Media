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

    const bsReminderSidebar = reminderSidebar ? new bootstrap.Offcanvas(reminderSidebar) : null;
    const bsMeetingSidebar = meetingSidebar ? new bootstrap.Offcanvas(meetingSidebar) : null;

    const reminderLeadId = $('#reminderLeadId');
    const recurrenceType = $('#recurrenceType');
    const meetingLeadId = $('#meetingLeadId');
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
    if (recurrenceType.length) {
      function renderBadges(option) {
        if (!option.id) return option.text;
        return `<span class='badge badge-dot bg-primary me-2'></span>${option.text}`;
      }
      recurrenceType.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Select value',
        dropdownParent: recurrenceType.parent(),
        templateResult: renderBadges,
        templateSelection: renderBadges,
        minimumResultsForSearch: -1,
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

    function fetchEvents(info, successCallback) {
      let calendars = selectedCalendars();
      $.ajax({
        url: '/calendar/events',
        type: 'GET',
        data: {
          start: info.startStr,
          end: info.endStr,
          salesperson_id: $('meta[name="user-id"]').attr('content')
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
          console.log('Fetched events:', data);
          let filteredEvents = data.filter(event => calendars.includes(event.extendedProps.type) || calendars.includes('all'));
          filteredEvents = filteredEvents.map(event => {
            if (event.extendedProps.type === 'reminder') {
              const startDate = new Date(event.start);
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
          alert('Failed to load calendar events.');
        }
      });
    }

    let calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: fetchEvents,
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
      dateClick: function (info) {
        let date = moment(info.date).format('YYYY-MM-DDTHH:mm');
        if (bsReminderSidebar) {
          bsReminderSidebar.show();
          document.getElementById('reminderDueDate').value = date;
        }
      },
      eventClick: function (info) {
        console.log('Event Click Data:', info.event);
        let modalId = 'eventDetailModal_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '');
        if (!$('#' + modalId).length) {
          const isReminder = info.event.extendedProps.type === 'reminder';
          const modalBody = `
  <p><strong>Title:</strong> <span id="eventTitleDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
  <p><strong>Type:</strong> <span id="eventTypeDetail_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}"></span></p>
  <p><strong>Status:</strong> 
    <select id="eventStatusSelect_${info.event.id.replace(/[^a-zA-Z0-9]/g, '')}" class="form-select">
      <option value="scheduled" ${info.event.extendedProps.status === "scheduled" ? "selected" : ""}>Scheduled</option>
      <option value="canceled" ${info.event.extendedProps.status === "canceled" ? "selected" : ""}>Canceled</option>
      <option value="postponed" ${info.event.extendedProps.status === "postponed" ? "selected" : ""}>Postponed</option>
      ${isReminder
              ? `<option value="completed" ${info.event.extendedProps.status === "completed" ? "selected" : ""}>Completed</option>`
              : ""
            }
    </select>
  </p>
`;

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

        $('#eventTitleDetail_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '')).text(info.event.title || 'N/A');
        $('#eventTypeDetail_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '')).text(info.event.extendedProps.type || 'N/A');
        $('#eventStatusDetail_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '')).text(info.event.extendedProps.status || 'N/A');



        const eventModal = new bootstrap.Modal(document.getElementById(modalId));
        eventModal.show();

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
                      alert('You do not have permission to access this lead.');
                    } else {
                      alert('Failed to load lead data.');
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
              form.querySelector('[name="due_date"]').value = moment(info.event.start).format('YYYY-MM-DDTHH:mm') || '';
              const recurrenceTypeVal = info.event.extendedProps.recurrence_type || 'none';
              $('#recurrenceType').val(recurrenceTypeVal).trigger('change');
              form.querySelector('[name="recurrence_time"]').value = info.event.extendedProps.recurrence_time || '';
            }
          }
        });


        $('.btn-delete-event').off('click').on('click', function() {
          if (confirm('Are you sure you want to delete this event?')) {
            const eventId = $(this).data('event-id');
            const type = $(this).data('event-type');
            const id = eventId.replace(`${type}-`, '');
            $.ajax({
              url: `/calendar/${type}s/${id}`,
              type: 'DELETE',
              headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
              success: function(response) {
                if (response.success) {
                  calendar.refetchEvents();
                  eventModal.hide();
                } else {
                  alert('Error deleting event');
                }
              },
              error: function() {
                alert('Failed to delete event');
              }
            });
          }
        });


        $('.btn-confirm-complete').off('click').on('click', function () {
          const eventId = $(this).data('event-id');
          const confirmModalId = 'confirmCompleteModal_' + eventId.replace(/[^a-zA-Z0-9]/g, '');
          new bootstrap.Modal(document.getElementById(confirmModalId)).show();
        });

        $('#confirmCompleteBtn_' + info.event.id.replace(/[^a-zA-Z0-9]/g, '')).off('click').on('click', function () {
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
                alert(response.message || 'Reminder marked as completed.');
              } else {
                alert('Error: ' + response.error);
              }
            },
            error: function (xhr) {
              console.error('Error confirming completion:', xhr.status, xhr.responseText);
              alert('Failed to confirm completion');
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
                calendar.refetchEvents();
                eventModal.hide();
              } else {
                alert('Error updating status');
              }
            },
            error: function (xhr) {
              alert('Failed to update status');
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

    let reminderSubmitting = false;
    const reminderForm = document.getElementById('reminderForm');
    if (reminderForm) {
      $(reminderForm).off('submit').on('submit', function(e) {
        e.preventDefault();
        if (reminderSubmitting) return;
        reminderSubmitting = true;
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        const formData = new FormData(this);
        console.log('Reminder FormData:');
        for (let [key, value] of formData.entries()) {
          console.log(key, value);
        }
        if (formData.get('recurrence_time') === '') {
          formData.delete('recurrence_time');
        }
        const isUpdate = this.querySelector('button[type="submit"]').classList.contains('btn-update-event');
        const url = isUpdate ? '/calendar/reminders/' + formData.get('id') : '/calendar/reminders';
        let method = isUpdate ? 'POST' : 'POST';
        if (isUpdate) {
          formData.append('_method', 'PUT');
        }

        if (!formData.get('lead_id') || !formData.get('title') || !formData.get('due_date')) {
          alert('Please fill in all required fields: Lead, Title, and Due Date.');
          reminderSubmitting = false;
          submitBtn.disabled = false;
          return;
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
          success: function(data) {
            if (data.success) {
              calendar.refetchEvents();
              bsReminderSidebar.hide();
              reminderForm.reset();
              submitBtn.classList.remove('btn-update-event');
              submitBtn.innerHTML = 'Add';
              reminderForm.querySelector('.offcanvas-title').innerHTML = 'Add Reminder';
              $(reminderForm.querySelector('[name="lead_id"]')).val(null).trigger('change');
            } else {
              alert('Error: ' + data.message);
            }
          },
          error: function(xhr) {
            console.error('Error adding/updating reminder:', xhr.status, xhr.responseText);
            if (xhr.status === 422) {
              const errors = xhr.responseJSON.errors;
              let errorMsg = 'Validation errors:\n';
              for (let key in errors) {
                errorMsg += `${key}: ${errors[key].join(', ')}\n`;
              }
              alert(errorMsg);
            } else {
              alert('Failed to add/update reminder');
            }
          },
          complete: function() {
            submitBtn.disabled = false;
            reminderSubmitting = false;
          }
        });
      });
    }

    let meetingSubmitting = false;
    const meetingForm = document.getElementById('meetingForm');
    if (meetingForm) {
      $(meetingForm).off('submit').on('submit', function(e) {
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
          alert('Please fill in all required fields: Lead, Title, Start Time, Duration, and Type.');
          meetingSubmitting = false;
          submitBtn.disabled = false;
          return;
        }
        if (type === 'online' && (!urlField || urlField.trim() === '')) {
          alert('Please provide a valid URL for online meetings.');
          meetingSubmitting = false;
          submitBtn.disabled = false;
          return;
        }
        if (type === 'offline' && (!location || location.trim() === '')) {
          alert('Please provide a location for offline meetings.');
          meetingSubmitting = false;
          submitBtn.disabled = false;
          return;
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
          success: function(data) {
            if (data.success) {
              calendar.refetchEvents();
              bsMeetingSidebar.hide();
              meetingForm.reset();
              submitBtn.classList.remove('btn-update-event');
              submitBtn.innerHTML = 'Add';
              meetingForm.querySelector('.offcanvas-title').innerHTML = 'Add Meeting';
              $(meetingForm.querySelector('[name="lead_id"]')).val(null).trigger('change');
              document.getElementById('onlineUrl').style.display = 'block';
              document.getElementById('offlineLocation').style.display = 'none';
            } else {
              alert('Error: ' + data.message);
            }
          },
          error: function(xhr) {
            console.error('Error adding/updating meeting:', xhr.status, xhr.responseText);
            if (xhr.status === 422) {
              const errors = xhr.responseJSON.errors;
              let errorMsg = 'Validation errors:\n';
              for (let key in errors) {
                errorMsg += `${key}: ${errors[key].join(', ')}\n`;
              }
              alert(errorMsg);
            } else {
              alert('Failed to add/update meeting: ' + xhr.responseText);
            }
          },
          complete: function() {
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
        if (e.currentTarget.checked) {
          filterInputs.forEach(c => {
            if (c) c.checked = true;
          });
        } else {
          filterInputs.forEach(c => {
            if (c) c.checked = false;
          });
        }
        calendar.refetchEvents();
      });
    }

    if (filterInputs.length > 0) {
      filterInputs.forEach(item => {
        item.addEventListener('click', () => {
          const checkedCount = Array.from(filterInputs).filter(f => f.checked).length;
          const totalCount = filterInputs.length;
          selectAll.checked = checkedCount === totalCount;
          calendar.refetchEvents();
        });
      });
    }

    if (inlineCalInstance) {
      inlineCalInstance.config.onChange.push(function (date) {
        calendar.changeView(calendar.view.type, moment(date[0]).format('YYYY-MM-DD'));
        modifyToggler();
        appCalendarSidebar.classList.remove('show');
        appOverlay.classList.remove('show');
      });
    }
  })();
});