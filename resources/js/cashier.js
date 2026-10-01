let currentAppointmentId = null;
let currentAppointmentDate = null;
let currentAppointmentTime = null;

function openRejectModal(appointmentId) {
    currentAppointmentId = appointmentId;
    const modal = document.getElementById("rejectModal");
    const rejectForm = document.getElementById("rejectForm");
    rejectForm.action = "{{ route('appointments.reject', ':appointmentId') }}".replace(':appointmentId', appointmentId);
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('new_date').min = today;
    
    // Display current appointment details
    const appointmentRow = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
    if (appointmentRow) {
        const formattedDate = appointmentRow.dataset.date;
        const formattedTime = appointmentRow.dataset.time;
        
        document.getElementById('currentRejectDateDisplay').textContent = 
            new Date(formattedDate).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        document.getElementById('currentRejectTimeDisplay').textContent = 
            formatTimeForDisplay(formattedTime + ':00');
    }
    
    modal.style.display = "flex";
}

function closeRejectModal() {
    document.getElementById("rejectModal").style.display = "none";
    document.getElementById("reason").value = "";
    document.getElementById("new_date").value = "";
    document.getElementById("new_time").value = "";
    currentAppointmentId = null;
}

function openRescheduleModal(appointmentId) {
    currentAppointmentId = appointmentId;
    const modal = document.getElementById("rescheduleModal");
    const rescheduleForm = document.getElementById("rescheduleForm");
    rescheduleForm.action = "{{ route('appointments.reschedule', ':appointmentId') }}".replace(':appointmentId', appointmentId);
    const appointmentRow = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
    if (appointmentRow) {
        const formattedDate = appointmentRow.dataset.date;
        const formattedTime = appointmentRow.dataset.time;
        // Display current appointment details
        document.getElementById('currentDateDisplay').textContent = 
            new Date(formattedDate).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        document.getElementById('currentTimeDisplay').textContent = 
            formatTimeForDisplay(formattedTime + ':00');
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.getElementById('reschedule_date');
        dateInput.min = today;
        dateInput.value = formattedDate;
        generateTimeSlots(formattedTime);
    }
    modal.style.display = "flex";
}

function convertTimeTo24Hour(time12h) {
    const [time, modifier] = time12h.split(' ');
    let [hours, minutes] = time.split(':');
    if (modifier === 'PM' && hours !== '12') {
        hours = parseInt(hours, 10) + 12;
    }
    if (modifier === 'AM' && hours === '12') {
        hours = '00';
    }
    return `${hours.padStart(2, '0')}:${minutes}`;
}

function closeRescheduleModal() {
    document.getElementById("rescheduleModal").style.display = "none";
    document.getElementById("reschedule_date").value = "";
    document.getElementById("timeSlot").value = "";
    document.getElementById("reschedule_reason").value = "";
    currentAppointmentId = null;
}

window.onclick = function(event) {
    const rejectModal = document.getElementById("rejectModal");
    const rescheduleModal = document.getElementById("rescheduleModal");
    if (event.target == rejectModal) {
        closeRejectModal();
    }
    if (event.target == rescheduleModal) {
        closeRescheduleModal();
    }
}

