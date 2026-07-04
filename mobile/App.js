import React, { useContext } from 'react';
import { ActivityIndicator, View, StyleSheet, Platform } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

import { AuthProvider, AuthContext } from './src/context/AuthContext';
import LoginScreen from './src/screens/LoginScreen';
import MainTabs from './src/navigation/MainTabs';
import PortafolioScreen from './src/screens/paciente/PortafolioScreen';
import ReservaScreen from './src/screens/paciente/ReservaScreen';
import WelcomeScreen from './src/screens/WelcomeScreen';



const Stack = createStackNavigator();

const linking = {
  prefixes: [
    'http://localhost:8081', 
    'https://citaspro.app',
    'https://reservas.citaspro.app',
  ],
  config: {
    screens: {
      Welcome: 'reservas',
      Portafolio: 'reservas/citas/:profesional_id',
      Reserva: 'reservas/reservar',
      Login: 'reservas/login',
      Main: {
        path: 'reservas/panel',
        screens: {
          Dashboard: 'inicio',
          Agenda: 'agenda'
        }
      }
    },
  },
};

function AppNavigator() {
  const { isAuthenticated, cargando } = useContext(AuthContext);

  if (cargando) {
    return (
      <View style={styles.centro}>
        <ActivityIndicator size="large" color="#6366F1" />
      </View>
    );
  }

  return (
    <NavigationContainer linking={linking}>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {!isAuthenticated ? (
          // Flujo Público y de Autenticación
          <>
            <Stack.Screen name="Welcome" component={WelcomeScreen} />
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Portafolio" component={PortafolioScreen} />
            <Stack.Screen name="Reserva" component={ReservaScreen} />
          </>
        ) : (
          // Flujo de la Aplicación Privada
          <Stack.Screen name="Main" component={MainTabs} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <AppNavigator />
      </AuthProvider>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  centro: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#1F2937',
  },
});
