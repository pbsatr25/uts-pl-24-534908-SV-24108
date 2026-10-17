import { describe, expect, it } from 'vitest'
import { formatTanggal, hitungRingkasan, LABEL_STATUS } from '../format'

describe('format', () => {
  it('memformat tanggal ISO menjadi tanggal Indonesia', () => {
    expect(formatTanggal('2026-10-25')).toBe('25 Okt 2026')
    expect(formatTanggal(null)).toBe('-')
  })

  it('menghitung jumlah tugas per status', () => {
    const daftar = [{ status: 'belum' }, { status: 'belum' }, { status: 'proses' }, { status: 'selesai' }]

    expect(hitungRingkasan(daftar)).toEqual({ semua: 4, belum: 2, proses: 1, selesai: 1 })
    expect(hitungRingkasan([])).toEqual({ semua: 0, belum: 0, proses: 0, selesai: 0 })
  })

  it('punya label untuk setiap status yang diterima API', () => {
    expect(LABEL_STATUS).toEqual({ belum: 'Belum', proses: 'Proses', selesai: 'Selesai' })
  })
})
