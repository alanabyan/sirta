<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Plus, Search, Pencil, Trash2, UsersRound, Eye } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const auth = useAuth()
const ui = useUi()
const route = useRoute()
const list = useList('/keluarga', { filters: { status_rumah: '' } })
const { saving, errors, save } = useSave('/keluarga')

const kosong = () => ({ id: null, no_kk: '', kepala_keluarga: '', alamat: '', status_rumah: 'Milik sendiri' })
const form = reactive(kosong())
const showForm = ref(false)
const detail = ref(null)

function buka(k) {
  Object.assign(form, kosong(), k || {})
  errors.value = {}
  showForm.value = true
}
async function simpan() {
  const { id, ...payload } = form
  if (await save(id, payload)) {
    showForm.value = false
    list.load()
  }
}
async function lihat(k) {
  try {
    detail.value = (await api.get(`/keluarga/${k.id}`)).data.data
  } catch (e) {
    ui.error(errorMessage(e))
  }
}
const hapus = (k) =>
  list.remove(k, { title: `Hapus KK ${k.kepala_keluarga}?`, message: 'Anggota keluarga tidak ikut terhapus, hanya terlepas dari KK ini.' })

onMounted(() => route.query.baru && auth.canWrite && buka())
</script>

<template>
  <PageHeader title="Data Keluarga" description="Setiap keluarga (KK) dapat memiliki beberapa anggota. Hubungkan warga ke KK lewat menu Data Warga.">
    <button v-if="auth.canWrite" class="btn btn-primary" @click="buka()"><Plus :size="18" /> Tambah keluarga</button>
  </PageHeader>

  <div class="card">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari No. KK, kepala keluarga, atau alamat…" aria-label="Cari keluarga"></div>
      <select v-model="list.filter.status_rumah" class="select" aria-label="Status rumah">
        <option value="">Semua status rumah</option><option>Milik sendiri</option><option>Kontrak</option><option>Rumah dinas</option>
      </select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="UsersRound" title="Keluarga tidak ditemukan" text="Tambahkan KK baru atau ubah kata kunci pencarian." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Kepala keluarga</th><th>No. KK</th><th>Alamat</th><th>Anggota</th><th>Status rumah</th><th /></tr></thead>
        <tbody>
          <tr v-for="k in list.items.value" :key="k.id">
            <td class="cell-title">{{ k.kepala_keluarga }}</td>
            <td class="mono">{{ k.no_kk }}</td>
            <td>{{ k.alamat }}</td>
            <td>{{ k.anggota_count }} orang</td>
            <td><StatusBadge :status="k.status_rumah" tone="primary" class="plain" /></td>
            <td class="actions">
              <button class="btn btn-icon" title="Lihat anggota" :aria-label="`Lihat anggota ${k.kepala_keluarga}`" @click="lihat(k)"><Eye :size="17" /></button>
              <template v-if="auth.canWrite">
                <button class="btn btn-icon" title="Ubah" @click="buka(k)"><Pencil :size="17" /></button>
                <button v-if="auth.canDecide" class="btn btn-icon" title="Hapus" @click="hapus(k)"><Trash2 :size="17" /></button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <BaseModal v-if="showForm" :title="form.id ? 'Ubah data keluarga' : 'Tambah keluarga'" :saving="saving" @close="showForm = false" @save="simpan">
    <div class="form-grid">
      <FormField label="No. KK" :error="errors.no_kk" hint="16 digit" required><input v-model="form.no_kk" class="input mono" maxlength="16" inputmode="numeric" required></FormField>
      <FormField label="Kepala keluarga" :error="errors.kepala_keluarga" required><input v-model="form.kepala_keluarga" class="input" required></FormField>
      <FormField label="Alamat" :error="errors.alamat" full required><input v-model="form.alamat" class="input" placeholder="mis. Blok C1 No. 12" required></FormField>
      <FormField label="Status rumah" :error="errors.status_rumah" required>
        <select v-model="form.status_rumah" class="select"><option>Milik sendiri</option><option>Kontrak</option><option>Rumah dinas</option></select>
      </FormField>
    </div>
  </BaseModal>

  <BaseModal v-if="detail" :title="`Keluarga ${detail.kepala_keluarga}`" :subtitle="`KK ${detail.no_kk} · ${detail.alamat}`" hide-footer @close="detail = null">
    <div v-if="!detail.anggota.length" class="empty" style="padding:20px">Belum ada anggota yang dihubungkan ke KK ini.</div>
    <table v-else class="table">
      <thead><tr><th>Nama</th><th>Hubungan</th><th>Jenis kelamin</th></tr></thead>
      <tbody>
        <tr v-for="a in detail.anggota" :key="a.id"><td class="cell-title">{{ a.nama }}</td><td>{{ a.hubungan_keluarga || '—' }}</td><td>{{ a.jenis_kelamin }}</td></tr>
      </tbody>
    </table>
  </BaseModal>
</template>
