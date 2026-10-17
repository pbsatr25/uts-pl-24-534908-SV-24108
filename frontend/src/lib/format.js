export const LABEL_STATUS = {
  belum: 'Belum',
  proses: 'Dikerjakan',
  selesai: 'Selesai',
}

export const LABEL_PRIORITAS = {
  rendah: 'Rendah',
  sedang: 'Sedang',
  tinggi: 'Tinggi',
}

/** '2026-10-25' -> '25 Okt 2026'. Tanggal dibaca sebagai tanggal lokal, bukan UTC. */
export function formatTanggal(iso) {
  if (!iso) {
    return '-'
  }
  const [tahun, bulan, hari] = iso.split('-').map(Number)
  return new Date(tahun, bulan - 1, hari).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

/** Jumlah tugas per status untuk kartu ringkasan dan tab filter. */
export function hitungRingkasan(daftar) {
  const ringkasan = { semua: daftar.length, belum: 0, proses: 0, selesai: 0 }
  for (const tugas of daftar) {
    ringkasan[tugas.status] += 1
  }
  return ringkasan
}
