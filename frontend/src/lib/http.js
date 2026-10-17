import axios from 'axios'
import { getToken, hapusSesi } from './auth'

/**
 * Alamat API dibaca dari environment variable VITE_API_URL (lihat .env.example),
 * bukan ditulis di komponen. Nilainya ditanam saat `npm run build`.
 * - npm run dev : /api diteruskan proxy Vite ke Laravel (vite.config.js)
 * - Docker      : /api diteruskan Nginx ke service backend
 */
export const API_URL = import.meta.env.VITE_API_URL || '/api'

const http = axios.create({
  baseURL: API_URL,
  headers: { Accept: 'application/json' },
  timeout: 15000,
})

// Setiap request membawa token yang tersimpan saat login.
http.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let onUnauthorized = () => {}

/** Dipasang di main.js: apa yang dilakukan saat token ditolak (401). */
export function setOnUnauthorized(handler) {
  onUnauthorized = handler
}

// Token kedaluwarsa atau dicabut: hapus sesi lokal lalu arahkan ke halaman login.
// Respons 401 dari /login sendiri (password salah) tidak dihitung sebagai sesi habis.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    const dariLogin = error.config?.url?.endsWith('/login')
    if (error.response?.status === 401 && !dariLogin) {
      hapusSesi()
      onUnauthorized()
    }
    return Promise.reject(error)
  },
)

export default http
