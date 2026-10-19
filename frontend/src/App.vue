<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { authApi } from '@/lib/api'
import { hapusSesi, sesi } from '@/lib/auth'

const router = useRouter()

async function keluar() {
  try {
    await authApi.logout()
  } catch {
    // Token mungkin sudah tidak berlaku; sesi lokal tetap dihapus.
  }
  hapusSesi()
  await router.push({ name: 'login' })
}
</script>

<template>
  <header class="topbar">
    <div class="container topbar__isi">
      <RouterLink :to="{ name: 'tugas' }" class="brand">
        <span class="brand__ikon" aria-hidden="true">✓</span> To-Do List
      </RouterLink>
      <div v-if="sesi.token" class="topbar__user">
        <span class="teks-redup">{{ sesi.user?.name }}</span>
        <button type="button" class="btn btn--ghost" @click="keluar">Keluar</button>
      </div>
    </div>
  </header>

  <main class="container konten">
    <RouterView />
  </main>

  <footer class="container footer teks-redup">
    UTS Konstruksi dan Evolusi Perangkat Lunak · Prihastomo Budi Satrio · 24/534908/SV/24108
  </footer>
</template>
