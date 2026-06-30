import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  FlatList,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  StatusBar,
  Alert,
  Dimensions,
  TextInput
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import api from '../services/api';

const { width } = Dimensions.get('window');
const COLUMN_WIDTH = (width - 48) / 2;

export default function PortafolioScreen({ route }) {
  const profesionalId = route?.params?.profesionalId || 1;
  const [trabajos, setTrabajos] = useState([]);
  const [cargando, setCargando] = useState(true);
  const [subiendo, setSubiendo] = useState(false);
  const [titulo, setTitulo] = useState('');
  const [descripcion, setDescripcion] = useState('');

  const obtenerPortafolio = useCallback(async () => {
    try {
      const response = await api.get(`/portafolio/${profesionalId}`);
      if (response.data.success) {
        setTrabajos(response.data.portafolio.data || []);
      }
    } catch (error) {
      Alert.alert('Error', 'No se pudo cargar la galería.');
    } finally {
      setCargando(false);
    }
  }, [profesionalId]);

  useEffect(() => {
    obtenerPortafolio();
  }, [obtenerPortafolio]);

  const seleccionarESubirImagen = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permiso requerido', 'Necesitamos acceso a la galería.');
      return;
    }

    const resultado = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });

    if (resultado.canceled) return;

    const imagenSeleccionada = resultado.assets[0];
    setSubiendo(true);

    const formData = new FormData();
    const uriParts = imagenSeleccionada.uri.split('/');
    const fileName = uriParts[uriParts.length - 1];
    const fileType = fileName.split('.').pop();

    formData.append('archivo', {
      uri: imagenSeleccionada.uri,
      name: fileName,
      type: `image/${fileType === 'jpg' ? 'jpeg' : fileType}`,
    });

    if (titulo) formData.append('titulo', titulo.trim());
    if (descripcion) formData.append('descripcion', descripcion.trim());
    formData.append('destacado', '0');

    try {
      const response = await api.post(`/portafolio/${profesionalId}/subir`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (response.data.success) {
        Alert.alert('¡Éxito!', 'Trabajo añadido al portafolio.');
        setTitulo('');
        setDescripcion('');
        obtenerPortafolio();
      }
    } catch (error) {
      Alert.alert('Error', error.response?.data?.message || 'Error al subir.');
    } finally {
      setSubiendo(false);
    }
  };

  const renderItem = ({ item }) => (
    <View style={styles.card}>
      <Image source={{ uri: item.url_miniatura || item.url }} style={styles.imagen} />
      <View style={styles.details}>
        <Text style={styles.tituloText} numberOfLines={1}>{item.titulo || 'Sin título'}</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#FFF" />
      <View style={styles.form}>
        <Text style={styles.seccion}>Nuevo Trabajo</Text>
        <TextInput style={styles.input} placeholder="Título (ej. Corte Degradado)" placeholderTextColor="#9CA3AF" value={titulo} onChangeText={setTitulo} editable={!subiendo} />
        <TextInput style={styles.input} placeholder="Descripción" placeholderTextColor="#9CA3AF" value={descripcion} onChangeText={setDescripcion} editable={!subiendo} />
        <TouchableOpacity style={[styles.btn, subiendo && styles.btnDis]} onPress={seleccionarESubirImagen} disabled={subiendo}>
          {subiendo ? <ActivityIndicator color="#FFF" /> : <Text style={styles.btnText}>📸 Seleccionar y Subir Foto</Text>}
        </TouchableOpacity>
      </View>
      <View style={styles.galeria}>
        <Text style={styles.seccion}>Trabajos en tu Portafolio</Text>
        {cargando ? (
          <ActivityIndicator size="large" color="#6366F1" style={{ flex: 1 }} />
        ) : (
          <FlatList
            data={trabajos}
            keyExtractor={(item) => item.id.toString()}
            renderItem={renderItem}
            numColumns={2}
            contentContainerStyle={styles.grid}
            columnWrapperStyle={styles.row}
            ListEmptyComponent={<Text style={styles.vacio}>Tu portafolio está vacío.</Text>}
          />
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  form: { backgroundColor: '#FFF', padding: 16, borderBottomLeftRadius: 16, borderBottomRightRadius: 16, elevation: 2 },
  seccion: { fontSize: 15, fontWeight: 'bold', color: '#111827', marginBottom: 8 },
  input: { backgroundColor: '#F9FAFB', borderColor: '#D1D5DB', borderWidth: 1, borderRadius: 8, padding: 8, fontSize: 14, marginBottom: 8 },
  btn: { backgroundColor: '#6366F1', borderRadius: 8, padding: 12, alignItems: 'center' },
  btnDis: { backgroundColor: '#9CA3AF' },
  btnText: { color: '#FFF', fontWeight: 'bold' },
  galeria: { flex: 1, padding: 16 },
  grid: { paddingBottom: 20 },
  row: { justifyContent: 'space-between' },
  card: { backgroundColor: '#FFF', width: COLUMN_WIDTH, borderRadius: 8, marginBottom: 12, overflow: 'hidden', borderWidth: 1, borderColor: '#E5E7EB' },
  imagen: { width: '100%', height: 110, backgroundColor: '#E5E7EB' },
  details: { padding: 6 },
  tituloText: { fontSize: 12, fontWeight: 'bold', color: '#1F2937' },
  vacio: { textAlign: 'center', color: '#6B7280', marginTop: 30 }
});
