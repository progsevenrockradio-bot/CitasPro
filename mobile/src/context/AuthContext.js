import React, { createContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../services/api';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [role, setRole] = useState(null);
  const [cargando, setCargando] = useState(true);

  useEffect(() => {
    const cargarSesion = async () => {
      try {
        const tokenGuardado = await AsyncStorage.getItem('@CitasPro:token');
        const userGuardado = await AsyncStorage.getItem('@CitasPro:user');
        const roleGuardado = await AsyncStorage.getItem('@CitasPro:role');

        if (tokenGuardado && userGuardado) {
          setToken(tokenGuardado);
          setUser(JSON.parse(userGuardado));
          setRole(roleGuardado || 'cliente');
        }
      } catch (e) {
        console.error('Error cargando sesion:', e);
      } finally {
        setCargando(false);
      }
    };
    cargarSesion();
  }, []);

  const login = async (nuevoToken, datosUsuario, rolUsuario = 'cliente') => {
    try {
      await AsyncStorage.setItem('@CitasPro:token', nuevoToken);
      await AsyncStorage.setItem('@CitasPro:user', JSON.stringify(datosUsuario));
      await AsyncStorage.setItem('@CitasPro:role', rolUsuario);
      setToken(nuevoToken);
      setUser(datosUsuario);
      setRole(rolUsuario);
    } catch (e) {
      console.error(e);
    }
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (e) {
      console.log(e);
    } finally {
      await AsyncStorage.removeItem('@CitasPro:token');
      await AsyncStorage.removeItem('@CitasPro:user');
      await AsyncStorage.removeItem('@CitasPro:role');
      setToken(null);
      setUser(null);
      setRole(null);
    }
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated: !!token, user, token, role, cargando, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};
