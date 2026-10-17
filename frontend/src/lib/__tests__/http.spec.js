import { AxiosError } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { hapusSesi, simpanSesi, sudahLogin } from '../auth'
import http, { API_URL, setOnUnauthorized } from '../http'

// Adapter palsu: request tidak benar-benar dikirim, config akhirnya dikembalikan untuk diperiksa.
function balasDengan(status, data = {}) {
  return (config) => {
    const response = { data, status, statusText: String(status), headers: {}, config }
    if (status >= 400) {
      return Promise.reject(new AxiosError('Gagal', 'ERR_BAD_REQUEST', config, null, response))
    }
    return Promise.resolve(response)
  }
}

describe('http (instance Axios)', () => {
  const adapterAsli = http.defaults.adapter

  beforeEach(() => {
    hapusSesi()
  })

  afterEach(() => {
    http.defaults.adapter = adapterAsli
    setOnUnauthorized(() => {})
  })

  it('memakai alamat API dari konfigurasi, bukan alamat yang ditulis mati', () => {
    expect(http.defaults.baseURL).toBe(API_URL)
  })

  it('menambahkan header Authorization Bearer bila sudah login', async () => {
    simpanSesi('1|rahasia', { id: 1, name: 'Admin' })
    http.defaults.adapter = balasDengan(200)

    const { config } = await http.get('/tugas')

    expect(config.headers.Authorization).toBe('Bearer 1|rahasia')
    expect(config.headers.Accept).toBe('application/json')
  })

  it('tidak mengirim header Authorization bila belum login', async () => {
    http.defaults.adapter = balasDengan(200)

    const { config } = await http.get('/tugas')

    expect(config.headers.Authorization).toBeUndefined()
  })

  it('menghapus sesi dan memanggil handler saat API membalas 401', async () => {
    simpanSesi('1|kedaluwarsa', { id: 1, name: 'Admin' })
    const handler = vi.fn()
    setOnUnauthorized(handler)
    http.defaults.adapter = balasDengan(401, { message: 'Unauthenticated.' })

    await expect(http.get('/tugas')).rejects.toThrow('Gagal')

    expect(sudahLogin()).toBe(false)
    expect(handler).toHaveBeenCalledOnce()
  })

  it('tidak menganggap 401 dari /login (password salah) sebagai sesi habis', async () => {
    const handler = vi.fn()
    setOnUnauthorized(handler)
    http.defaults.adapter = balasDengan(401, { message: 'Email atau password salah.' })

    await expect(http.post('/login', {})).rejects.toThrow('Gagal')

    expect(handler).not.toHaveBeenCalled()
  })
})
