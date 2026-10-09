<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Plus, Search, Pencil, Trash2, Inbox } from 'lucide-vue-next'
import { useAuth } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { useSave } from '@/composables/useSave'
import { formatTanggal, hariIni } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const auth = useAuth()
const route = useRoute()
const list = useList('/surat-masuk', { filters: { status: '' } })
const { saving, errors, save } = useSave('/surat-masuk')

const kosong = () => ({ id: null, nomor: '', tanggal: hariIni(), pengirim: '', perihal: '', status: 'Baru' })
const form = reactive(kosong())
const showForm = ref(false)

function buka(s) {
  Object.assign(form, kosong(), s ? { ...s } : {})
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
const hapus = (s) => list.remove(s, { title: `Hapus surat ${s.nomor}?`, message: 'Catatan surat masuk ini akan dihapus permanen.' })

onMounted(() => route.query.baru && auth.canWrite && buka())
</script>

<template>
  <PageHeader title="Surat Masuk" description="Catat setiap surat yang diterima RT agar mudah dicari dan tidak terlewat.">
    <button v-if="auth.canWrite" class="btn btn-primary" @click="buka()"><Plus :size="18" /> Catat surat masuk</button>
  </PageHeader>

  <div class="card">
    <div class="toolbar">
      <div class="search"><Search :size="18" /><input v-model="list.q.value" class="input" placeholder="Cari nomor, pengirim, atau perihal…" aria-label="Cari surat masuk"></div>
      <select v-model="list.filter.status" class="select" aria-label="Status">
        <option value="">Semua status</option><option>Baru</option><option>Diproses</option><option>Diarsipkan</option>
      </select>
    </div>

    <div v-if="list.loading.value && !list.items.value.length" class="loading"><div class="spinner" /></div>
    <div v-else-if="list.error.value" class="callout tone-bad" style="margin:16px">{{ list.error.value }}</div>
    <EmptyState v-else-if="!list.items.value.length" :icon="Inbox" title="Belum ada surat masuk" text="Surat yang Anda catat akan muncul di sini." />
    <div v-else class="table-wrap">
      <table class="table">
        <thead><tr><th>Nomor surat</th><th>Tanggal</th><th>Pengirim</th><th>Perihal</th><th>Status</th><th v-if="auth.canWrite" /></tr></thead>
        <tbody>
          <tr v-for="s in list.items.value" :key="s.id">
            <td class="cell-title mono">{{ s.nomor }}</td>
            <td>{{ formatTanggal(s.tanggal, true) }}</td>
            <td>{{ s.pengirim }}</td>
            <td>{{ s.perihal }}</td>
            <td><StatusBadge :status="s.status" /></td>
            <td v-if="auth.canWrite" class="actions">
              <button class="btn btn-icon" title="Ubah" :aria-label="`Ubah ${s.nomor}`" @click="buka(s)"><Pencil :size="17" /></button>
              <button v-if="auth.canDecide" class="btn btn-icon" title="Hapus" :aria-label="`Hapus ${s.nomor}`" @click="hapus(s)"><Trash2 :size="17" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-bind="list.meta" @change="list.goto" />
  </div>

  <BaseModal v-if="showForm" :title="form.id ? 'Ubah surat masuk' : 'Catat surat masuk'" :saving="saving" @close="showForm = false" @save="simpan">
    <div class="form-grid">
      <FormField label="Nomor surat" :error="errors.nomor" hint="Sesuai yang tertera pada surat" required><input v-model="form.nomor" class="input" required></FormField>
      <FormField label="Tanggal diterima" :error="errors.tanggal" required><input v-model="form.tanggal" type="date" class="input" required></FormField>
      <FormField label="Pengirim" :error="errors.pengirim" required><input v-model="form.pengirim" class="input" placeholder="mis. Kantor Desa Sukajaya" required></FormField>
      <FormField label="Status" :error="errors.status" hint="Baru → Diproses → Diarsipkan" required>
        <select v-model="form.status" class="select"><option>Baru</option><option>Diproses</option><option>Diarsipkan</option></select>
      </FormField>
      <FormField label="Perihal" :error="errors.perihal" full required><textarea v-model="form.perihal" class="textarea" style="min-height:70px" required /></FormField>
    </div>
  </BaseModal>
</template>
