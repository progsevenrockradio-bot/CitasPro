import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  StatusBar,
  Alert,
  RefreshControl,
  Image
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';

export default function TestimoniosScreen() {
  const [resenas, setResenas] = useState([]);
  const [stats, setStats] = useState({ promedio: 0, total: 0, ocultas: 0 });
  const [cargando, setCargando] = useState(true);
  const [refrescando, setRefrescando] = useState(false);
  const [pagina, setPagina] = useState(1);
  const [tieneMas, setTieneMas] = useState(false);

  const cargarDatos = useCallback(async (page = 1, shouldAppend = false) => {
    try {
      if (!shouldAppend) setCargando(true);
      const res = await api.get('/dashboard/resenas', { params: { page } });
      if (res.data.success) {
        const listado = res.data.data.data || [];
        setResenas(prev => shouldAppend ? [...prev, ...listado] : listado);
        setStats(res.data.stats || { promedio: 0, total: 0, ocultas: 0 });
        setPagina(res.data.data.current_page);
        setTieneMas(res.data.data.current_page < res.data.data.last_page);
      }
    } catch (err) {
      console.error(err);
      Alert.alert('Error', 'No se pudieron cargar las opiniones.');
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
    cargarDatos(1, false);
  };

  const cargarMas = () => {
    if (tieneMas && !cargando) {
      cargarDatos(pagina + 1, true);
    }
  };

  const toggleEstado = (resena) => {
    const tituloMsg = resena.activo ? 'Ocultar Testimonio' : 'Aprobar Testimonio';
    const descMsg = resena.activo
      ? '¿Estás seguro de que deseas ocultar esta opinión? Ya no se mostrará públicamente en el directorio.'
      : '¿Deseas aprobar esta opinión? Será visible en tu perfil público y afectará a la calificación promedio.';

    Alert.alert(
      tituloMsg,
      descMsg,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: resena.activo ? 'Ocultar' : 'Aprobar',
          style: resena.activo ? 'destructive' : 'default',
          onPress: async () => {
            try {
              setCargando(true);
              const response = await api.patch(`/dashboard/resenas/${resena.id}/toggle-activo`);
              if (response.data.success) {
                Alert.alert('Éxito', response.data.message);
                cargarDatos(1, false); // recargar listado y estadísticas
              }
            } catch (err) {
              Alert.alert('Error', 'No se pudo cambiar el estado del testimonio.');
              setCargando(false);
            }
          }
        }
      ]
    );
  };

  const renderEstrellas = (calificacion, size = 16) => {
    const estrellas = [];
    for (let i = 1; i <= 5; i++) {
      estrellas.push(
        <Ionicons
          key={i}
          name={i <= calificacion ? 'star' : 'star-outline'}
          size={size}
          color="#FBBF24" // Dorado
          style={{ marginRight: 2 }}
        />
      );
    }
    return <View style={styles.starsRow}>{estrellas}</View>;
  };

  const formatFecha = (dateStr) => {
    if (!dateStr) return '';
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
      return dateStr;
    }
  };

  const renderResena = ({ item }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View style={styles.clienteInfo}>
          <View style={styles.fotoPlaceholder}>
            {item.cliente?.foto ? (
              <Image source={{ uri: item.cliente.foto }} style={styles.foto} />
            ) : (
              <Text style={styles.inicial}>{item.cliente?.nombre?.[0]?.toUpperCase() || 'C'}</Text>
            )}
          </View>
          <View>
            <Text style={styles.clienteNombre}>{item.cliente?.nombre} {item.cliente?.apellido}</Text>
            <Text style={styles.fecha}>{formatFecha(item.created_at)}</Text>
          </View>
        </View>
        <View :style={styles.badgeWrapper}>
          <Text style={[styles.badge, item.activo ? styles.badgeVisible : styles.badgeOculto]}>
            {item.activo ? 'Visible' : 'Oculto'}
          </Text>
        </View>
      </View>

      <View style={styles.ratingBox}>
        {renderEstrellas(item.calificacion)}
      </View>

      <Text style={styles.comentario}>
        "{item.comentario || 'Sin comentario escrito.'}"
      </Text>

      <View style={styles.metaRow}>
        <Text style={styles.metaText}>
          Atendido por: <Text style={styles.metaBold}>{item.profesional?.nombre} {item.profesional?.apellido}</Text>
        </Text>
        {item.cita?.servicio && (
          <Text style={styles.metaText}>
            Servicio: <Text style={styles.metaBold}>{item.cita.servicio.nombre}</Text>
          </Text>
        )}
      </View>

      <View style={styles.btnRow}>
        <TouchableOpacity
          style={[styles.actionBtn, item.activo ? styles.btnOcultar : styles.btnAprobar]}
          onPress={() => toggleEstado(item)}
        >
          <Ionicons
            name={item.activo ? 'eye-off-outline' : 'checkmark-circle-outline'}
            size={16}
            color="#FFF"
            style={{ marginRight: 6 }}
          />
          <Text style={styles.actionBtnText}>
            {item.activo ? 'Ocultar' : 'Aprobar'}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#1E1B4B" />

      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerRow}>
          <View>
            <Text style={styles.headerTitle}>Testimonios y Reseñas</Text>
            <Text style={styles.headerSub}>Modera la reputación de tu negocio</Text>
          </View>
        </View>

        {/* KPIs */}
        <View style={styles.kpis}>
          <View style={styles.kpiCard}>
            <Text style={styles.kpiVal}>{stats.promedio || 0} ⭐</Text>
            <Text style={styles.kpiLbl}>Valoración</Text>
          </View>
          <View style={styles.kpiCard}>
            <Text style={styles.kpiVal}>{stats.total || 0}</Text>
            <Text style={styles.kpiLbl}>Opiniones</Text>
          </View>
          <View style={styles.kpiCard}>
            <Text style={[styles.kpiVal, { color: '#EF4444' }]}>{stats.ocultas || 0}</Text>
            <Text style={styles.kpiLbl}>Ocultos</Text>
          </View>
        </View>
      </View>

      {/* List */}
      <View style={styles.body}>
        {cargando && resenas.length === 0 ? (
          <ActivityIndicator size="large" color="#6366F1" style={{ flex: 1 }} />
        ) : (
          <FlatList
            data={resenas}
            keyExtractor={(item) => item.id.toString()}
            renderItem={renderResena}
            refreshControl={<RefreshControl refreshing={refrescando} onRefresh={alRefrescar} />}
            onEndReached={cargarMas}
            onEndReachedThreshold={0.3}
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Ionicons name="chatbubbles-outline" size={48} color="#9CA3AF" />
                <Text style={styles.emptyText}>No has recibido reseñas todavía.</Text>
              </View>
            }
          />
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  header: { backgroundColor: '#1E1B4B', padding: 20, borderBottomLeftRadius: 24, borderBottomRightRadius: 24 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFF' },
  headerSub: { fontSize: 12, color: '#C7D2FE', marginTop: 2 },
  kpis: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 5 },
  kpiCard: { flex: 1, backgroundColor: '#FFF', borderRadius: 12, padding: 12, alignItems: 'center', marginHorizontal: 4, elevation: 1 },
  kpiVal: { fontSize: 16, fontWeight: 'bold', color: '#111827' },
  kpiLbl: { fontSize: 10, color: '#6B7280', marginTop: 2 },
  body: { flex: 1, paddingHorizontal: 16, paddingTop: 16 },
  card: { backgroundColor: '#FFF', borderRadius: 16, padding: 16, marginBottom: 16, borderWidth: 1, borderColor: '#E5E7EB', elevation: 1 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  clienteInfo: { flexDirection: 'row', alignItems: 'center' },
  fotoPlaceholder: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#EEF2F6', alignItems: 'center', justifyContent: 'center', marginRight: 10, borderWidth: 1, borderColor: '#E2E8F0', overflow: 'hidden' },
  foto: { width: '100%', height: '100%' },
  inicial: { fontSize: 14, fontWeight: 'bold', color: '#6366F1' },
  clienteNombre: { fontSize: 14, fontWeight: 'bold', color: '#1F2937' },
  fecha: { fontSize: 10, color: '#9CA3AF', marginTop: 1 },
  badge: { fontSize: 9, fontWeight: 'bold', paddingHorizontal: 8, paddingText: 2, borderRadius: 8, overflow: 'hidden', textTransform: 'uppercase' },
  badgeVisible: { backgroundColor: '#D1FAE5', color: '#065F46' },
  badgeOculto: { backgroundColor: '#FEE2E2', color: '#991B1B' },
  ratingBox: { marginBottom: 8 },
  starsRow: { flexDirection: 'row' },
  comentario: { fontSize: 13, fontStyle: 'italic', color: '#4B5563', lineHeight: 18, marginBottom: 12 },
  metaRow: { borderTopWidth: 1, borderTopColor: '#F3F4F6', paddingTop: 8, marginBottom: 12 },
  metaText: { fontSize: 11, color: '#6B7280', marginBottom: 2 },
  metaBold: { color: '#374151', fontWeight: '500' },
  btnRow: { flexDirection: 'row', justifyContent: 'flex-end' },
  actionBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 8, paddingHorizontal: 14, borderRadius: 10 },
  btnOcultar: { backgroundColor: '#9CA3AF' },
  btnAprobar: { backgroundColor: '#EF4444' }, // Coral / Accent
  actionBtnText: { color: '#FFF', fontSize: 12, fontWeight: 'bold' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 80 },
  emptyText: { color: '#6B7280', fontSize: 14, marginTop: 10 }
});
