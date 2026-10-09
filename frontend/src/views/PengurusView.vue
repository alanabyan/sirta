<script setup>
import { onMounted, reactive, ref } from 'vue'
import { Plus, Pencil, Trash2, Phone, Award } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import { useAuth } from '@/stores/auth'
import { useUi } from '@/stores/ui'
import { useSave } from '@/composables/useSave'
import { inisial } from '@/utils'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'

const auth = useAuth()
const ui = useUi()
const { saving, errors, save } = useSave('/pengurus')
const items = ref([])
const loading = ref(true)

async function load() {
  try {
    items.value = (await api.get('/pengurus', { params: { all: 1 } })).data.data
  } catch (e) {
    ui.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

const kosong = () => ({ id: null, nama: '', jabatan: '', periode: '2025–2028', kontak: '' })
const form = reactive(kosong())
const showForm = ref(false)
function buka(p) {
  Object.assign(form, kosong(), p ? { ...p, kontak: p.kontak ?? '' } : {})
  errors.value = {}
  showForm.value = true
}
async function simpan() {
  const { id, ...payload } = form
  if (await save(id, payload)) {
    showForm.value = false
    load()
  }
}
async function hapus(p) {
  if (!(await ui.ask({ title: `Hapus ${p.nama}?`, message: `${p.nama} akan dihapus dari susunan pengurus.`, confirmText: 'Ya, hapus', danger: true }))) return
  try {
    await api.delete(`/pengurus/${p.id}`)
    ui.success('Pengurus dihapus.')
    load()
  } catch (e) {
    ui.error(errorMessage(e))
  }
}
onMounted(load)
</script>

<template>
  <PageHeader title="Pengurus RT" description="Susunan kepengurusan RT periode berjalan.">
    <button v-if="auth.canDecide" class="btn btn-primary" @click="buka()"><Plus :size="18" /> Tambah pengurus</button>
  </PageHeader>

  <div v-if="loading" class="loading"><div class="spinner" /></div>
  <EmptyState v-else-if="!items.length" :icon="Award" title="Belum ada pengurus" text="Tambahkan susunan pengurus RT." />
  <div v-else class="grid grid-3">
    <article v-for="p in items" :key="p.id" class="card card-pad person">
      <div class="row">
        <span class="avatar lg">{{ inisial(p.nama) }}</span>
        <div class="grow">
          <h3>{{ p.nama }}</h3>
          <span class="badge plain tone-primary">{{ p.jabatan }}</span>
        </div>
      </div>
      <hr class="divider">
      <div class="small muted">Periode <b style="color:var(--text)">{{ p.periode }}</b></div>
      <div class="small muted row" style="margin-top:6px"><Phone :size="14" /> {{ p.kontak || 'Belum ada kontak' }}</div>
      <div v-if="auth.canDecide" class="row" style="margin-top:14px">
        <button class="btn btn-secondary btn-sm" @click="buka(p)"><Pencil :size="14" /> Ubah</button>
        <button class="btn btn-danger btn-sm" @click="hapus(p)"><Trash2 :size="14" /> Hapus</button>
      </div>
    </article>
  </div>

  <BaseModal v-if="showForm" :title="form.id ? 'Ubah pengurus' : 'Tambah pengurus'" size="sm" :saving="saving" @close="showForm = false" @save="simpan">
    <div class="stack">
      <FormField label="Nama" :error="errors.nama" required><input v-model="form.nama" class="input" required></FormField>
      <FormField label="Jabatan" :error="errors.jabatan" required><input v-model="form.jabatan" class="input" placeholder="mis. Sekretaris" required></FormField>
      <FormField label="Periode" :error="errors.periode" required><input v-model="form.periode" class="input" required></FormField>
      <FormField label="Kontak (telepon/WA)" :error="errors.kontak"><input v-model="form.kontak" class="input" inputmode="tel"></FormField>
    </div>
  </BaseModal>
</template>

<style scoped>
h3 { font-size: 16px; margin-bottom: 4px; }
</style>
