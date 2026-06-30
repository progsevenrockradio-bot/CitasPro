import React, { useState, useEffect, useCallback } from 'react';
import { StyleSheet, Text, View, FlatList, ActivityIndicator, TouchableOpacity, Linking, Alert } from 'react-native';
import { Calendar, LocaleConfig } from 'react-native-calendars';
import api from '../services/api';

// Configurar idioma español
LocaleConfig.locales['es'] = {
  monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
  monthNamesShort: ['Ene.', 'Feb.', 'Mar', 'Abr', 'May', 'Jun', 'Jul.', 'Ago', 'Sept.', 'Oct.', 'Nov.', 'Dic.'],
  dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
  dayNamesShort: ['Dom.', 'Lun.', 'Mar.', 'Mié.', 'Jue.', 'Vie.', 'Sáb.'],
  today: 'Hoy'
};
LocaleConfig.defaultLocale = 'es';

export default function AgendaScreen() {
  const [citasAgrupadas, setCitasAgrupadas] = useState({});
  const [cargando, setCargando] = useState(true);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);

  const cargarAgenda = useCallback(async () => {
    try {
      // Pedimos 30 días en el backend (desde hoy - 7 días hasta hoy + 30)
      const start_date = new Date(new Date().setDate(new Date().getDate() - 7)).toISOString().split('T')[0];
      const end_date = new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0];
      
      const res = await api.get(`/dashboard/agenda?start_date=${start_date}&end_date=${end_date}`);
      
      if (res.data.success) {
        const ag = res.data.agenda; // array de { fecha, citas: [] }
        const obj = {};
        ag.forEach(dia => {
          obj[dia.fecha] = dia.citas;
        });
        setCitasAgrupadas(obj);
      }
    } catch (err) {
      console.log(err);
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    cargarAgenda();
  }, [cargarAgenda]);

  // Generar las marcas en el calendario para los días que tienen citas
  const getMarkedDates = () => {
    const marked = {};
    Object.keys(citasAgrupadas).forEach(dateStr => {
      marked[dateStr] = { marked: true, dotColor: '#10B981' };
    });
    // Agregar la seleccionada
    if (marked[selectedDate]) {
      marked[selectedDate] = { ...marked[selectedDate], selected: true, selectedColor: '#6366F1' };
    } else {
      marked[selectedDate] = { selected: true, selectedColor: '#6366F1' };
    }
    return marked;
  };

  const citasDelDia = citasAgrupadas[selectedDate] || [];

  const iniciarLlamada = (telefono) => {
    Linking.openURL(`tel:${telefono}`).catch(() => Alert.alert('Error', 'No se puede llamar.'));
  };

  const iniciarWhatsApp = (telefono) => {
    Linking.openURL(`whatsapp://send?phone=${telefono}`).catch(() => Alert.alert('Error', 'WhatsApp no instalado.'));
  };

  if (cargando) {
    return (
      <View style={styles.centro}>
        <ActivityIndicator size="large" color="#6366F1" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Calendar
        current={selectedDate}
        onDayPress={(day) => setSelectedDate(day.dateString)}
        markedDates={getMarkedDates()}
        theme={{
          backgroundColor: '#1F2937',
          calendarBackground: '#1F2937',
          textSectionTitleColor: '#9CA3AF',
          selectedDayBackgroundColor: '#6366F1',
          selectedDayTextColor: '#ffffff',
          todayTextColor: '#10B981',
          dayTextColor: '#F3F4F6',
          textDisabledColor: '#374151',
          monthTextColor: '#F3F4F6',
          arrowColor: '#6366F1',
        }}
      />

      <View style={styles.headerContainer}>
        <Text style={styles.headerTitulo}>Citas para {selectedDate}</Text>
        <Text style={styles.headerSub}>Total: {citasDelDia.length}</Text>
      </View>

      <FlatList
        data={citasDelDia}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <View style={styles.tarjetaCita}>
            <View style={styles.tarjetaHeader}>
              <Text style={styles.horaCita}>{item.hora_inicio} hs</Text>
              <Text style={styles.estadoCita}>{item.estado.toUpperCase()}</Text>
            </View>
            <Text style={styles.nombreCliente}>{item.cliente.nombre}</Text>
            <Text style={styles.servicioTexto}>{item.servicio.nombre}</Text>

            <View style={styles.accionesContainer}>
              <TouchableOpacity style={styles.btnLlamar} onPress={() => iniciarLlamada(item.cliente.tel)}>
                <Text style={styles.btnTextoOscuro}>Llamar</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.btnWs} onPress={() => iniciarWhatsApp(item.cliente.tel)}>
                <Text style={styles.btnTextoBlanco}>WhatsApp</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.textoVacio}>No hay citas para este día.</Text>}
        contentContainerStyle={{ padding: 20 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  centro: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#111827' },
  container: { flex: 1, backgroundColor: '#111827' },
  headerContainer: { padding: 20, borderBottomWidth: 1, borderBottomColor: '#374151' },
  headerTitulo: { color: '#F3F4F6', fontSize: 18, fontWeight: 'bold' },
  headerSub: { color: '#9CA3AF', marginTop: 5 },
  tarjetaCita: { backgroundColor: '#1F2937', padding: 15, borderRadius: 10, marginBottom: 15 },
  tarjetaHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  horaCita: { color: '#F3F4F6', fontSize: 16, fontWeight: 'bold' },
  estadoCita: { color: '#6366F1', fontSize: 10, fontWeight: 'bold' },
  nombreCliente: { color: '#F3F4F6', fontSize: 18, fontWeight: '600' },
  servicioTexto: { color: '#9CA3AF', marginTop: 4, marginBottom: 15 },
  accionesContainer: { flexDirection: 'row', justifyContent: 'space-between' },
  btnLlamar: { backgroundColor: '#F3F4F6', flex: 1, padding: 10, borderRadius: 5, marginRight: 5, alignItems: 'center' },
  btnWs: { backgroundColor: '#10B981', flex: 1, padding: 10, borderRadius: 5, marginLeft: 5, alignItems: 'center' },
  btnTextoOscuro: { color: '#1F2937', fontWeight: 'bold' },
  btnTextoBlanco: { color: '#ffffff', fontWeight: 'bold' },
  textoVacio: { color: '#9CA3AF', textAlign: 'center', marginTop: 30 }
});
