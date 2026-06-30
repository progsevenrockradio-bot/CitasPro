let currentStep = 1;
let bookingData = {
    profesional_id: null,
    servicio_id: null,
    servicio_nombre: '',
    precio: 0,
    duracion: 0,
    fecha: '',
    hora: '',
    cliente: {
        nombre: '',
        apellido: '',
        telefono: ''
    }
};

document.addEventListener('DOMContentLoaded', () => {
    bookingData.profesional_id = window.AppData.profesional_id;
});

function openBookingModal() {
    document.getElementById('booking-modal').classList.add('show');
    showStep(1);
}

function closeBookingModal() {
    document.getElementById('booking-modal').classList.remove('show');
    // Si estaba en success, resetear para la proxima
    if(currentStep === 5) {
        resetBooking();
    }
}

function selectService(id, nombre, precio, duracion) {
    bookingData.servicio_id = id;
    bookingData.servicio_nombre = nombre;
    bookingData.precio = precio;
    bookingData.duracion = duracion;
    
    // UI update
    document.querySelectorAll('.service-card').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    
    // Enable Next button and auto-advance
    setTimeout(() => {
        nextStep();
    }, 300);
}

function fetchAvailability(date) {
    bookingData.fecha = date;
    const container = document.getElementById('slots-container');
    container.innerHTML = '<div class="spinner" style="margin: 20px auto;"></div>';
    
    // Llamada real a la API de disponibilidad
    fetch(`/api/paciente/profesional/${bookingData.profesional_id}/disponibilidad?fecha=${date}`)
        .then(res => res.json())
        .then(data => {
            if(!data.success || data.disponibles.length === 0) {
                container.innerHTML = '<div class="empty-state">No hay horarios disponibles para esta fecha.</div>';
                return;
            }
            
            let html = '<div class="slots-grid">';
            data.disponibles.forEach(hora => {
                html += `<button class="slot-btn" onclick="selectSlot('${hora}', this)">${hora}</button>`;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div class="empty-state">Error al cargar horarios. Intenta de nuevo.</div>';
        });
}

function selectSlot(hora, btnElement) {
    bookingData.hora = hora;
    
    document.querySelectorAll('.slot-btn').forEach(el => el.classList.remove('selected'));
    btnElement.classList.add('selected');
    
    setTimeout(() => {
        nextStep();
    }, 300);
}

function nextStep() {
    // Validations before moving next
    if (currentStep === 1 && !bookingData.servicio_id) return alert('Selecciona un servicio.');
    if (currentStep === 2 && !bookingData.hora) return alert('Selecciona una hora disponible.');
    if (currentStep === 3) {
        bookingData.cliente.nombre = document.getElementById('client-name').value.trim();
        bookingData.cliente.apellido = document.getElementById('client-lastname').value.trim();
        bookingData.cliente.telefono = document.getElementById('client-phone').value.trim();
        
        if(!bookingData.cliente.nombre || !bookingData.cliente.apellido || !bookingData.cliente.telefono) {
            return alert('Completa todos tus datos.');
        }
        
        // Update summary
        document.getElementById('summary-service').innerText = bookingData.servicio_nombre;
        document.getElementById('summary-date').innerText = bookingData.fecha;
        document.getElementById('summary-time').innerText = bookingData.hora;
        document.getElementById('summary-price').innerText = `$${bookingData.precio.toFixed(2)}`;
    }
    
    if (currentStep === 4) {
        submitBooking();
        return;
    }

    currentStep++;
    showStep(currentStep);
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(step) {
    // Hide all
    document.querySelectorAll('.booking-step').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step-${step}`).classList.remove('hidden');
    
    // Header logic
    const titleEl = document.getElementById('modal-title');
    const backBtn = document.getElementById('btn-modal-back');
    const footer = document.getElementById('modal-footer');
    
    backBtn.style.display = step > 1 && step < 5 ? 'flex' : 'none';
    footer.style.display = step > 2 && step < 5 ? 'block' : 'none'; // Only show footer button on form and summary
    
    if(step === 1) {
        titleEl.innerText = 'Selecciona un Servicio';
    } else if(step === 2) {
        titleEl.innerText = 'Fecha y Hora';
    } else if(step === 3) {
        titleEl.innerText = 'Tus Datos';
        document.getElementById('btn-next').innerText = 'Revisar Reserva';
    } else if(step === 4) {
        titleEl.innerText = 'Confirmación';
        document.getElementById('btn-next').innerText = 'Confirmar Cita';
    } else if(step === 5) {
        titleEl.innerText = '¡Listo!';
        backBtn.style.display = 'none';
        footer.style.display = 'none';
    }
}

function submitBooking() {
    document.getElementById('modal-loader').classList.remove('hidden');
    
    fetch('/api/paciente/reservar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            profesional_id: bookingData.profesional_id,
            servicio_id: bookingData.servicio_id,
            fecha: bookingData.fecha,
            hora: bookingData.hora,
            cliente: bookingData.cliente
        })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('modal-loader').classList.add('hidden');
        if(data.success) {
            currentStep = 5;
            showStep(5); // Show success screen
        } else {
            alert(data.message || 'Error al procesar la reserva');
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('modal-loader').classList.add('hidden');
        alert('Error de conexión.');
    });
}

function resetBooking() {
    currentStep = 1;
    bookingData.servicio_id = null;
    bookingData.hora = '';
    
    document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
    document.getElementById('booking-date').value = '';
    document.getElementById('slots-container').innerHTML = '<div class="empty-state">Selecciona una fecha para ver los horarios disponibles.</div>';
    
    document.getElementById('client-name').value = '';
    document.getElementById('client-lastname').value = '';
    document.getElementById('client-phone').value = '';
}
