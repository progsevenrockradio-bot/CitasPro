import React, { useState, useEffect, useRef } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  Animated,
} from 'react-native';
import { Calendar, LocaleConfig } from 'react-native-calendars';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import api from '../../services/api';

// Configurar idioma español
LocaleConfig.locales['es'] = {
  monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
  monthNamesShort: ['Ene.', 'Feb.', 'Mar', 'Abr', 'May', 'Jun', 'Jul.', 'Ago', 'Sept.', 'Oct.', 'Nov.', 'Dic.'],
  dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
  dayNamesShort: ['Dom.', 'Lun.', 'Mar.', 'Mié.', 'Jue.', 'Vie.', 'Sáb.'],
  today: 'Hoy'
};
LocaleConfig.defaultLocale = 'es';

export default function ReservaScreen({ route, navigation }) {
  const { servicio, profesionalId } = route.params;
  const insets = useSafeAreaInsets();
  const scrollViewRef = useRef(null);

  const [fecha, setFecha] = useState(new Date().toISOString().split('T')[0]);
  const [horas, setHoras] = useState([]);
  const [horaSeleccionada, setHoraSeleccionada] = useState(null);
  const [cargandoHoras, setCargandoHoras] = useState(false);

  // Formulario paciente
  const [nombre, setNombre] = useState('');
  const [apellido, setApellido] = useState('');
  const [telefono, setTelefono] = useState('');
  const [enviando, setEnviando] = useState(false);

  // Estados adicionales para pago
  const [metodoPago, setMetodoPago] = useState('efectivo'); // 'efectivo' | 'stripe'
  const [createdCita, setCreatedCita] = useState(null);
  const [showMockCardModal, setShowMockCardModal] = useState(false);
  const [numeroTarjeta, setNumeroTarjeta] = useState('');
  const [nombreTitular, setNombreTitular] = useState('');
  const [expiracion, setExpiracion] = useState('');
  const [cvv, setCvv] = useState('');
  const [pagandoTarjeta, setPagandoTarjeta] = useState(false);

  // Animación de la barra inferior
  const barraAnim = useState(new Animated.Value(0))[0];

  useEffect(() => {
    cargarDisponibilidad(fecha);
  }, [fecha]);

  // Animar la barra cuando se selecciona una hora
  useEffect(() => {
    Animated.spring(barraAnim, {
      toValue: horaSeleccionada ? 1 : 0,
      useNativeDriver: true,
      tension: 60,
      friction: 8,
    }).start();

    // Auto-scroll al final para mostrar los inputs y botones
    if (horaSeleccionada) {
      setTimeout(() => {
        scrollViewRef.current?.scrollToEnd({ animated: true });
      }, 150);
    }
  }, [horaSeleccionada]);

  const cargarDisponibilidad = async (dateStr) => {
    setCargandoHoras(true);
    setHoraSeleccionada(null);
    try {
      const res = await api.get(`/paciente/profesional/${profesionalId}/disponibilidad?fecha=${dateStr}`);
      if (res.data.success) {
        setHoras(res.data.disponibles);
      }
    } catch (err) {
      console.log('Error cargando horas', err);
    } finally {
      setCargandoHoras(false);
    }
  };

  // Alerta personalizada
  const [modalAlerta, setModalAlerta] = useState({
    visible: false,
    titulo: '',
    mensaje: '',
    tipo: 'info', // 'success', 'error', 'warning', 'info'
    onConfirm: null,
  });

  // Helper para mostrar la alerta personalizada
  const mostrarAlerta = (titulo, mensaje, tipo = 'info', onConfirm = null) => {
    setModalAlerta({
      visible: true,
      titulo,
      mensaje,
      tipo,
      onConfirm,
    });
  };

  const confirmarReserva = async () => {
    // Validar si el servicio se deserializó mal como string "[object Object]" al abrir la URL directa en incógnito
    if (!servicio || typeof servicio === 'string' || !servicio.id) {
      mostrarAlerta(
        'Error de navegación',
        'Los datos del servicio no se cargaron correctamente. Por favor, vuelve atrás y selecciona el servicio desde el portafolio de nuevo.',
        'warning'
      );
      return;
    }

    if (!horaSeleccionada || !nombre || !apellido || !telefono) {
      mostrarAlerta('Faltan datos', 'Por favor, completa tus datos de contacto.', 'warning');
      return;
    }

    setEnviando(true);
    const payload = {
      profesional_id: profesionalId,
      servicio_id: servicio.id,
      fecha: fecha,
      hora: horaSeleccionada,
      cliente: { nombre, apellido, telefono }
    };

    console.log('Enviando reserva...', payload);

    try {
      const res = await api.post('/paciente/reservar', payload);
      console.log('Respuesta del servidor:', res.data);

      if (res.data.success) {
        const cita = res.data.cita;
        setCreatedCita(cita);

        // Procesar pago en base al método elegido
        if (metodoPago === 'efectivo') {
          // Procesar el pago en efectivo
          await api.post('/paciente/pagos/procesar', {
            cita_id: cita.id,
            telefono: telefono,
            metodo: 'efectivo'
          });

          mostrarAlerta('¡Reserva Confirmada!', 'Te esperamos con gusto. Pagas al asistir.', 'success', () => {
            navigation.navigate('Portafolio');
          });
        } else {
          // Guardar cita y abrir modal para pago seguro simulado con Stripe
          setEnviando(false);
          setShowMockCardModal(true);
        }
      }
    } catch (err) {
      console.error('Error al reservar:', err);
      const errMsg = err.response?.data?.message || 'Hubo un problema al agendar. Por favor, inténtalo de nuevo.';
      mostrarAlerta('Error', errMsg, 'error');
      setEnviando(false);
    }
  };

  // Ejecuta la confirmación del pago simulado con tarjeta
  const procesarPagoTarjetaSimulado = async () => {
    if (!numeroTarjeta || !nombreTitular || !expiracion || !cvv) {
      Alert.alert('Faltan datos', 'Por favor completa la información de la tarjeta.');
      return;
    }

    setPagandoTarjeta(true);

    try {
      // 1. Obtener la intención de pago del servidor
      const resIntencion = await api.post('/paciente/pagos/procesar', {
        cita_id: createdCita.id,
        telefono: telefono,
        metodo: 'stripe'
      });

      if (resIntencion.data.success) {
        // 2. Simular confirmación exitosa llamando al endpoint simulado del webhook local
        const resConfirmacion = await api.post('/paciente/pagos/confirmar-simulado', {
          cita_id: createdCita.id,
          telefono: telefono
        });

        if (resConfirmacion.data.success) {
          setShowMockCardModal(false);
          setNumeroTarjeta('');
          setNombreTitular('');
          setExpiracion('');
          setCvv('');
          
          mostrarAlerta('¡Pago Aprobado!', 'Tu cita ha sido confirmada y pagada con éxito.', 'success', () => {
            navigation.navigate('Portafolio');
          });
        }
      }
    } catch (error) {
      console.error('Error en simulación de pago:', error);
      Alert.alert('Error', 'No se pudo simular la transacción con tarjeta.');
    } finally {
      setPagandoTarjeta(false);
    }
  };

  // Altura dinámica de la barra fija según safe-area
  const barraAltura = 80 + insets.bottom;

  const barraTranslateY = barraAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [barraAltura, 0],
  });

  return (
    <KeyboardAvoidingView
      style={{ flex: 1, backgroundColor: '#F9FAFB', height: Platform.OS === 'web' ? '100vh' : '100%' }}
      behavior={Platform.OS === 'ios' ? 'padding' : Platform.OS === 'android' ? 'height' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 20}
    >
      {/* Contenido scrollable */}
      <ScrollView
        ref={scrollViewRef}
        style={styles.container}
        contentContainerStyle={{
          flexGrow: 1,
          // Espacio inferior adecuado para no quedar detrás de la barra fija
          paddingBottom: horaSeleccionada ? barraAltura + 40 : 32,
        }}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={true}
      >

        {/* RESUMEN SERVICIO */}
        <View style={styles.header}>
          {/* Botones de Navegación Rápida */}
          <View style={styles.navFila}>
            <TouchableOpacity style={styles.btnVolver} onPress={() => navigation.canGoBack() ? navigation.goBack() : navigation.navigate('Portafolio', { profesional_id: profesionalId })}>
              <Text style={styles.txtVolver}>← Volver</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.btnInicio} onPress={() => navigation.navigate('Welcome')}>
              <Text style={styles.txtInicio}>🏠 Inicio</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.titulo}>Agendar {servicio.nombre}</Text>
          <Text style={styles.precio}>{servicio.precio} € - {servicio.duracion_minutos} min</Text>
        </View>

        {/* CALENDARIO - Solo visible si no se ha elegido hora */}
        {!horaSeleccionada && (
          <Calendar
            current={fecha}
            minDate={new Date().toISOString().split('T')[0]}
            onDayPress={(day) => setFecha(day.dateString)}
            markedDates={{
              [fecha]: { selected: true, selectedColor: '#6366F1' }
            }}
            theme={{
              todayTextColor: '#10B981',
              arrowColor: '#6366F1',
            }}
          />
        )}

        {/* HORAS - Solo visible si no se ha elegido hora */}
        {!horaSeleccionada && (
          <View style={styles.seccion}>
            <Text style={styles.label}>Horas disponibles para el {fecha}</Text>
            {cargandoHoras ? (
              <ActivityIndicator style={{ marginTop: 10 }} color="#6366F1" />
            ) : horas.length === 0 ? (
              <Text style={styles.vacio}>No hay horas disponibles este día.</Text>
            ) : (
              <View style={styles.gridHoras}>
                {horas.map((h, i) => (
                  <TouchableOpacity
                    key={i}
                    style={[styles.btnHora, horaSeleccionada === h && styles.btnHoraActiva]}
                    onPress={() => setHoraSeleccionada(h)}
                  >
                    <Text style={[styles.txtHora, horaSeleccionada === h && styles.txtHoraActiva]}>{h}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            )}
          </View>
        )}

        {/* FORMULARIO PACIENTE — Visible al elegir hora, ocupando la pantalla principal */}
        {horaSeleccionada && (
          <View style={styles.seccionForm}>
            <View style={styles.resumenHora}>
              <Text style={styles.resumenHoraTexto}>
                📅 Fecha: <Text style={{ fontWeight: 'bold' }}>{fecha}</Text>{"\n"}
                ⏰ Hora: <Text style={{ fontWeight: 'bold' }}>{horaSeleccionada}</Text>
              </Text>
              <TouchableOpacity 
                style={styles.btnCambiarHora} 
                onPress={() => setHoraSeleccionada(null)}
              >
                <Text style={styles.btnCambiarHoraTxt}>Cambiar Fecha/Hora</Text>
              </TouchableOpacity>
            </View>

            <Text style={styles.label}>Tus Datos de Contacto</Text>
            <TextInput
              style={styles.input}
              placeholder="Nombre"
              value={nombre}
              onChangeText={setNombre}
              returnKeyType="next"
            />
            <TextInput
              style={styles.input}
              placeholder="Apellido"
              value={apellido}
              onChangeText={setApellido}
              returnKeyType="next"
            />
            <TextInput
              style={styles.input}
              placeholder="Teléfono"
              keyboardType="phone-pad"
              value={telefono}
              onChangeText={setTelefono}
              returnKeyType="done"
            />

            <Text style={[styles.label, { marginTop: 15 }]}>Método de Pago</Text>
            <View style={styles.selectorPagoContainer}>
              <TouchableOpacity
                style={[styles.btnMetodoPago, metodoPago === 'efectivo' && styles.btnMetodoPagoActivo]}
                onPress={() => setMetodoPago('efectivo')}
                activeOpacity={0.8}
              >
                <Text style={[styles.txtMetodoPago, metodoPago === 'efectivo' && styles.txtMetodoPagoActivo]}>💵 Efectivo en local</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.btnMetodoPago, metodoPago === 'stripe' && styles.btnMetodoPagoActivo]}
                onPress={() => setMetodoPago('stripe')}
                activeOpacity={0.8}
              >
                <Text style={[styles.txtMetodoPago, metodoPago === 'stripe' && styles.txtMetodoPagoActivo]}>💳 Tarjeta (Stripe)</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      </ScrollView>

      {/* =====================================================
          BARRA DE ACCIÓN FIJA EN LA PARTE INFERIOR
          Se anima desde abajo cuando se selecciona una hora.
          Siempre visible encima de cualquier otro elemento.
          ===================================================== */}
      <Animated.View
        style={[
          styles.barraFija,
          {
            // Respeta la safe-area del dispositivo (notch, barra de inicio iPhone)
            paddingBottom: insets.bottom + 12,
            transform: [{ translateY: barraTranslateY }],
          },
        ]}
        pointerEvents={horaSeleccionada ? 'auto' : 'none'}
      >
        <View style={styles.barraContenido}>
          {/* Indicador de la selección */}
          <View style={styles.barraInfo}>
            <Text style={styles.barraHoraTxt} numberOfLines={1}>
              {horaSeleccionada || ''}
            </Text>
            <Text style={styles.barraFechaTxt} numberOfLines={1}>
              {fecha}
            </Text>
          </View>

          {/* Botón Cancelar */}
          <TouchableOpacity
            style={styles.btnCancelar}
            onPress={() => setHoraSeleccionada(null)}
            activeOpacity={0.7}
          >
            <Text style={styles.btnCancelarTxt}>Cancelar</Text>
          </TouchableOpacity>

          {/* Botón Confirmar */}
          <TouchableOpacity
            style={[styles.btnConfirmar, enviando && styles.btnConfirmarDeshabilitado]}
            onPress={confirmarReserva}
            disabled={enviando}
            activeOpacity={0.8}
          >
            {enviando
              ? <ActivityIndicator color="#FFF" size="small" />
              : <Text style={styles.btnConfirmarTxt}>Confirmar</Text>
            }
          </TouchableOpacity>
        </View>
      </Animated.View>

      {/* MODAL DE ALERTA PERSONALIZADA */}
      {modalAlerta.visible && (
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={[
              styles.modalIconBg, 
              modalAlerta.tipo === 'success' && styles.modalIconSuccess,
              modalAlerta.tipo === 'error' && styles.modalIconError,
              modalAlerta.tipo === 'warning' && styles.modalIconWarning,
            ]}>
              <Text style={[
                styles.modalIconTxt,
                modalAlerta.tipo === 'success' && styles.modalIconTxtSuccess,
                modalAlerta.tipo === 'error' && styles.modalIconTxtError,
                modalAlerta.tipo === 'warning' && styles.modalIconTxtWarning,
              ]}>
                {modalAlerta.tipo === 'success' ? '✓' : modalAlerta.tipo === 'error' ? '✕' : modalAlerta.tipo === 'warning' ? '⚠' : 'ℹ'}
              </Text>
            </View>
            <Text style={styles.modalTitulo}>{modalAlerta.titulo}</Text>
            <Text style={styles.modalMensaje}>{modalAlerta.mensaje}</Text>
            <TouchableOpacity 
              style={[
                styles.modalBtn,
                modalAlerta.tipo === 'success' && styles.modalBtnSuccess,
                modalAlerta.tipo === 'error' && styles.modalIconTxtError,
                modalAlerta.tipo === 'warning' && styles.modalBtnWarning,
              ]} 
              onPress={() => {
                const onConfirmCallback = modalAlerta.onConfirm;
                setModalAlerta({ ...modalAlerta, visible: false });
                if (onConfirmCallback) onConfirmCallback();
              }}
            >
              <Text style={styles.modalBtnTxt}>Aceptar</Text>
            </TouchableOpacity>
          </View>
        </View>
      )}

      {/* MODAL MOCK DE STRIPE */}
      {showMockCardModal && (
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitulo}>💳 Pago Seguro (Stripe Simulado)</Text>
            <Text style={[styles.modalMensaje, { marginBottom: 12 }]}>
              Ingresa los datos de tu tarjeta para abonar {servicio.precio} € por {servicio.nombre}
            </Text>

            <TextInput
              style={styles.inputModal}
              placeholder="Nombre del Titular (ej. Juan Pérez)"
              value={nombreTitular}
              onChangeText={setNombreTitular}
              placeholderTextColor="#9CA3AF"
            />
            <TextInput
              style={styles.inputModal}
              placeholder="Número de Tarjeta (ej. 4242 4242 4242 4242)"
              keyboardType="numeric"
              value={numeroTarjeta}
              onChangeText={setNumeroTarjeta}
              maxLength={19}
              placeholderTextColor="#9CA3AF"
            />
            <View style={styles.filaInputsModal}>
              <TextInput
                style={[styles.inputModal, { flex: 1, marginRight: 8 }]}
                placeholder="MM/AA"
                value={expiracion}
                onChangeText={setExpiracion}
                maxLength={5}
                placeholderTextColor="#9CA3AF"
              />
              <TextInput
                style={[styles.inputModal, { flex: 1 }]}
                placeholder="CVV"
                keyboardType="numeric"
                secureTextEntry
                value={cvv}
                onChangeText={setCvv}
                maxLength={4}
                placeholderTextColor="#9CA3AF"
              />
            </View>

            <View style={styles.filaBotonesModal}>
              <TouchableOpacity
                style={[styles.btnCancelarModal]}
                onPress={() => setShowMockCardModal(false)}
                disabled={pagandoTarjeta}
              >
                <Text style={styles.btnCancelarModalTxt}>Cancelar</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.btnPagarModal]}
                onPress={procesarPagoTarjetaSimulado}
                disabled={pagandoTarjeta}
              >
                {pagandoTarjeta ? (
                  <ActivityIndicator color="#FFF" size="small" />
                ) : (
                  <Text style={styles.btnPagarModalTxt}>Pagar {servicio.precio} €</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      )}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
    height: Platform.OS === 'web' ? '100%' : undefined,
    maxHeight: Platform.OS === 'web' ? '100%' : undefined,
    overflowY: Platform.OS === 'web' ? 'auto' : undefined,
  },

  // --- Cabecera ---
  header: {
    padding: 20,
    backgroundColor: '#1E1B4B',
  },
  navFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  btnVolver: {
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
  },
  txtVolver: {
    color: '#FFF',
    fontWeight: 'bold',
    fontSize: 14,
  },
  btnInicio: {
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
  },
  txtInicio: {
    color: '#FFF',
    fontWeight: 'bold',
    fontSize: 14,
  },
  titulo: {
    color: '#FFF',
    fontSize: 20,
    fontWeight: 'bold',
  },
  precio: {
    color: '#10B981',
    fontSize: 16,
    marginTop: 5,
    fontWeight: '600',
  },

  // --- Secciones ---
  seccion: {
    padding: 20,
    backgroundColor: '#FFF',
    marginTop: 10,
  },
  label: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 15,
  },
  vacio: {
    color: '#6B7280',
    fontStyle: 'italic',
  },

  // --- Grid de horas ---
  gridHoras: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  btnHora: {
    padding: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 8,
    marginRight: 10,
    marginBottom: 10,
    width: '30%',
    alignItems: 'center',
  },
  btnHoraActiva: {
    backgroundColor: '#6366F1',
    borderColor: '#6366F1',
  },
  txtHora: {
    color: '#374151',
    fontWeight: 'bold',
  },
  txtHoraActiva: {
    color: '#FFF',
  },

  // --- Formulario ---
  seccionForm: {
    padding: 20,
    backgroundColor: '#FFF',
    marginTop: 10,
  },
  resumenHora: {
    backgroundColor: '#EEF2FF',
    borderRadius: 8,
    padding: 12,
    marginBottom: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#6366F1',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
  },
  resumenHoraTexto: {
    color: '#3730A3',
    fontSize: 14,
    flex: 1,
    minWidth: 150,
  },
  btnCambiarHora: {
    backgroundColor: '#6366F1',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 6,
  },
  btnCambiarHoraTxt: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    fontSize: 16,
    color: '#111827',
  },

  // --- Barra fija inferior ---
  barraFija: {
    position: Platform.OS === 'web' ? 'fixed' : 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFF',
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingTop: 12,
    paddingHorizontal: 16,
    // Sombra visible en ambas plataformas
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.12,
    shadowRadius: 8,
    elevation: 20,  // Android
    zIndex: 999,    // Garantiza estar por encima de todo
  },
  barraContenido: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  barraInfo: {
    flex: 1,
  },
  barraHoraTxt: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E1B4B',
  },
  barraFechaTxt: {
    fontSize: 12,
    color: '#6B7280',
    marginTop: 2,
  },
  btnCancelar: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    backgroundColor: '#F9FAFB',
  },
  btnCancelarTxt: {
    color: '#374151',
    fontWeight: '600',
    fontSize: 14,
  },
  btnConfirmar: {
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
    backgroundColor: '#10B981',
    minWidth: 110,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnConfirmarDeshabilitado: {
    backgroundColor: '#6EE7B7',
  },
  btnConfirmarTxt: {
    color: '#FFF',
    fontSize: 15,
    fontWeight: 'bold',
  },

  // --- Modal Alerta ---
  modalOverlay: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 9999,
  },
  modalCard: {
    backgroundColor: '#FFF',
    width: '85%',
    maxWidth: 380,
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 24,
  },
  modalIconBg: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#E0F2FE',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalIconSuccess: {
    backgroundColor: '#D1FAE5',
  },
  modalIconError: {
    backgroundColor: '#FEE2E2',
  },
  modalIconWarning: {
    backgroundColor: '#FEF3C7',
  },
  modalIconTxt: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#0369A1',
    lineHeight: Platform.OS === 'web' ? 40 : undefined,
  },
  modalIconTxtSuccess: {
    color: '#047857',
  },
  modalIconTxtError: {
    color: '#B91C1C',
  },
  modalIconTxtWarning: {
    color: '#B45309',
  },
  modalTitulo: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
    textAlign: 'center',
  },
  modalMensaje: {
    fontSize: 15,
    color: '#4B5563',
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 24,
  },
  modalBtn: {
    width: '100%',
    paddingVertical: 14,
    borderRadius: 8,
    backgroundColor: '#6366F1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalBtnSuccess: {
    backgroundColor: '#10B981',
  },
  modalBtnError: {
    backgroundColor: '#EF4444',
  },
  modalBtnWarning: {
    backgroundColor: '#F59E0B',
  },
  modalBtnTxt: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  // --- Nuevos estilos de pago ---
  selectorPagoContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    marginBottom: 20,
  },
  btnMetodoPago: {
    flex: 1,
    paddingVertical: 12,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    borderRadius: 10,
    alignItems: 'center',
    marginHorizontal: 4,
    backgroundColor: '#FFF',
  },
  btnMetodoPagoActivo: {
    borderColor: '#6366F1',
    backgroundColor: '#EEF2FF',
  },
  txtMetodoPago: {
    fontSize: 13,
    fontWeight: '600',
    color: '#4B5563',
  },
  txtMetodoPagoActivo: {
    color: '#6366F1',
  },
  inputModal: {
    width: '100%',
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    marginBottom: 10,
    fontSize: 14,
    color: '#1F2937',
  },
  filaInputsModal: {
    flexDirection: 'row',
    width: '100%',
    marginBottom: 16,
  },
  filaBotonesModal: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
  },
  btnCancelarModal: {
    flex: 1,
    paddingVertical: 12,
    marginRight: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    backgroundColor: '#FFF',
    alignItems: 'center',
  },
  btnCancelarModalTxt: {
    color: '#4B5563',
    fontWeight: 'bold',
  },
  btnPagarModal: {
    flex: 2,
    paddingVertical: 12,
    borderRadius: 8,
    backgroundColor: '#10B981',
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnPagarModalTxt: {
    color: '#FFF',
    fontWeight: 'bold',
  },
});
