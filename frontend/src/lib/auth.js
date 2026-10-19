import { reactive, readonly } from 'vue'

const KUNCI_TOKEN = 'todo_token'
const KUNCI_USER = 'todo_user'

function bacaUser() {
  try {
    return JSON.parse(localStorage.getItem(KUNCI_USER))
  } catch {
    return null
  }
}

const state = reactive({
  token: localStorage.getItem(KUNCI_TOKEN),
  user: bacaUser(),
})

/** Sesi login yang reaktif (hanya-baca) untuk dipakai komponen. */
export const sesi = readonly(state)

export function getToken() {
  return state.token
}

export function sudahLogin() {
  return Boolean(state.token)
}

/** Simpan token dari POST /api/login agar tetap ada setelah halaman dimuat ulang. */
export function simpanSesi(token, user) {
  state.token = token
  state.user = user
  localStorage.setItem(KUNCI_TOKEN, token)
  localStorage.setItem(KUNCI_USER, JSON.stringify(user))
}

export function hapusSesi() {
  state.token = null
  state.user = null
  localStorage.removeItem(KUNCI_TOKEN)
  localStorage.removeItem(KUNCI_USER)
}
