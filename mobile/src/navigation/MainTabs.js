import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import DashboardScreen from '../screens/DashboardScreen';
import AgendaScreen from '../screens/AgendaScreen';
import TestimoniosScreen from '../screens/TestimoniosScreen';
import SettingsScreen from '../screens/SettingsScreen';

const Tab = createBottomTabNavigator();

export default function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarIcon: ({ focused, color, size }) => {
          let iconName;

          if (route.name === 'Dashboard') {
            iconName = focused ? 'home' : 'home-outline';
          } else if (route.name === 'Agenda') {
            iconName = focused ? 'calendar' : 'calendar-outline';
          } else if (route.name === 'Testimonios') {
            iconName = focused ? 'star' : 'star-outline';
          } else if (route.name === 'Ajustes') {
            iconName = focused ? 'settings' : 'settings-outline';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#6366F1', // Color primario índigo
        tabBarInactiveTintColor: '#9CA3AF', // Color inactivo gris
        tabBarStyle: {
          backgroundColor: '#1F2937', // Fondo oscuro (combina con el panel)
          borderTopColor: '#374151',
          paddingBottom: 5,
          height: 60,
        },
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Agenda" component={AgendaScreen} />
      <Tab.Screen name="Testimonios" component={TestimoniosScreen} />
      <Tab.Screen name="Ajustes" component={SettingsScreen} />
    </Tab.Navigator>
  );
}
