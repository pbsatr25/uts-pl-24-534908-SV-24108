import { ref } from 'vue'

const notifikasi = ref(null)

/** Titip pesan untuk ditampilkan di halaman berikutnya (misalnya setelah simpan). */
export function titipNotifikasi(teks, jenis = 'sukses') {
  notifikasi.value = { teks, jenis }
}

/** Ambil pesan titipan sekali saja, lalu kosongkan. */
export function ambilNotifikasi() {
  const isi = notifikasi.value
  notifikasi.value = null
  return isi
}
