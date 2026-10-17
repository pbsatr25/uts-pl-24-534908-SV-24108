<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { pesanError, tugasApi } from '@/lib/api'
import { formatTanggal, hitungRingkasan, LABEL_PRIORITAS, LABEL_STATUS } from '@/lib/format'
import { ambilNotifikasi } from '@/lib/notifikasi'

const FILTER = [
  { nilai: 'semua', label: 'Semua' },
  { nilai: 'belum', label: 'Belum' },
  { nilai: 'proses', label: 'Proses' },
  { nilai: 'selesai', label: 'Selesai' },
]

const daftar = ref([])
const filter = ref('semua')
const memuat = ref(true)
const galat = ref('')
const info = ref(ambilNotifikasi())
const idDiproses = ref(null)

const ringkasan = computed(() => hitungRingkasan(daftar.value))
const tampil = computed(() =>
  filter.value === 'semua' ? daftar.value : daftar.value.filter((t) => t.status === filter.value),
)

async function muat() {
  memuat.value = true
  galat.value = ''
  try {
    daftar.value = await tugasApi.daftar()
  } catch (error) {
    galat.value = pesanError(error, 'Gagal memuat daftar tugas.')
  } finally {
    memuat.value = false
  }
}

async function tandaiSelesai(tugas) {
  idDiproses.value = tugas.id
  try {
    await tugasApi.ubahStatus(tugas.id, 'selesai')
    info.value = { teks: `"${tugas.judul}" ditandai selesai.`, jenis: 'sukses' }
    await muat()
  } catch (error) {
    info.value = { teks: pesanError(error, 'Gagal mengubah status.'), jenis: 'error' }
  } finally {
    idDiproses.value = null
  }
}

async function hapus(tugas) {
  if (!window.confirm(`Hapus tugas "${tugas.judul}"? Tindakan ini tidak bisa dibatalkan.`)) {
    return
  }
  idDiproses.value = tugas.id
  try {
    const hasil = await tugasApi.hapus(tugas.id)
    daftar.value = daftar.value.filter((t) => t.id !== tugas.id)
    info.value = { teks: hasil.message, jenis: 'sukses' }
  } catch (error) {
    info.value = { teks: pesanError(error, 'Gagal menghapus tugas.'), jenis: 'error' }
  } finally {
    idDiproses.value = null
  }
}

onMounted(muat)
</script>

<template>
  <section>
    <div class="judul-halaman">
      <div>
        <h1>Daftar Tugas</h1>
        <p class="teks-redup">Tugas yang belum selesai dan tenggatnya paling dekat tampil di atas.</p>
      </div>
      <RouterLink :to="{ name: 'tugas-tambah' }" class="btn btn--primary">+ Tambah Tugas</RouterLink>
    </div>

    <p v-if="info" class="alert" :class="`alert--${info.jenis}`" role="status">
      {{ info.teks }}
      <button type="button" class="alert__tutup" aria-label="Tutup" @click="info = null">×</button>
    </p>

    <div class="tab-filter" role="tablist">
      <button
        v-for="f in FILTER"
        :key="f.nilai"
        type="button"
        role="tab"
        class="tab-filter__item"
        :class="{ 'is-aktif': filter === f.nilai }"
        :aria-selected="filter === f.nilai"
        @click="filter = f.nilai"
      >
        {{ f.label }} <span class="tab-filter__jumlah">{{ ringkasan[f.nilai] }}</span>
      </button>
    </div>

    <p v-if="memuat" class="kartu kosong">Memuat daftar tugas…</p>
    <p v-else-if="galat" class="alert alert--error">
      {{ galat }}
      <button type="button" class="btn btn--ghost" @click="muat">Coba lagi</button>
    </p>
    <p v-else-if="tampil.length === 0" class="kartu kosong">
      {{ filter === 'semua' ? 'Belum ada tugas. Tambahkan tugas pertama kamu.' : 'Tidak ada tugas dengan status ini.' }}
    </p>

    <div v-else class="kartu tabel-wadah">
      <table class="tabel">
        <thead>
          <tr>
            <th>Tugas</th>
            <th>Prioritas</th>
            <th>Tenggat</th>
            <th>Status</th>
            <th class="kanan">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tugas in tampil" :key="tugas.id" :class="{ 'is-selesai': tugas.status === 'selesai' }">
            <td>
              <strong class="tugas__judul">{{ tugas.judul }}</strong>
              <p v-if="tugas.deskripsi" class="teks-redup kecil">{{ tugas.deskripsi }}</p>
            </td>
            <td>
              <span class="badge" :class="`badge--${tugas.prioritas}`">{{ LABEL_PRIORITAS[tugas.prioritas] }}</span>
            </td>
            <td class="nowrap">
              {{ formatTanggal(tugas.tenggat) }}
              <span v-if="tugas.terlambat" class="badge badge--terlambat">Terlambat</span>
            </td>
            <td>
              <span class="badge" :class="`badge--${tugas.status}`">{{ LABEL_STATUS[tugas.status] }}</span>
            </td>
            <td class="kanan nowrap">
              <button
                v-if="tugas.status !== 'selesai'"
                type="button"
                class="btn btn--kecil btn--sukses"
                :disabled="idDiproses === tugas.id"
                @click="tandaiSelesai(tugas)"
              >
                Selesai
              </button>
              <RouterLink :to="{ name: 'tugas-ubah', params: { id: tugas.id } }" class="btn btn--kecil">Ubah</RouterLink>
              <button
                type="button"
                class="btn btn--kecil btn--bahaya"
                :disabled="idDiproses === tugas.id"
                @click="hapus(tugas)"
              >
                Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
