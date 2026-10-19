<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi, errorValidasi, pesanError } from '@/lib/api'
import { simpanSesi } from '@/lib/auth'

const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errors = ref({})
const pesan = ref(route.query.alasan === 'sesi-habis' ? 'Sesi berakhir, silakan login kembali.' : '')
const memproses = ref(false)

async function masuk() {
  memproses.value = true
  errors.value = {}
  pesan.value = ''

  try {
    const hasil = await authApi.login(form.email, form.password)
    simpanSesi(hasil.token, hasil.user)

    const redirect = route.query.redirect
    const tujuan = typeof redirect === 'string' && redirect.startsWith('/') ? redirect : '/tugas'
    await router.push(tujuan)
  } catch (error) {
    errors.value = errorValidasi(error)
    if (error.response?.status !== 422) {
      pesan.value = pesanError(error, 'Login gagal.')
    }
  } finally {
    memproses.value = false
  }
}
</script>

<template>
  <section class="auth">
    <form class="kartu auth__form" novalidate @submit.prevent="masuk">
      <h1>Masuk</h1>
      <p class="teks-redup">Login untuk mengelola daftar tugas kamu.</p>

      <p v-if="pesan" class="alert alert--error" role="alert">{{ pesan }}</p>

      <label class="field">
        <span class="field__label">Email</span>
        <input
          v-model.trim="form.email"
          type="email"
          name="email"
          autocomplete="username"
          :class="{ 'is-invalid': errors.email }"
        />
        <small v-if="errors.email" class="field__error">{{ errors.email[0] }}</small>
      </label>

      <label class="field">
        <span class="field__label">Password</span>
        <input
          v-model="form.password"
          type="password"
          name="password"
          autocomplete="current-password"
          :class="{ 'is-invalid': errors.password }"
        />
        <small v-if="errors.password" class="field__error">{{ errors.password[0] }}</small>
      </label>

      <button type="submit" class="btn btn--primary btn--block" :disabled="memproses">
        {{ memproses ? 'Memproses…' : 'Masuk' }}
      </button>

      <p class="teks-redup kecil">
        Akun demo: <code>admin@kepl.test</code> / <code>password</code>
      </p>
    </form>
  </section>
</template>
