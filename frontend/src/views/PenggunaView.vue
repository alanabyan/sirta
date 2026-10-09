<script setup>
import { onMounted, reactive, ref } from 'vue'
import { Plus, Search, Pencil, Trash2, ShieldCheck, Info } from 'lucide-vue-next'
import api from '@/api'
import { useAuth } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { inisial, waktuRelatif, formatWaktu } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const ROLES = [
  ['administrator', 'Administrator', 'Akses penuh, termasuk mengelola pengguna.'],
  ['ketua_rt', 'Ketua RT', 'Verifikasi, hapus data, kelola pengurus & template.'],
  ['sekretaris', 'Sekretaris', 'Verifikasi, hapus data, kelola pengurus & template.'],
  ['operator', 'Operator', 'Menambah & mengubah data, tidak dapat memverifikasi atau menghapus.'],
  ['bendahara', 'Bendahara', 'Hanya dapat melihat data.'],
]
const auth = useAuth()
const list = useList('/users', { filters: { role: '' } })
const { saving, errors, save } = useSave('/users')

// Jejak audit pembukaan NIK lengkap.
const log = reactive({ items: [], page: 1, lastPage: 1, total: 0, from: 0, to: 0, loading: true })
async function loadLog(page = 1) {
  log.loading = true
  try {
    const { data } = await api.get('/log-nik', { params: { page } })
    Object.assign(log, { items: data.data, page: data.current_page, lastPage: data.last_page, total: data.total, from: data.from ?? 0, to: data.to ?? 0 })
  } finally {
    log.loading = false
  }
}
onMounted(loadLog)

const kosong = () => ({ id: null, name: '', username: '', password: '', role: 'operator', is_active: true })
const form = reactive(kosong())
const showForm = ref(false)

function buka(u) {
  Object.assign(form, kosong(), u ? { ...u, password: '' } : {})
  errors.value = {}
  showForm.value = true
}
async function simpan() {
  const { id, ...payload } = form
  if (!payload.password) delete payload.password
  if (await save(id, payload)) {
    showForm.value = false
    list.load()
  }
}
const hapus = (u) => list.remove(u, { title: `Hapus akun ${u.name}?`, message: 'Akun tidak dapat masuk lagi. Riwayat aktivitasnya tetap tersimpan.' })
</script>

<template>
  <PageHeader title="Pengguna Sistem" description="Atur siapa yang boleh masuk ke SIRTA dan apa saja yang boleh dilakukannya.">
    <button class="btn btn-primary" @click="buka()"><Plus :size="18" /> Tambah pengguna</button>
  </PageHeader>

  <div class="card card-pad roles">
    <h3><ShieldCheck :size="18" /> Hak akses per peran</h3>
    <ul>
      <li v-for="r in ROLES" :key="r[0]"><b>{{ r[1] }}</b><span>{{ r[2] }}</span></li>
    </ul>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari nama atau username…" aria-label="Cari pengguna"></div>
      <select v-model="list.filter.role" class="select" aria-label="Peran"><option value="">Semua peran</option><option v-for="r in ROLES" :key="r[0]" :value="r[0]">{{ r[1] }}</option></select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" title="Pengguna tidak ditemukan" />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Pengguna</th><th>Username</th><th>Peran</th><th>Status</th><th>Masuk terakhir</th><th /></tr></thead>
        <tbody>
          <tr v-for="u in list.items.value" :key="u.id">
            <td><div class="row"><span class="avatar">{{ inisial(u.name) }}</span><b>{{ u.name }}</b><span v-if="u.id === auth.user.id" class="badge plain tone-primary">Anda</span></div></td>
            <td class="mono">{{ u.username }}</td>
            <td>{{ u.role_label }}</td>
            <td><StatusBadge :status="u.is_active ? 'Aktif' : 'Nonaktif'" /></td>
            <td class="muted">{{ u.last_login_at ? waktuRelatif(u.last_login_at) : 'Belum pernah' }}</td>
            <td class="actions">
              <button class="btn btn-icon" title="Ubah" :aria-label="`Ubah ${u.name}`" @click="buka(u)"><Pencil :size="17" /></button>
              <button v-if="u.id !== auth.user.id" class="btn btn-icon" title="Hapus" :aria-label="`Hapus ${u.name}`" @click="hapus(u)"><Trash2 :size="17" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-head" style="padding-bottom:12px">
      <div><h3>Log akses NIK</h3><p>Setiap kali NIK lengkap warga dibuka (untuk mengubah data atau menyusun surat) tercatat di sini.</p></div>
    </div>
    <div v-if="log.loading && !log.items.length" class="loading"><div class="spinner" /></div>
    <EmptyState v-else-if="!log.items.length" title="Belum ada akses NIK" text="Catatan akan muncul saat ada yang membuka NIK lengkap." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Warga</th><th>Keperluan</th></tr></thead>
        <tbody>
          <tr v-for="l in log.items" :key="l.id">
            <td>{{ formatWaktu(l.created_at) }}</td>
            <td><b>{{ l.user?.name || '(dihapus)' }}</b><div class="cell-sub">{{ l.user?.role_label }}</div></td>
            <td>{{ l.warga?.nama }}</td>
            <td><span class="badge plain tone-neutral">{{ l.keperluan === 'ubah' ? 'Mengubah data warga' : 'Menyusun surat' }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="log" @change="loadLog" />
  </div>

  <BaseModal v-if="showForm" :title="form.id ? 'Ubah pengguna' : 'Tambah pengguna'" :saving="saving" @close="showForm = false" @save="simpan">
    <div class="form-grid">
      <FormField label="Nama lengkap" :error="errors.name" required><input v-model="form.name" class="input" required></FormField>
      <FormField label="Username" :error="errors.username" hint="Huruf, angka, titik, strip" required><input v-model="form.username" class="input" autocomplete="off" required></FormField>
      <FormField :label="form.id ? 'Kata sandi baru' : 'Kata sandi'" :error="errors.password" :hint="form.id ? 'Kosongkan bila tidak diubah' : 'Minimal 8 karakter'" :required="!form.id">
        <input v-model="form.password" type="password" class="input" autocomplete="new-password" minlength="8" :required="!form.id">
      </FormField>
      <FormField label="Peran" :error="errors.role" required>
        <select v-model="form.role" class="select" :disabled="form.id === auth.user.id"><option v-for="r in ROLES" :key="r[0]" :value="r[0]">{{ r[1] }}</option></select>
      </FormField>
      <label class="full switch"><input v-model="form.is_active" type="checkbox" :disabled="form.id === auth.user.id"> Akun aktif (dapat masuk ke sistem)</label>
    </div>
    <div class="callout tone-info" style="margin-top:14px"><Info :size="18" /><span>{{ ROLES.find((r) => r[0] === form.role)?.[2] }}</span></div>
  </BaseModal>
</template>

<style scoped>
.roles h3 { font-size: 15px; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.roles ul { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
.roles li { background: var(--surface-2); border-radius: 10px; padding: 10px 12px; font-size: 12.5px; color: var(--muted); }
.roles li b { display: block; color: var(--text); font-size: 13.5px; }
.switch { display: flex; align-items: center; gap: 10px; font-weight: 600; }
.switch input { width: 18px; height: 18px; accent-color: var(--primary); }
</style>
