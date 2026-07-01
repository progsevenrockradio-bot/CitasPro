import React, { useState, useEffect, useContext } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, TextInput, ActivityIndicator, Alert, Switch, Linking } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';
import { AuthContext } from '../context/AuthContext';

export default function SettingsScreen() {
  const { logout, role } = useContext(AuthContext);
  const [activeTab, setActiveTab] = useState('servicios'); // 'servicios' | 'horario'
  const [loading, setLoading] = useState(true);
  
  // Servicios State
  const [servicios, setServicios] = useState([]);
  const [editingService, setEditingService] = useState(null); // null = list, {} = new, {id...} = edit
  
  // Horarios State
  const [horario, setHorario] = useState(null);

  // Suscripción State
  const [negocio, setNegocio] = useState(null);
  const [planes, setPlanes] = useState(null);
  const [procesandoSuscripcion, setProcesandoSuscripcion] = useState(false);

  useEffect(() => {
    if (role === 'profesional') {
      fetchData();
    } else {
      setLoading(false);
    }
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [resServicios, resHorario, resNegocio, resPlanes] = await Promise.all([
        api.get('/servicios'),
        api.get('/horarios'),
        api.get('/negocio'),
        api.get('/suscripciones/planes')
      ]);
      setServicios(resServicios.data.servicios || []);
      setHorario(resHorario.data.horario || {});
      setNegocio(resNegocio.data);
      setPlanes(resPlanes.data.planes || {});
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'No se pudieron cargar los datos de configuración.');
    } finally {
      setLoading(false);
    }
  };

  // --- SERVICIOS LOGIC ---
  const handleSaveService = async () => {
    if (!editingService.nombre || !editingService.precio || !editingService.duracion_min) {
      return Alert.alert('Error', 'Completa los campos obligatorios (Nombre, Precio, Duración).');
    }
    
    try {
      setLoading(true);
      if (editingService.id) {
        // Edit
        await api.patch(`/servicios/${editingService.id}`, editingService);
      } else {
        // Create
        await api.post('/servicios', editingService);
      }
      setEditingService(null);
      await fetchData();
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'No se pudo guardar el servicio.');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteService = async (id) => {
    Alert.alert('Confirmar', '¿Seguro que deseas eliminar este servicio?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Eliminar', style: 'destructive', onPress: async () => {
          try {
            setLoading(true);
            await api.delete(`/servicios/${id}`);
            await fetchData();
          } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudo eliminar.');
            setLoading(false);
          }
      }}
    ]);
  };

  // --- HORARIOS LOGIC ---
  const toggleDia = (dia) => {
    setHorario(prev => ({
      ...prev,
      [dia]: { ...prev[dia], activo: !prev[dia].activo }
    }));
  };

  const updateHora = (dia, campo, valor) => {
    setHorario(prev => ({
      ...prev,
      [dia]: { ...prev[dia], [campo]: valor }
    }));
  };

  const handleSaveHorario = async () => {
    try {
      setLoading(true);
      await api.put('/horarios', { horario });
      Alert.alert('Éxito', 'Horario actualizado correctamente.');
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'No se pudo guardar el horario.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpgrade = async (planKey) => {
    setProcesandoSuscripcion(true);
    try {
      const res = await api.post('/suscripciones/suscribir', { plan: planKey });
      if (res.data.success && res.data.checkout_url) {
        // Abrir pasarela de Stripe en navegador
        await Linking.openURL(res.data.checkout_url);
      } else {
        Alert.alert('Error', 'No se pudo generar la sesión de pago.');
      }
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'Error al conectar con la pasarela de pagos.');
    } finally {
      setProcesandoSuscripcion(false);
    }
  };

  const handleCancelarSuscripcion = async () => {
    Alert.alert('Confirmar', '¿Seguro que deseas cancelar la renovación de tu suscripción? Mantendrás el acceso premium hasta el final del periodo facturado.', [
      { text: 'No', style: 'cancel' },
      { text: 'Sí, cancelar', style: 'destructive', onPress: async () => {
          setProcesandoSuscripcion(true);
          try {
            const res = await api.delete('/suscripciones/cancelar');
            if (res.data.success) {
              Alert.alert('Suscripción Cancelada', res.data.message);
              fetchData();
            }
          } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudo procesar la cancelación.');
          } finally {
            setProcesandoSuscripcion(false);
          }
      }}
    ]);
  };


  if (role !== 'profesional') {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.centerBox}>
          <Ionicons name="lock-closed" size={48} color="#6B7280" />
          <Text style={styles.noAccessText}>Solo los profesionales pueden acceder a la configuración.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Ajustes</Text>
        <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
          <Ionicons name="log-out-outline" size={24} color="#EF4444" />
        </TouchableOpacity>
      </View>

      <View style={styles.tabs}>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'servicios' && styles.tabActive]} 
          onPress={() => { setActiveTab('servicios'); setEditingService(null); }}
        >
          <Text style={[styles.tabText, activeTab === 'servicios' && styles.tabTextActive]}>Servicios</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'horario' && styles.tabActive]} 
          onPress={() => { setActiveTab('horario'); setEditingService(null); }}
        >
          <Text style={[styles.tabText, activeTab === 'horario' && styles.tabTextActive]}>Mi Horario</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.tab, activeTab === 'suscripcion' && styles.tabActive]} 
          onPress={() => { setActiveTab('suscripcion'); setEditingService(null); }}
        >
          <Text style={[styles.tabText, activeTab === 'suscripcion' && styles.tabTextActive]}>Suscripción</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#6366F1" />
        </View>
      ) : (
        <ScrollView style={styles.content}>
          
          {/* TAB: SERVICIOS */}
          {activeTab === 'servicios' && !editingService && (
            <View>
              <TouchableOpacity style={styles.addButton} onPress={() => setEditingService({ nombre: '', precio: '', duracion_min: '30', activo: true })}>
                <Ionicons name="add-circle" size={24} color="#FFF" />
                <Text style={styles.addButtonText}>Añadir Nuevo Servicio</Text>
              </TouchableOpacity>

              {servicios.map(srv => (
                <View key={srv.id} style={styles.serviceCard}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.serviceName}>{srv.nombre}</Text>
                    <Text style={styles.serviceDetails}>{srv.duracion_min} min • ${srv.precio}</Text>
                  </View>
                  <View style={styles.serviceActions}>
                    <TouchableOpacity style={styles.actionBtn} onPress={() => setEditingService({...srv, precio: srv.precio.toString(), duracion_min: srv.duracion_min.toString()})}>
                      <Ionicons name="pencil" size={20} color="#6366F1" />
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.actionBtn} onPress={() => handleDeleteService(srv.id)}>
                      <Ionicons name="trash" size={20} color="#EF4444" />
                    </TouchableOpacity>
                  </View>
                </View>
              ))}
              
              {servicios.length === 0 && (
                <Text style={styles.emptyText}>No tienes servicios configurados.</Text>
              )}
            </View>
          )}

          {activeTab === 'servicios' && editingService && (
            <View style={styles.formContainer}>
              <Text style={styles.formTitle}>{editingService.id ? 'Editar Servicio' : 'Nuevo Servicio'}</Text>
              
              <Text style={styles.label}>Nombre del servicio</Text>
              <TextInput 
                style={styles.input} 
                value={editingService.nombre}
                onChangeText={t => setEditingService({...editingService, nombre: t})}
                placeholder="Ej. Corte Clásico"
                placeholderTextColor="#6B7280"
              />

              <View style={styles.rowInputs}>
                <View style={{flex: 1, marginRight: 8}}>
                  <Text style={styles.label}>Precio ($)</Text>
                  <TextInput 
                    style={styles.input} 
                    value={editingService.precio}
                    onChangeText={t => setEditingService({...editingService, precio: t})}
                    keyboardType="numeric"
                    placeholder="15.00"
                    placeholderTextColor="#6B7280"
                  />
                </View>
                <View style={{flex: 1, marginLeft: 8}}>
                  <Text style={styles.label}>Duración (min)</Text>
                  <TextInput 
                    style={styles.input} 
                    value={editingService.duracion_min}
                    onChangeText={t => setEditingService({...editingService, duracion_min: t})}
                    keyboardType="numeric"
                    placeholder="30"
                    placeholderTextColor="#6B7280"
                  />
                </View>
              </View>

              <View style={styles.rowBtns}>
                <TouchableOpacity style={[styles.btn, styles.btnCancel]} onPress={() => setEditingService(null)}>
                  <Text style={styles.btnText}>Cancelar</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[styles.btn, styles.btnSave]} onPress={handleSaveService}>
                  <Text style={styles.btnText}>Guardar</Text>
                </TouchableOpacity>
              </View>
            </View>
          )}

          {/* TAB: HORARIO */}
          {activeTab === 'horario' && horario && (
            <View style={styles.horarioContainer}>
              <Text style={styles.infoText}>Configura tus horas de disponibilidad para reservas online.</Text>
              
              {['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'].map(dia => (
                <View key={dia} style={styles.diaRow}>
                  <View style={styles.diaHeader}>
                    <Switch
                      trackColor={{ false: "#374151", true: "#6366F1" }}
                      thumbColor="#FFF"
                      onValueChange={() => toggleDia(dia)}
                      value={horario[dia].activo}
                    />
                    <Text style={[styles.diaName, !horario[dia].activo && { color: '#6B7280' }]}>
                      {dia.charAt(0).toUpperCase() + dia.slice(1)}
                    </Text>
                  </View>
                  
                  {horario[dia].activo && (
                    <View style={styles.horasInputs}>
                      <TextInput 
                        style={styles.horaInput} 
                        value={horario[dia].inicio}
                        onChangeText={t => updateHora(dia, 'inicio', t)}
                        placeholder="09:00"
                        placeholderTextColor="#6B7280"
                      />
                      <Text style={{color: '#9CA3AF'}}> a </Text>
                      <TextInput 
                        style={styles.horaInput} 
                        value={horario[dia].fin}
                        onChangeText={t => updateHora(dia, 'fin', t)}
                        placeholder="19:00"
                        placeholderTextColor="#6B7280"
                      />
                    </View>
                  )}
                </View>
              ))}

              <TouchableOpacity style={[styles.btn, styles.btnSave, {marginTop: 20}]} onPress={handleSaveHorario}>
                <Text style={styles.btnText}>Guardar Horario</Text>
              </TouchableOpacity>
            </View>
          )}

          {/* TAB: SUSCRIPCION */}
          {activeTab === 'suscripcion' && negocio && planes && (
            <View style={styles.suscripcionContainer}>
              <View style={styles.planCard}>
                <Text style={styles.planTitle}>Tu Plan Actual: <Text style={styles.planNombre}>{negocio.plan.toUpperCase()}</Text></Text>
                {negocio.plan !== 'free' && negocio.plan_vence_en && (
                  <Text style={styles.planVence}>
                    Renovación el: {new Date(negocio.plan_vence_en).toLocaleDateString()}
                  </Text>
                )}
                {negocio.plan === 'free' && (
                  <Text style={styles.planVence}>Acceso básico limitado.</Text>
                )}
              </View>

              <Text style={styles.seccionTitle}>Planes Disponibles</Text>
              
              {Object.keys(planes).map(key => {
                const plan = planes[key];
                const esPlanActual = negocio.plan === key;
                
                return (
                  <View key={key} style={[styles.planItemCard, esPlanActual && styles.planItemCardActivo]}>
                    <View style={styles.planItemHeader}>
                      <Text style={styles.planItemNombre}>{plan.nombre}</Text>
                      <Text style={styles.planItemPrecio}>{plan.precio_mes} €/mes</Text>
                    </View>
                    
                    <Text style={styles.planItemDetalle}>
                      • Profesionales: {plan.limite_profesionales || 'Ilimitados'}{"\n"}
                      • Citas/mes: {plan.limite_citas_mes || 'Ilimitadas'}{"\n"}
                      • Portafolio: {plan.portafolio ? '✅ Incluido' : '❌ No disponible'}{"\n"}
                      • Soporte: {plan.soporte}
                    </Text>

                    {esPlanActual ? (
                      <View style={styles.btnPlanActual}>
                        <Text style={styles.btnPlanActualTxt}>Tu Plan Activo</Text>
                      </View>
                    ) : key !== 'free' ? (
                      <TouchableOpacity
                        style={[styles.btnPlanUpgrade]}
                        onPress={() => handleUpgrade(key)}
                        disabled={procesandoSuscripcion}
                      >
                        <Text style={styles.btnPlanUpgradeTxt}>Contratar Plan</Text>
                      </TouchableOpacity>
                    ) : null}
                  </View>
                );
              })}

              {negocio.stripe_subscription_id && (
                <TouchableOpacity
                  style={[styles.btnCancelarSuscripcion]}
                  onPress={handleCancelarSuscripcion}
                  disabled={procesandoSuscripcion}
                >
                  <Text style={styles.btnCancelarSuscripcionTxt}>Cancelar Renovación Automática</Text>
                </TouchableOpacity>
              )}
            </View>
          )}

          <View style={{height: 100}} />
        </ScrollView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#111827',
  },
  centerBox: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#1F2937',
  },
  headerTitle: {
    color: '#FFF',
    fontSize: 24,
    fontWeight: '700',
  },
  logoutBtn: {
    padding: 8,
    backgroundColor: '#1F2937',
    borderRadius: 8,
  },
  tabs: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingTop: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#1F2937',
  },
  tab: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
  },
  tabActive: {
    borderBottomWidth: 2,
    borderBottomColor: '#6366F1',
  },
  tabText: {
    color: '#9CA3AF',
    fontSize: 16,
    fontWeight: '600',
  },
  tabTextActive: {
    color: '#6366F1',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  noAccessText: {
    color: '#9CA3AF',
    marginTop: 12,
    textAlign: 'center',
    fontSize: 16,
  },
  // --- Suscripción Styles ---
  suscripcionContainer: {
    paddingBottom: 40,
  },
  planCard: {
    backgroundColor: '#1F2937',
    padding: 20,
    borderRadius: 12,
    marginBottom: 24,
    borderWidth: 1,
    borderColor: '#374151',
  },
  planTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFF',
  },
  planNombre: {
    color: '#6366F1',
  },
  planVence: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 6,
  },
  seccionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
    marginBottom: 16,
  },
  planItemCard: {
    backgroundColor: '#1F2937',
    padding: 20,
    borderRadius: 12,
    marginBottom: 16,
    borderWidth: 2,
    borderColor: 'transparent',
  },
  planItemCardActivo: {
    borderColor: '#6366F1',
  },
  planItemHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#374151',
    paddingBottom: 8,
  },
  planItemNombre: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFF',
  },
  planItemPrecio: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#10B981',
  },
  planItemDetalle: {
    fontSize: 14,
    color: '#9CA3AF',
    lineHeight: 22,
    marginBottom: 16,
  },
  btnPlanActual: {
    backgroundColor: '#374151',
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnPlanActualTxt: {
    color: '#9CA3AF',
    fontWeight: 'bold',
  },
  btnPlanUpgrade: {
    backgroundColor: '#6366F1',
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnPlanUpgradeTxt: {
    color: '#FFF',
    fontWeight: 'bold',
  },
  btnCancelarSuscripcion: {
    marginTop: 10,
    paddingVertical: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#EF4444',
    alignItems: 'center',
  },
  btnCancelarSuscripcionTxt: {
    color: '#EF4444',
    fontWeight: 'bold',
  },
  // Servicios
  addButton: {
    backgroundColor: '#6366F1',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 14,
    borderRadius: 12,
    marginBottom: 20,
  },
  addButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '600',
    marginLeft: 8,
  },
  serviceCard: {
    backgroundColor: '#1F2937',
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#374151',
  },
  serviceName: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  serviceDetails: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  serviceActions: {
    flexDirection: 'row',
    gap: 12,
  },
  actionBtn: {
    padding: 8,
    backgroundColor: '#374151',
    borderRadius: 8,
  },
  emptyText: {
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 20,
  },
  // Form
  formContainer: {
    backgroundColor: '#1F2937',
    padding: 20,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#374151',
  },
  formTitle: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 20,
  },
  label: {
    color: '#9CA3AF',
    fontSize: 14,
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#111827',
    color: '#FFF',
    borderWidth: 1,
    borderColor: '#374151',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    marginBottom: 16,
  },
  rowInputs: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  rowBtns: {
    flexDirection: 'row',
    marginTop: 8,
  },
  btn: {
    flex: 1,
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnCancel: {
    backgroundColor: '#374151',
    marginRight: 8,
  },
  btnSave: {
    backgroundColor: '#6366F1',
    marginLeft: 8,
  },
  btnText: {
    color: '#FFF',
    fontWeight: '600',
    fontSize: 16,
  },
  // Horarios
  horarioContainer: {
    paddingBottom: 20,
  },
  infoText: {
    color: '#9CA3AF',
    marginBottom: 20,
    fontSize: 15,
  },
  diaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#1F2937',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#374151',
  },
  diaHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    width: 120,
  },
  diaName: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '500',
    marginLeft: 12,
  },
  horasInputs: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    justifyContent: 'flex-end',
  },
  horaInput: {
    backgroundColor: '#111827',
    color: '#FFF',
    borderWidth: 1,
    borderColor: '#374151',
    borderRadius: 6,
    paddingVertical: 8,
    paddingHorizontal: 12,
    width: 70,
    textAlign: 'center',
  }
});
