import { createI18n } from 'vue-i18n';
import es from './locales/es.json';
import en from './locales/en.json';
import it from './locales/it.json';
import pt from './locales/pt.json';

// Definir los mensajes
const messages = {
  es,
  en,
  it,
  pt
};

// Determinar idioma por defecto (localStorage o navegador)
const getBrowserLang = () => {
  const lang = navigator.language.split('-')[0];
  return Object.keys(messages).includes(lang) ? lang : 'es';
};

const savedLang = localStorage.getItem('app_lang');
const defaultLang = savedLang || getBrowserLang();

const i18n = createI18n({
  legacy: false, // Usar Composition API
  locale: defaultLang,
  fallbackLocale: 'es',
  messages,
});

export default i18n;
