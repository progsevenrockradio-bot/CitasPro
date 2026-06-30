import React, { useState, useEffect, useContext, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  StatusBar,
  Linking,
  Alert,
  RefreshControl
} from 'react-native';
import { AuthContext } from '../context/AuthContext';
import api from '../services/api';
import { Ionicons } from '@expo/vector-icons';

export default function DashboardScreen() {
  const { logout, user, role } = useContext(AuthContext);
  const [resumen, setResumen] = useState(null);
  const [citasHoy, setCitasHoy] = useState([]);
  const [cargando, setCargando] = useState(true);
  const [refrescando, setRefrescando] = useState(false);
  const [error, setError] = useState(null);

  const cargarDatos = useCallback(async () => {
    try {
      setError(null);
      const [resumenRes, agendaRes] = await Promise.all([
        api.get('/dashboard/resumen-rapido'),
        api.get('/dashboard/agenda')
      ]);

      if (resumenRes.data.success && agendaRes.data.success) {
        setResumen(resumenRes.data.kpis);
        const hoyFechaStr = agendaRes.data.hoy;
        const agendaHoy = agendaRes.data.agenda.find(dia => dia.fecha === hoyFechaStr);
        setCitasHoy(agendaHoy ? agendaHoy.citas : []);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Error al conectar con el servidor.');
    } finally {
      setCargando(false);
      setRefrescando(false);
    }
  }, []);

  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

  const alRefrescar = () => {
    setRefrescando(true);
    cargarDatos();
  };

  const iniciarLlamada = (telefono) => {
    const url = `tel:${telefono}`;
    Linking.openURL(url).catch(() => Alert.alert('Error', 'No se puede llamar desde este dispositivo.'));
  };

  const contactarWhatsApp = (telefono, nombre) => {
    const numeroLimpio = telefono.replace(/[^\d+]/g, '');
    const msg = encodeURIComponent(`Hola ${nombre}, te escribo desde CitasPro para coordinar tu cita.`);
    Linking.openURL(`https://wa.me/${numeroLimpio}?text=${msg}`).catch(() => Alert.alert('Error', 'WhatsApp no instalado.'));
  };

  const cambiarEstadoCita = async (id, nuevoEstado) => {
    try {
      setCargando(true);
      const response = await api.patch(`/dashboard/citas/${id}/estado`, { estado: nuevoEstado });
      if (response.data.success) {
        Alert.alert('Éxito', `Cita marcada como ${nuevoEstado}`);
        cargarDatos();
      }
    } catch (err) {
      Alert.alert('Error', err.response?.data?.message || 'Error al actualizar la cita');
      setCargando(false);
    }
  };

  const renderCita = ({ item }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.hora}>{item.hora_inicio} hs</Text>
        <Text style={[styles.estado, item.estado === 'completada' ? styles.estadoCompletada : item.estado === 'cancelada' ? styles.estadoCancelada : {}]}>{item.estado.toUpperCase()}</Text>
      </View>
      <Text style={styles.cliente}>{item.cliente.nombre}</Text>
      <Text style={styles.servicio}>{item.servicio.nombre}</Text>
      {item.notas && <Text style={styles.notas}>💬 {item.notas}</Text>}
      
      <View style={styles.acciones}>
        <TouchableOpacity style={[styles.btn, styles.btnLlamar]} onPress={() => iniciarLlamada(item.cliente.tel)}>
          <Text style={styles.btnText}>📞 Llamar</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.btn, styles.btnWpp]} onPress={() => contactarWhatsApp(item.cliente.tel, item.cliente.nombre)}>
          <Text style={[styles.btnText, { color: '#FFF' }]}>💬 WhatsApp</Text>
        </TouchableOpacity>
      </View>

      {(item.estado === 'pendiente' || item.estado === 'confirmada') && (
        <View style={styles.accionesSecundarias}>
          <TouchableOpacity style={[styles.btnAction, styles.btnCompletar]} onPress={() => cambiarEstadoCita(item.id, 'completada')}>
            <Ionicons name="checkmark-circle-outline" size={16} color="#FFF" />
            <Text style={styles.btnActionText}>Completar</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.btnAction, styles.btnCancelar]} onPress={() => cambiarEstadoCita(item.id, 'cancelada')}>
            <Ionicons name="close-circle-outline" size={16} color="#EF4444" />
            <Text style={[styles.btnActionText, { color: '#EF4444' }]}>Cancelar</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#1E1B4B" />
      <View style={styles.header}>
        <View style={styles.headerRow}>
          <View>
            <Text style={styles.saludo}>Hola, {user?.nombre || 'Profesional'}</Text>
            <Text style={styles.sub}>Panel de Control</Text>
          </View>
          <TouchableOpacity style={styles.btnLogout} onPress={logout}>
            <Text style={styles.logoutText}>Salir</Text>
          </TouchableOpacity>
        </View>
        {resumen && (
          <View style={styles.kpis}>
            <View style={styles.kpiCard}><Text style={styles.kpiVal}>{resumen.total_citas}</Text><Text style={styles.kpiLbl}>Citas Mes</Text></View>
            <View style={styles.kpiCard}><Text style={styles.kpiVal}>€{resumen.ingresos_mes}</Text><Text style={styles.kpiLbl}>Ingresos</Text></View>
            <View style={styles.kpiCard}><Text style={styles.kpiVal}>{resumen.tasa_exito_pct}%</Text><Text style={styles.kpiLbl}>Éxito</Text></View>
          </View>
        )}
      </View>

      {role === 'profesional' && !user?.telegram_chat_id && (
        <TouchableOpacity 
          style={styles.telegramBanner} 
          onPress={() => {
            const botUser = user?.telegram_bot_username || 'CitasProJM_bot';
            const phone = user?.telefono || '';
            const link = `https://t.me/${botUser}?start=${encodeURIComponent(phone)}`;
            Linking.openURL(link).catch(() => Alert.alert('Error', 'No se pudo abrir Telegram. Asegúrate de tenerlo instalado.'));
          }}
        >
          <Ionicons name="logo-telegram" size={20} color="#FFF" style={{ marginRight: 8 }} />
          <Text style={styles.telegramBannerText}>
            📢 Vincular con Telegram para recibir alertas en tiempo real
          </Text>
        </TouchableOpacity>
      )}

      <View style={styles.body}>
        <Text style={styles.seccion}>Citas del día de hoy</Text>
        {cargando ? (
          <ActivityIndicator size="large" color="#4F46E5" style={{ flex: 1 }} />
        ) : error ? (
          <Text style={styles.error}>{error}</Text>
        ) : (
          <FlatList
            data={citasHoy}
            keyExtractor={(item) => item.id.toString()}
            renderItem={renderCita}
            refreshControl={<RefreshControl refreshing={refrescando} onRefresh={alRefrescar} />}
            ListEmptyComponent={<Text style={styles.vacio}>🌴 Día libre. Sin citas programadas.</Text>}
          />
        )}

        {/* FLOATING ACTION BUTTON PARA CREAR CITA MANUAL */}
        <TouchableOpacity 
          style={styles.fab} 
          onPress={() => Alert.alert('Próximamente', 'Aquí se abrirá el formulario para que el profesional agende a un paciente manualmente por teléfono.')}
        >
          <Ionicons name="add" size={30} color="#FFF" />
        </TouchableOpacity>

      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  header: { backgroundColor: '#1E1B4B', padding: 20, borderBottomLeftRadius: 24, borderBottomRightRadius: 24 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  saludo: { fontSize: 20, fontWeight: 'bold', color: '#FFF' },
  sub: { fontSize: 12, color: '#C7D2FE' },
  btnLogout: { backgroundColor: 'rgba(239,68,68,0.2)', padding: 6, borderRadius: 8 },
  logoutText: { color: '#FCA5A5', fontWeight: 'bold', fontSize: 12 },
  kpis: { flexDirection: 'row', justifyContent: 'space-between' },
  kpiCard: { flex: 1, backgroundColor: '#FFF', borderRadius: 12, padding: 12, alignItems: 'center', marginHorizontal: 4 },
  kpiVal: { fontSize: 16, fontWeight: 'bold', color: '#111827' },
  kpiLbl: { fontSize: 10, color: '#6B7280', marginTop: 2 },
  body: { flex: 1, padding: 20 },
  seccion: { fontSize: 16, fontWeight: 'bold', marginBottom: 12, color: '#111827' },
  card: { backgroundColor: '#FFF', borderRadius: 12, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: '#E5E7EB' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  hora: { fontWeight: 'bold', color: '#111827' },
  estado: { fontSize: 10, fontWeight: 'bold', color: '#6366F1' },
  cliente: { fontSize: 16, fontWeight: 'bold' },
  servicio: { color: '#6B7280', fontSize: 13, marginTop: 2 },
  notas: { backgroundColor: '#F3F4F6', padding: 6, borderRadius: 6, fontSize: 12, marginTop: 8, fontStyle: 'italic' },
  acciones: { flexDirection: 'row', marginTop: 12, borderTopWidth: 1, borderTopColor: '#F3F4F6', paddingTop: 8 },
  btn: { flex: 1, padding: 8, borderRadius: 8, alignItems: 'center', marginHorizontal: 4 },
  btnLlamar: { backgroundColor: '#F3F4F6' },
  btnWpp: { backgroundColor: '#25D366' },
  btnText: { fontSize: 12, fontWeight: 'bold' },
  error: { color: '#EF4444', textAlign: 'center', marginTop: 20 },
  vacio: { textAlign: 'center', color: '#6B7280', marginTop: 40 },
  fab: {
    position: 'absolute',
    width: 60,
    height: 60,
    alignItems: 'center',
    justifyContent: 'center',
    right: 20,
    bottom: 20,
    backgroundColor: '#6366F1',
    borderRadius: 30,
    elevation: 8,
    shadowColor: '#000',
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 5,
  },
  estadoCompletada: { color: '#10B981' },
  estadoCancelada: { color: '#EF4444' },
  accionesSecundarias: { flexDirection: 'row', marginTop: 8, justifyContent: 'space-between' },
  btnAction: { flex: 1, flexDirection: 'row', padding: 8, borderRadius: 8, alignItems: 'center', justifyContent: 'center', marginHorizontal: 4, borderWidth: 1 },
  btnCompletar: { backgroundColor: '#10B981', borderColor: '#10B981' },
  btnCancelar: { backgroundColor: 'transparent', borderColor: '#EF4444' },
  btnActionText: { marginLeft: 4, fontSize: 12, fontWeight: 'bold', color: '#FFF' },
  telegramBanner: {
    backgroundColor: '#0088cc',
    padding: 12,
    marginHorizontal: 20,
    marginTop: 15,
    borderRadius: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  telegramBannerText: {
    color: '#FFF',
    fontWeight: 'bold',
    fontSize: 13,
    textAlign: 'center',
  }
});
