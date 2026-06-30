import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity, SafeAreaView, Image } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function WelcomeScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.inner}>
        
        <View style={styles.header}>
          <Text style={styles.logo}>CitasPro</Text>
          <Text style={styles.sub}>Bienvenido a la plataforma inteligente de reservas.</Text>
        </View>

        <View style={styles.opciones}>
          <Text style={styles.pregunta}>¿Cómo deseas ingresar hoy?</Text>

          {/* Tarjeta Cliente/Paciente */}
          <TouchableOpacity 
            style={[styles.tarjeta, styles.tarjetaCliente]} 
            onPress={() => navigation.navigate('Portafolio', { profesional_id: 1 })}
          >
            <Ionicons name="calendar-outline" size={40} color="#10B981" />
            <View style={styles.textos}>
              <Text style={styles.tituloTarjeta}>Soy Paciente / Cliente</Text>
              <Text style={styles.descTarjeta}>Quiero agendar o revisar mis citas con el profesional.</Text>
            </View>
            <Ionicons name="chevron-forward" size={24} color="#10B981" />
          </TouchableOpacity>

          {/* Tarjeta Profesional/Socio */}
          <TouchableOpacity 
            style={[styles.tarjeta, styles.tarjetaSocio]} 
            onPress={() => navigation.navigate('Login')}
          >
            <Ionicons name="briefcase-outline" size={40} color="#6366F1" />
            <View style={styles.textos}>
              <Text style={styles.tituloTarjeta}>Soy Profesional / Socio</Text>
              <Text style={styles.descTarjeta}>Quiero entrar a mi Panel de Control y ver mi agenda.</Text>
            </View>
            <Ionicons name="chevron-forward" size={24} color="#6366F1" />
          </TouchableOpacity>

        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  inner: { flex: 1, justifyContent: 'center', padding: 20 },
  header: { alignItems: 'center', marginBottom: 50 },
  logo: { fontSize: 40, fontWeight: 'bold', color: '#111827' },
  sub: { fontSize: 16, color: '#6B7280', textAlign: 'center', marginTop: 10 },
  opciones: { width: '100%' },
  pregunta: { fontSize: 18, fontWeight: 'bold', color: '#374151', marginBottom: 20, textAlign: 'center' },
  tarjeta: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    backgroundColor: '#FFF', 
    padding: 20, 
    borderRadius: 15, 
    marginBottom: 20,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 10,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#E5E7EB'
  },
  tarjetaCliente: {
    borderLeftWidth: 5,
    borderLeftColor: '#10B981'
  },
  tarjetaSocio: {
    borderLeftWidth: 5,
    borderLeftColor: '#6366F1'
  },
  textos: { flex: 1, marginLeft: 15, marginRight: 10 },
  tituloTarjeta: { fontSize: 18, fontWeight: 'bold', color: '#1F2937' },
  descTarjeta: { fontSize: 13, color: '#6B7280', marginTop: 4 }
});
