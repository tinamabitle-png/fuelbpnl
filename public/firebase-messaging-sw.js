/* global importScripts, firebase */

importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: 'AIzaSyCpcvLISEcL9dKupWx0YFA78GudmjDYXFE',
  authDomain: 'fuellevy-d381e.firebaseapp.com',
  projectId: 'fuellevy-d381e',
  messagingSenderId: '1093745774511',
  appId: '1:1093745774511:web:84479d97d6fcae53a0295a',
  measurementId: 'G-T4L03Q8HLF',
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const notificationTitle = payload?.notification?.title || 'New notification';
  const notificationOptions = {
    body: payload?.notification?.body || 'New admin update received.',
    icon: '/images/brand-logo.png',
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
