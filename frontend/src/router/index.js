import { createRouter, createWebHistory } from 'vue-router'
import { sudahLogin } from '@/lib/auth'
import LoginView from '@/views/LoginView.vue'
import TugasListView from '@/views/TugasListView.vue'

export const routes = [
  { path: '/', redirect: { name: 'tugas' } },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { hanyaTamu: true },
  },
  {
    path: '/tugas',
    name: 'tugas',
    component: TugasListView,
    meta: { perluLogin: true },
  },
  {
    path: '/tugas/tambah',
    name: 'tugas-tambah',
    component: () => import('@/views/TugasFormView.vue'),
    meta: { perluLogin: true },
  },
  {
    path: '/tugas/:id/ubah',
    name: 'tugas-ubah',
    component: () => import('@/views/TugasFormView.vue'),
    meta: { perluLogin: true },
    props: true,
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'tugas' } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Halaman bertanda perluLogin hanya bisa dibuka bila token sudah tersimpan.
router.beforeEach((to) => {
  if (to.meta.perluLogin && !sudahLogin()) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.hanyaTamu && sudahLogin()) {
    return { name: 'tugas' }
  }
})

export default router
