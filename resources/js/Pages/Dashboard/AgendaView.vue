<template>
  <div class="agenda-view">
    <!-- Controles y Filtros (Restricción 1: Uso de CustomSelect) -->
    <div class="filters-container">
      <CustomSelect
        v-model="filters.professional"
        :options="professionals"
        placeholder="Filtrar por Profesional"
      />
      <CustomSelect
        v-model="filters.status"
        :options="statuses"
        placeholder="Filtrar por Estado"
      />
    </div>

    <!-- Grid del Calendario -->
    <div class="calendar-grid">
      <div v-for="day in days" :key="day.date" class="calendar-day">
        <div class="day-header">{{ day.name }} <br> <small>{{ day.date }}</small></div>
        
        <!-- Zonas para soltar (Drop Zones) -->
        <div
          v-for="hour in hours"
          :key="hour"
          class="time-slot"
          @dragover.prevent
          @dragenter.prevent
          @drop="handleDrop($event, day.date, hour)"
        >
          <span class="hour-label">{{ hour }}</span>
          
          <!-- Elementos arrastrables (Draggable Items) -->
          <div
            v-for="appointment in getAppointmentsForSlot(day.date, hour)"
            :key="appointment.id"
            class="appointment-card"
            :class="appointment.status"
            draggable="true"
            @dragstart="handleDragStart($event, appointment)"
          >
            <strong>{{ appointment.clientName }}</strong>
            <p>{{ appointment.service }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Personalizado (Restricción 2: Uso de ConfirmModal) -->
    <ConfirmModal
      :show="isModalOpen"
      :title="'Confirmar cambio de cita'"
      :message="modalMessage"
      @confirm="executeMove"
      @cancel="cancelMove"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import CustomSelect from '../Components/CustomSelect.vue';
import ConfirmModal from '../Components/ConfirmModal.vue';

// --- ESTADOS DE FILTROS ---
const filters = reactive({
  professional: null,
  status: null
});

// Mock de opciones para los CustomSelect (esto vendría de tu API)
const professionals = ref([
  { value: 1, label: 'Dr. Alejandro (Dental)' },
  { value: 2, label: 'Dra. María (Estética)' }
]);

const statuses = ref([
  { value: 'pending', label: 'Pendiente' },
  { value: 'confirmed', label: 'Confirmada' }
]);

// --- ESTRUCTURA DEL CALENDARIO ---
const days = ref([
  { date: '2023-11-20', name: 'Lunes' },
  { date: '2023-11-21', name: 'Martes' },
  { date: '2023-11-22', name: 'Miércoles' },
]);
const hours = ref(['09:00', '10:00', '11:00', '12:00', '13:00', '14:00']);

// --- ESTADOS DE CITAS (Mock inicial) ---
const appointments = ref([
  { id: 101, clientName: 'Juan Pérez', service: 'Limpieza Dental', date: '2023-11-20', time: '09:00', professionalId: 1, status: 'confirmed' },
  { id: 102, clientName: 'Laura Gómez', service: 'Botox', date: '2023-11-21', time: '11:00', professionalId: 2, status: 'pending' }
]);

// Función para renderizar citas filtradas
const getAppointmentsForSlot = (date, time) => {
  return appointments.value.filter(app => {
    const matchTime = app.date === date && app.time === time;
    const matchProf = filters.professional ? app.professionalId === filters.professional.value : true;
    const matchStatus = filters.status ? app.status === filters.status.value : true;
    
    return matchTime && matchProf && matchStatus;
  });
};

// --- LÓGICA DE DRAG AND DROP ---
const draggedAppointment = ref(null);
const targetSlot = ref(null);

const handleDragStart = (event, appointment) => {
  draggedAppointment.value = appointment;
  event.dataTransfer.effectAllowed = 'move';
  // Necesario para Firefox y estándares HTML5
  event.dataTransfer.setData('text/plain', appointment.id);
  
  // Opcional: efecto visual al arrastrar
  setTimeout(() => event.target.style.opacity = '0.5', 0);
};

const handleDrop = (event, newDate, newTime) => {
  if (!draggedAppointment.value) return;

  // Evitar soltar en el mismo bloque exacto
  if (draggedAppointment.value.date === newDate && draggedAppointment.value.time === newTime) {
    resetDragState();
    return;
  }

  // Guardar intención y abrir el modal
  targetSlot.value = { date: newDate, time: newTime };
  modalMessage.value = `Vas a mover la cita de ${draggedAppointment.value.clientName} para el ${newDate} a las ${newTime}. ¿Deseas continuar?`;
  isModalOpen.value = true;
};

// --- LÓGICA DEL MODAL DE CONFIRMACIÓN ---
const isModalOpen = ref(false);
const modalMessage = ref('');

const executeMove = () => {
  if (draggedAppointment.value && targetSlot.value) {
    // 1. Aquí harías la petición a tu API Backend en Laravel
    // axios.put(`/api/appointments/${draggedAppointment.value.id}/move`, targetSlot.value)...
    
    // 2. Actualización optimista en el Frontend
    const index = appointments.value.findIndex(a => a.id === draggedAppointment.value.id);
    if (index !== -1) {
      appointments.value[index].date = targetSlot.value.date;
      appointments.value[index].time = targetSlot.value.time;
    }
  }
  resetDragState();
};

const cancelMove = () => {
  resetDragState();
};

const resetDragState = () => {
  isModalOpen.value = false;
  draggedAppointment.value = null;
  targetSlot.value = null;
  modalMessage.value = '';
  
  // Restaurar opacidades de los elementos arrastrados
  document.querySelectorAll('.appointment-card').forEach(el => el.style.opacity = '1');
};
</script>

<style scoped>
.agenda-view {
  font-family: system-ui, -apple-system, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
}

.filters-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.calendar-day {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.day-header {
  background: #f1f5f9;
  padding: 1rem;
  text-align: center;
  font-weight: 600;
  border-bottom: 1px solid #e2e8f0;
  color: #334155;
}

.time-slot {
  min-height: 100px;
  padding: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
  position: relative;
  transition: background-color 0.2s;
}

.time-slot:last-child {
  border-bottom: none;
}

/* Efecto hover nativo cuando se arrastra sobre un slot */
.time-slot:dragover,
.time-slot:hover {
  background-color: #f1f5f9;
}

.hour-label {
  position: absolute;
  top: 5px;
  right: 10px;
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}

.appointment-card {
  margin-top: 1.5rem;
  padding: 0.75rem;
  border-radius: 6px;
  cursor: grab;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  transition: transform 0.1s;
}

.appointment-card:active {
  cursor: grabbing;
  transform: scale(0.98);
}

/* Colores según estado */
.appointment-card.confirmed {
  background-color: #dcfce7;
  border-left: 4px solid #22c55e;
  color: #166534;
}

.appointment-card.pending {
  background-color: #fef9c3;
  border-left: 4px solid #eab308;
  color: #854d0e;
}
</style>
