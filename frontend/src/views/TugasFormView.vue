<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { errorValidasi, pesanError, tugasApi } from '@/lib/api'
import { LABEL_PRIORITAS, LABEL_STATUS } from '@/lib/format'
import { titipNotifikasi } from '@/lib/notifikasi'

// Satu komponen untuk dua halaman: /tugas/tambah (tanpa id) dan /tugas/:id/ubah.
const props = defineProps({
  id: { type: String, default: null },
})

const router = useRouter()
const modeUbah = computed(() => props.id !== null)

const form = reactive({
  judul: '',
  deskripsi: '',
  prioritas: 'sedang',
  tenggat: '',
  status: 'belum',
})
const errors = ref({})
const pesan = ref('')
const memuat = ref(false)
const gagalMuat = ref(false)
const menyimpan = ref(false)

onMounted(async () => {
  if (!modeUbah.value) {
    return
  }
  memuat.value = true
  try {
    const tugas = await tugasApi.ambil(props.id)
    Object.assign(form, {
      judul: tugas.judul,
      deskripsi: tugas.deskripsi ?? '',
      prioritas: tugas.prioritas,
      tenggat: tugas.tenggat ?? '',
      status: tugas.status,
    })
  } catch (error) {
    gagalMuat.value = true
    pesan.value = pesanError(error, 'Gagal memuat tugas.')
  } finally {
    memuat.value = false
  }
})

async function simpan() {
  menyimpan.value = true
  errors.value = {}
  pesan.value = ''

  const data = {
    ...form,
    deskripsi: form.deskripsi || null,
    tenggat: form.tenggat || null,
  }

  try {
    const hasil = modeUbah.value ? await tugasApi.ubah(props.id, data) : await tugasApi.tambah(data)
    titipNotifikasi(hasil.message)
    await router.push({ name: 'tugas' })
  } catch (error) {
    errors.value = errorValidasi(error)
    pesan.value =
      error.response?.status === 422
        ? 'Periksa kembali isian yang ditandai merah.'
        : pesanError(error, 'Gagal menyimpan tugas.')
  } finally {
    menyimpan.value = false
  }
}
</script>

<template>
  <section class="form-wadah">
    <div class="judul-halaman">
      <h1>{{ modeUbah ? 'Ubah Tugas' : 'Tambah Tugas' }}</h1>
      <RouterLink :to="{ name: 'tugas' }" class="btn btn--ghost">← Kembali</RouterLink>
    </div>

    <p v-if="memuat" class="kartu kosong">Memuat data tugas…</p>

    <p v-else-if="gagalMuat" class="alert alert--error">{{ pesan }}</p>

    <form v-else class="kartu" novalidate @submit.prevent="simpan">
      <p v-if="pesan" class="alert alert--error" role="alert">{{ pesan }}</p>

      <label class="field">
        <span class="field__label">Judul <span class="wajib">*</span></span>
        <input v-model.trim="form.judul" type="text" name="judul" :class="{ 'is-invalid': errors.judul }" />
        <small v-if="errors.judul" class="field__error">{{ errors.judul[0] }}</small>
      </label>

      <label class="field">
        <span class="field__label">Deskripsi</span>
        <textarea v-model="form.deskripsi" name="deskripsi" rows="3" :class="{ 'is-invalid': errors.deskripsi }" />
        <small v-if="errors.deskripsi" class="field__error">{{ errors.deskripsi[0] }}</small>
      </label>

      <div class="field-baris">
        <label class="field">
          <span class="field__label">Prioritas <span class="wajib">*</span></span>
          <select v-model="form.prioritas" name="prioritas" :class="{ 'is-invalid': errors.prioritas }">
            <option v-for="(label, nilai) in LABEL_PRIORITAS" :key="nilai" :value="nilai">{{ label }}</option>
          </select>
          <small v-if="errors.prioritas" class="field__error">{{ errors.prioritas[0] }}</small>
        </label>

        <label class="field">
          <span class="field__label">Tenggat</span>
          <input v-model="form.tenggat" type="date" name="tenggat" :class="{ 'is-invalid': errors.tenggat }" />
          <small v-if="errors.tenggat" class="field__error">{{ errors.tenggat[0] }}</small>
        </label>

        <label class="field">
          <span class="field__label">Status <span class="wajib">*</span></span>
          <select v-model="form.status" name="status" :class="{ 'is-invalid': errors.status }">
            <option v-for="(label, nilai) in LABEL_STATUS" :key="nilai" :value="nilai">{{ label }}</option>
          </select>
          <small v-if="errors.status" class="field__error">{{ errors.status[0] }}</small>
        </label>
      </div>

      <div class="aksi-form">
        <RouterLink :to="{ name: 'tugas' }" class="btn btn--ghost">Batal</RouterLink>
        <button type="submit" class="btn btn--primary" :disabled="menyimpan">
          {{ menyimpan ? 'Menyimpan…' : modeUbah ? 'Simpan Perubahan' : 'Tambah Tugas' }}
        </button>
      </div>
    </form>
  </section>
</template>
