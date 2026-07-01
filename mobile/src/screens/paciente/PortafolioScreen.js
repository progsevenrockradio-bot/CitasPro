import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, ActivityIndicator, TouchableOpacity, SafeAreaView } from 'react-native';
import api from '../../services/api';

export default function PortafolioScreen({ route, navigation }) {
  const profesionalId = route.params?.profesional_id || 1; 

  const [profesional, setProfesional] = useState(null);
  const [servicios, setServicios] = useState([]);
  const [resenas, setResenas] = useState([]);
  const [activeTab, setActiveTab] = useState('servicios'); // 'servicios' | 'opiniones'
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const cargarDatos = async () => {
      try {
        const [resPortafolio, resResenas] = await Promise.all([
          api.get(`/paciente/profesional/${profesionalId}`),
          api.get(`/resenas/profesional/${profesionalId}`)
        ]);

        if (resPortafolio.data.success) {
          setProfesional(resPortafolio.data.profesional);
          setServicios(resPortafolio.data.servicios);
        }
        
        // Cargar reseñas si existen
        if (resResenas.data && resResenas.data.data) {
          setResenas(resResenas.data.data);
        }
      } catch (err) {
        console.error('Error al cargar portafolio/reseñas:', err);
        setError('No se pudo cargar la información del profesional.');
      } finally {
        setCargando(false);
      }
    };
    cargarDatos();
  }, [profesionalId]);

  if (cargando) {
    return (
      <View style={styles.centro}>
        <ActivityIndicator size="large" color="#6366F1" />
      </View>
    );
  }

  if (error || !profesional) {
    return (
      <View style={styles.centro}>
        <Text style={styles.error}>{error || 'Profesional no encontrado'}</Text>
      </View>
    );
  }

  // Estrellas formateadas
  const ratingVal = parseFloat(profesional.calificacion_promedio) || 0;
  const ratingStr = ratingVal > 0 ? `⭐ ${ratingVal.toFixed(1)} (${profesional.total_resenas} opiniones)` : '⭐ Sin calificaciones';

  return (
    <SafeAreaView style={styles.container}>
      {/* HEADER DEL NEGOCIO */}
      <View style={styles.header}>
        <View style={styles.navFila}>
          <TouchableOpacity style={styles.btnVolver} onPress={() => navigation.canGoBack() ? navigation.goBack() : navigation.navigate('Welcome')}>
            <Text style={styles.txtVolver}>← Volver</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.btnInicio} onPress={() => navigation.navigate('Welcome')}>
            <Text style={styles.txtInicio}>🏠 Inicio</Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.negocioNombre}>{profesional.negocio?.nombre}</Text>
        <Text style={styles.profesionalNombre}>Con {profesional.nombre} {profesional.apellido}</Text>
        <Text style={styles.ratingText}>{ratingStr}</Text>
      </View>

      {/* TABS DE NAVEGACIÓN LOCAL */}
      <View style={styles.tabsContainer}>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'servicios' && styles.tabActive]} 
          onPress={() => setActiveTab('servicios')}
        >
          <Text style={[styles.tabTxt, activeTab === 'servicios' && styles.tabTxtActive]}>Servicios</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'opiniones' && styles.tabActive]} 
          onPress={() => setActiveTab('opiniones')}
        >
          <Text style={[styles.tabTxt, activeTab === 'opiniones' && styles.tabTxtActive]}>Opiniones ({resenas.length})</Text>
        </TouchableOpacity>
      </View>

      {activeTab === 'servicios' ? (
        <FlatList
          data={servicios}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.lista}
          ListEmptyComponent={<Text style={styles.vacioText}>No hay servicios disponibles.</Text>}
          renderItem={({ item }) => (
            <TouchableOpacity 
              style={styles.card}
              onPress={() => navigation.navigate('Reserva', { 
                servicio: item, 
                profesionalId: profesional.id 
              })}
            >
              <View style={styles.cardContent}>
                <View>
                  <Text style={styles.servicioNombre}>{item.nombre}</Text>
                  <Text style={styles.servicioDuracion}>⏱ {item.duracion_minutos} mins</Text>
                </View>
                <Text style={styles.servicioPrecio}>{item.precio} €</Text>
              </View>
            </TouchableOpacity>
          )}
        />
      ) : (
        <FlatList
          data={resenas}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.lista}
          ListEmptyComponent={
            <View style={styles.vacioBox}>
              <Text style={styles.vacioText}>Aún no hay opiniones sobre este profesional.</Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.reviewCard}>
              <View style={styles.reviewHeader}>
                <Text style={styles.reviewAutor}>{item.cliente?.nombre} {item.cliente?.apellido || ''}</Text>
                <Text style={styles.reviewStars}>{'★'.repeat(item.calificacion)}{'☆'.repeat(5 - item.calificacion)}</Text>
              </View>
              {item.comentario && <Text style={styles.reviewComentario}>{item.comentario}</Text>}
              <Text style={styles.reviewFecha}>{new Date(item.created_at).toLocaleDateString()}</Text>
            </View>
          )}
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  centro: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F9FAFB' },
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  header: {
    backgroundColor: '#ffffff',
    padding: 24,
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  negocioNombre: { fontSize: 22, fontWeight: 'bold', color: '#111827' },
  profesionalNombre: { fontSize: 16, color: '#6366F1', marginTop: 4, fontWeight: '500' },
  ratingText: { fontSize: 14, color: '#4B5563', marginTop: 6, fontWeight: 'bold' },
  tabsContainer: {
    flexDirection: 'row',
    backgroundColor: '#FFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  tab: {
    flex: 1,
    paddingVertical: 14,
    alignItems: 'center',
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  tabActive: {
    borderBottomColor: '#6366F1',
  },
  tabTxt: {
    fontSize: 15,
    fontWeight: '600',
    color: '#6B7280',
  },
  tabTxtActive: {
    color: '#6366F1',
  },
  lista: { padding: 20 },
  card: {
    backgroundColor: '#FFF',
    padding: 20,
    borderRadius: 12,
    marginBottom: 15,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
    elevation: 2,
  },
  cardContent: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  servicioNombre: { fontSize: 16, fontWeight: 'bold', color: '#1F2937' },
  servicioDuracion: { fontSize: 13, color: '#6B7280', marginTop: 4 },
  servicioPrecio: { fontSize: 18, fontWeight: 'bold', color: '#10B981' },
  error: { color: '#EF4444', fontWeight: 'bold' },
  navFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
    marginBottom: 12,
  },
  btnVolver: {
    backgroundColor: '#F3F4F6',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
  },
  txtVolver: {
    color: '#1F2937',
    fontWeight: 'bold',
    fontSize: 14,
  },
  btnInicio: {
    backgroundColor: '#F3F4F6',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
  },
  txtInicio: {
    color: '#1F2937',
    fontWeight: 'bold',
    fontSize: 14,
  },
  vacioText: {
    textAlign: 'center',
    color: '#9CA3AF',
    marginTop: 30,
    fontSize: 15,
  },
  reviewCard: {
    backgroundColor: '#FFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  reviewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  reviewAutor: {
    fontWeight: 'bold',
    fontSize: 14,
    color: '#1F2937',
  },
  reviewStars: {
    color: '#F59E0B',
    fontSize: 14,
  },
  reviewComentario: {
    fontSize: 14,
    color: '#4B5563',
    lineHeight: 20,
    marginBottom: 6,
  },
  reviewFecha: {
    fontSize: 11,
    color: '#9CA3AF',
    textAlign: 'right',
  },
  vacioBox: {
    paddingVertical: 40,
    alignItems: 'center',
  }
});