function generateTimeSlots(selectedTime = null) {
    const timeSlotSelect = document.getElementById('timeSlot');
    timeSlotSelect.innerHTML = '<option value="">Select a time slot</option>';
    const startHour = 9;
    const endHour = 21;
    const interval = 30;
    for (let hour = startHour; hour < endHour; hour++) {
        for (let minute = 0; minute < 60; minute += interval) {
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:00`;
            const displayTime = formatTimeForDisplay(timeString);
            const option = document.createElement('option');
            option.value = timeString;
            option.textContent = displayTime;
            if (selectedTime && timeString === selectedTime + ':00') {
                option.selected = true;
                option.textContent += ' (Current)';
            }
            timeSlotSelect.appendChild(option);
        }
    }
}

function formatTimeForDisplay(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const period = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    const minutesStr = String(minutes).padStart(2, '0');
    return `${displayHour}:${minutesStr} ${period}`;
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const url = form.action;
    const formData = new FormData(form);
    
    // For rejection, we don't need date/time
    if (formData.get('action') === 'rejected') {
        formData.delete('new_date');
        formData.delete('new_time');
    } else {
        // For reschedule, validate date and time
        if (!formData.get('new_date') || !formData.get('new_time')) {
            alert('Please select both date and time for rescheduling');
            return;
        }
    }
    
    const loadingElement = formData.get('action') === 'rejected' 
        ? document.getElementById('rejectLoading') 
        : document.getElementById('rescheduleLoading');
        
    const buttonElement = formData.get('action') === 'rejected' 
        ? document.getElementById('rejectBtn') 
        : document.getElementById('rescheduleBtn');
        
    loadingElement.style.display = 'inline-block';
    buttonElement.disabled = true;
    
    axios.post(url, formData)
        .then(response => {
            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            loadingElement.style.display = 'none';
            buttonElement.disabled = false;
        });
});

document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const url = form.action;
    const formData = new FormData();
    formData.append('_token', form.querySelector('[name="_token"]').value);
    formData.append('_method', 'PUT');
    formData.append('appointment_date', document.getElementById('reschedule_date').value);
    const timeSelect = document.getElementById('timeSlot');
    const timeValue = timeSelect.value;
    const [hours, minutes] = timeValue.split(':');
    formData.append('appointment_time', `${hours}:${minutes}`);
    formData.append('reason', document.getElementById('reschedule_reason').value);
    document.getElementById('rescheduleLoading').style.display = 'inline-block';
    document.getElementById('rescheduleBtn').disabled = true;
    axios.post(url, formData)
        .then(response => {
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + (error.response?.data?.message || 'Failed to reschedule. Please check the time format.'));
        })
        .finally(() => {
            document.getElementById('rescheduleLoading').style.display = 'none';
            document.getElementById('rescheduleBtn').disabled = false;
        });
});

document.getElementById('tableViewBtn').addEventListener('click', () => {
    document.getElementById('tableView').style.display = 'block';
    document.getElementById('calendarView').style.display = 'none';
    document.getElementById('tableViewBtn').classList.add('active');
    document.getElementById('calendarViewBtn').classList.remove('active');
});

document.getElementById('calendarViewBtn').addEventListener('click', () => {
    document.getElementById('tableView').style.display = 'none';
    document.getElementById('calendarView').style.display = 'block';
    document.getElementById('calendarViewBtn').classList.add('active');
    document.getElementById('tableViewBtn').classList.remove('active');
    initCalendar();
});

function formatCalendarDateTime(dateString, timeString) {
    let [year, month, day] = dateString.includes('/')
        ? dateString.split('/') : dateString.split('-');
    const [hours, minutes] = timeString.split(':');
    return `${year}-${month}-${day}T${hours}:${minutes}:00`;
}

function formatTimeForCalendar(dateTime) {
    const dateObj = new Date(dateTime);
    const hours = dateObj.getHours();
    const period = hours >= 12 ? 'PM' : 'AM';
    const formattedHour = hours % 12 || 12;
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');
    return `${formattedHour}:${minutes} ${period}`;
}

function formatStaffNames(staffArray) {
    return staffArray && staffArray.length > 0 ? staffArray.join(', ') : 'No staff assigned';
}

function acceptAppointment(appointmentId) {
    if (confirm('Are you sure you want to accept this appointment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('appointments.updateStatus', ':appointmentId') }}".replace(':appointmentId', appointmentId);
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = "{{ csrf_token() }}";
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'Accepted';
        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('tableView').style.display = 'block';
    document.getElementById('calendarView').style.display = 'none';
});

function toggleMoreOptions(appointmentId) {
    const optionsContainer = document.querySelector(`.more-options-container[data-appointment-id="${appointmentId}"]`);
    const optionsMenu = optionsContainer.querySelector('.more-options-menu');
    
    // Close all other open menus first
    document.querySelectorAll('.more-options-menu').forEach(menu => {
        if (menu !== optionsMenu) {
            menu.style.display = 'none';
        }
    });
    
    // Toggle the clicked menu
    if (optionsMenu.style.display === 'block') {
        optionsMenu.style.display = 'none';
    } else {
        optionsMenu.style.display = 'block';
    }
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.more-options-container')) {
        document.querySelectorAll('.more-options-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});