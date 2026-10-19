import http from './http'

export const authApi = {
  login: (email, password) => http.post('/login', { email, password }).then((r) => r.data),
  me: () => http.get('/me').then((r) => r.data.data),
  logout: () => http.post('/logout').then((r) => r.data),
}

export const tugasApi = {
  daftar: () => http.get('/tugas').then((r) => r.data.data),
  ambil: (id) => http.get(`/tugas/${id}`).then((r) => r.data.data),
  tambah: (data) => http.post('/tugas', data).then((r) => r.data),
  ubah: (id, data) => http.put(`/tugas/${id}`, data).then((r) => r.data),
  ubahStatus: (id, status) => http.patch(`/tugas/${id}`, { status }).then((r) => r.data),
  hapus: (id) => http.delete(`/tugas/${id}`).then((r) => r.data),
}

/** Pesan error yang ramah untuk ditampilkan ke pengguna. */
export function pesanError(error, cadangan = 'Terjadi kesalahan.') {
  if (!error.response) {
    return 'Tidak dapat terhubung ke server API.'
  }
  return error.response.data?.message ?? cadangan
}

/** Error validasi per field dari respons 422 Laravel: { judul: ['...'], ... }. */
export function errorValidasi(error) {
  if (error.response?.status !== 422) {
    return {}
  }
  return error.response.data?.errors ?? {}
}
