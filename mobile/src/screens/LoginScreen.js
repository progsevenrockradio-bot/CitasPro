import React, { useState, useContext } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  StatusBar,
  KeyboardAvoidingView,
  Platform,
  Alert
} from 'react-native';
import { AuthContext } from '../context/AuthContext';
import api from '../services/api';

export default function LoginScreen({ navigation }) {
  const { login } = useContext(AuthContext);
  const [telefono, setTelefono] = useState('');
  const [codigo, setCodigo] = useState('');
  const [paso, setPaso] = useState(1);
  const [cargando, setCargando] = useState(false);
  const [codigoDebug, setCodigoDebug] = useState('');

  const solicitarOtp = async () => {
    if (!telefono || telefono.trim().length < 7) {
      Alert.alert('Atención', 'Por favor ingresa un número de celular válido.');
      return;
    }
    setCargando(true);
    try {
      const response = await api.post('/auth/otp/enviar', { telefono: telefono.trim() });
      if (response.data.success) {
        Alert.alert('Código Enviado', response.data.message);
        setPaso(2);
        if (response.data._debug_codigo) {
          setCodigoDebug(response.data._debug_codigo);
        }
      }
    } catch (error) {
      Alert.alert('Error', error.response?.data?.message || 'Error de conexión.');
    } finally {
      setCargando(false);
    }
  };

  const verificarOtp = async () => {
    if (!codigo || codigo.trim().length !== 6) {
      Alert.alert('Atención', 'El código debe tener 6 dígitos.');
      return;
    }
    setCargando(true);
    try {
      const response = await api.post('/auth/otp/verificar', {
        telefono: telefono.trim(),
        codigo: codigo.trim(),
        nombre_token: Platform.OS === 'ios' ? 'iPhone' : 'Android'
      });
      if (response.data.success) {
        await login(response.data.token, response.data.user || response.data.cliente, response.data.role);
      }
    } catch (error) {
      Alert.alert('Error', error.response?.data?.message || 'Código incorrecto.');
    } finally {
      setCargando(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#1F2937" />
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.kv}>
        <View style={styles.inner}>
          <View style={styles.logoCont}>
            <Text style={styles.logo}>CitasPro</Text>
            <Text style={styles.sub}>Gestión de reservas para profesionales</Text>
          </View>
          <View style={styles.form}>
            {paso === 1 ? (
              <View>
                <Text style={styles.titulo}>Ingresa tu celular</Text>
                <Text style={styles.desc}>Te enviaremos un código por WhatsApp/SMS para acceder sin contraseña.</Text>
                <TextInput
                  style={styles.input}
                  placeholder="+34600111222"
                  placeholderTextColor="#9CA3AF"
                  keyboardType="phone-pad"
                  value={telefono}
                  onChangeText={setTelefono}
                  editable={!cargando}
                />
                <TouchableOpacity style={styles.btn} onPress={solicitarOtp} disabled={cargando}>
                  {cargando ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Recibir código</Text>}
                </TouchableOpacity>
              </View>
            ) : (
              <View>
                <Text style={styles.titulo}>Ingresa el código</Text>
                <Text style={styles.desc}>Escribe los 6 dígitos enviados al celular {telefono}</Text>
                <TextInput
                  style={[styles.input, styles.inputCod]}
                  placeholder="000000"
                  placeholderTextColor="#9CA3AF"
                  keyboardType="number-pad"
                  maxLength={6}
                  value={codigo}
                  onChangeText={setCodigo}
                  editable={!cargando}
                />
                {codigoDebug ? (
                  <View style={styles.debug}>
                    <Text style={styles.debugText}>⚠️ CÓDIGO OTP LOCAL: {codigoDebug}</Text>
                  </View>
                ) : null}
                <TouchableOpacity style={styles.btn} onPress={verificarOtp} disabled={cargando}>
                  {cargando ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>Ingresar</Text>}
                </TouchableOpacity>
                <TouchableOpacity style={styles.btnSec} onPress={() => setPaso(1)} disabled={cargando}>
                  <Text style={styles.btnSecText}>Cambiar teléfono</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#1F2937' },
  kv: { flex: 1 },
  inner: { flex: 1, justifyContent: 'center', paddingHorizontal: 24 },
  logoCont: { alignItems: 'center', marginBottom: 40 },
  logo: { fontSize: 40, fontWeight: 'bold', color: '#6366F1' },
  sub: { fontSize: 13, color: '#9CA3AF', marginTop: 6, textAlign: 'center' },
  form: { backgroundColor: '#FFF', borderRadius: 20, padding: 24, elevation: 8 },
  titulo: { fontSize: 20, fontWeight: 'bold', color: '#111827', marginBottom: 6 },
  desc: { fontSize: 13, color: '#6B7280', marginBottom: 20, lineHeight: 18 },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, padding: 12, fontSize: 16, color: '#111827', marginBottom: 16 },
  inputCod: { textAlign: 'center', fontSize: 22, fontWeight: 'bold', letterSpacing: 6 },
  btn: { backgroundColor: '#6366F1', borderRadius: 10, padding: 14, alignItems: 'center' },
  btnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  btnSec: { marginTop: 12, alignItems: 'center' },
  btnSecText: { color: '#4B5563', fontWeight: '600' },
  debug: { backgroundColor: '#FEF3C7', padding: 8, borderRadius: 8, marginBottom: 12, borderWidth: 1, borderColor: '#F59E0B' },
  debugText: { color: '#D97706', fontSize: 12, fontWeight: 'bold', textAlign: 'center' }
});
