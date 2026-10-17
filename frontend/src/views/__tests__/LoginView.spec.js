import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { authApi } from '@/lib/api'
import { getToken, hapusSesi } from '@/lib/auth'
import LoginView from '../LoginView.vue'

vi.mock('@/lib/api', async (importOriginal) => ({
  ...(await importOriginal()),
  authApi: { login: vi.fn() },
}))

async function pasang() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', name: 'login', component: LoginView },
      { path: '/tugas', name: 'tugas', component: { template: '<p>daftar</p>' } },
    ],
  })
  router.push('/login')
  await router.isReady()

  const wrapper = mount(LoginView, { global: { plugins: [router] } })
  return { wrapper, router }
}

describe('LoginView', () => {
  beforeEach(() => {
    hapusSesi()
    vi.mocked(authApi.login).mockReset()
  })

  it('menyimpan token lalu pindah ke halaman tugas saat login berhasil', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      token: '1|abc',
      user: { id: 1, name: 'Admin KEPL', email: 'admin@kepl.test' },
    })
    const { wrapper, router } = await pasang()

    await wrapper.find('input[name="email"]').setValue('admin@kepl.test')
    await wrapper.find('input[name="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(authApi.login).toHaveBeenCalledWith('admin@kepl.test', 'password')
    expect(getToken()).toBe('1|abc')
    expect(router.currentRoute.value.name).toBe('tugas')
  })

  it('menampilkan pesan error per field saat API membalas 422', async () => {
    vi.mocked(authApi.login).mockRejectedValue({
      response: { status: 422, data: { errors: { email: ['Email wajib diisi.'] } } },
    })
    const { wrapper, router } = await pasang()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('.field__error').text()).toBe('Email wajib diisi.')
    expect(getToken()).toBeNull()
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('menampilkan pesan dari API saat password salah (401)', async () => {
    vi.mocked(authApi.login).mockRejectedValue({
      response: { status: 401, data: { message: 'Email atau password salah.' } },
    })
    const { wrapper } = await pasang()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toBe('Email atau password salah.')
  })
})
