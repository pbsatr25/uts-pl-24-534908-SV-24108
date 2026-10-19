import { beforeEach, describe, expect, it } from 'vitest'
import { getToken, hapusSesi, sesi, simpanSesi, sudahLogin } from '../auth'

describe('auth', () => {
  beforeEach(() => {
    hapusSesi()
  })

  it('menyimpan token dan user ke localStorage saat login', () => {
    simpanSesi('1|abc', { id: 1, name: 'Admin' })

    expect(sudahLogin()).toBe(true)
    expect(getToken()).toBe('1|abc')
    expect(sesi.user.name).toBe('Admin')
    expect(localStorage.getItem('todo_token')).toBe('1|abc')
  })

  it('menghapus token dari state dan localStorage saat logout', () => {
    simpanSesi('1|abc', { id: 1, name: 'Admin' })
    hapusSesi()

    expect(sudahLogin()).toBe(false)
    expect(sesi.user).toBeNull()
    expect(localStorage.getItem('todo_token')).toBeNull()
  })
})
