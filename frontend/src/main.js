import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { setOnUnauthorized } from './lib/http'

// Token ditolak API (401): sesi sudah dihapus oleh interceptor, tinggal pindah ke halaman login.
setOnUnauthorized(() => {
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { alasan: 'sesi-habis' } })
  }
})

const app = createApp(App)

app.use(router)

app.mount('#app')
