<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Home, Search, ArrowLeft } from 'lucide-vue-next'
import api, { errorMessage } from '@/api'
import TrackingTimeline from '@/components/TrackingTimeline.vue'

const route = useRoute()
const router = useRouter()
const kode = ref(route.params.kode || '')
const nik4 = ref('')
const data = ref(null)
const error = ref('')
const loading = ref(false)

async function cari() {
  const k = kode.value.trim().toUpperCase()
  if (!k || !/^\d{4}$/.test(nik4.value)) return
  loading.value = true
  error.value = ''
  data.value = null
  try {
    data.value = (await api.post('/tracking', { kode: k, nik4: nik4.value })).data.data
    router.replace({ name: 'lacak', params: { kode: k } })
  } catch (e) {
    error.value = e.response?.status === 404 ? 'Kode atau 4 digit terakhir NIK tidak cocok. Periksa kembali bukti pengajuan Anda.' : errorMessage(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="pub">
    <header>
      <RouterLink to="/masuk" class="brand"><span class="logo"><Home :size="18" /></span><b>SIRTA</b><span class="muted small">RT 03 / RW 20</span></RouterLink>
      <div class="row"><RouterLink to="/ajukan" class="btn btn-soft btn-sm">Ajukan surat</RouterLink><RouterLink to="/masuk" class="btn btn-secondary btn-sm"><ArrowLeft :size="15" /> Pengurus</RouterLink></div>
    </header>

    <main>
      <h1>Lacak permohonan Anda</h1>
      <p class="muted">Masukkan kode pengajuan (contoh: <b>PL-2026-0042</b>) dan 4 digit terakhir NIK pemohon untuk melihat sampai mana prosesnya.</p>

      <form class="card card-pad search-box" @submit.prevent="cari">
        <div class="search">
          <Search :size="18" />
          <input v-model="kode" class="input" placeholder="PL-2026-0042" aria-label="Kode pengajuan" autofocus>
        </div>
        <input v-model="nik4" class="input nik4" inputmode="numeric" maxlength="4" placeholder="4 digit NIK" aria-label="4 digit terakhir NIK" @input="nik4 = nik4.replace(/\D/g, '')">
        <button class="btn btn-primary" :disabled="loading || !kode.trim() || nik4.length !== 4">{{ loading ? 'Mencari…' : 'Lacak' }}</button>
      </form>

      <div v-if="error" class="callout tone-bad" style="margin-top:16px" role="alert">{{ error }}</div>
      <div v-if="data" class="card card-pad" style="margin-top:16px"><TrackingTimeline :data="data" /></div>
    </main>
  </div>
</template>

<style scoped>
.pub { min-height: 100vh; }
header { display: flex; justify-content: space-between; align-items: center; padding: 14px 28px; background: var(--surface); border-bottom: 1px solid var(--line); }
.brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none !important; }
.logo { width: 34px; height: 34px; border-radius: 10px; background: var(--primary); color: #fff; display: grid; place-items: center; }
main { max-width: 640px; margin: 0 auto; padding: 48px 20px; }
h1 { font-size: 28px; margin-bottom: 6px; letter-spacing: -.01em; }
.search-box { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
.nik4 { width: 130px; flex: none; }
</style>
