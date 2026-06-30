import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, ActivityIndicator, TouchableOpacity, SafeAreaView } from 'react-native';
import api from '../../services/api';

export default function PortafolioScreen({ route, navigation }) {
  // En la vida real leería el ID del link. Por defecto, 1 (el demo)
  const profesionalId = route.params?.profesional_id || 1; 

  const [profesional, setProfesional] = useState(null);
  const [servicios, setServicios] = useState([]);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const cargarPortafolio = async () => {
      try {
        const res = await api.get(`/paciente/profesional/${profesionalId}`);
        if (res.data.success) {
          setProfesional(res.data.profesional);
          setServicios(res.data.servicios);
        }
      } catch (err) {
        setError('No se pudo cargar el portafolio.');
      } finally {
        setCargando(false);
      }
    };
    cargarPortafolio();
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
        <Text style={styles.headerSub}>Selecciona un servicio para agendar</Text>
      </View>

      <FlatList
        data={servicios}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.lista}
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
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  centro: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F9FAFB' },
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  header: {
    backgroundColor: '#ffffff',
    padding: 30,
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    marginBottom: 10
  },
  negocioNombre: { fontSize: 24, fontWeight: 'bold', color: '#111827' },
  profesionalNombre: { fontSize: 16, color: '#6366F1', marginTop: 5, fontWeight: '500' },
  headerSub: { fontSize: 14, color: '#6B7280', marginTop: 15 },
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
    marginBottom: 16,
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
});
