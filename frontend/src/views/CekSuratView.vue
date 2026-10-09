<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Home, BadgeCheck, ShieldAlert, Search } from 'lucide-vue-next'
import api from '@/api'
import { formatTanggal, formatWaktu } from '@/utils'

const route = useRoute()
const kode = ref(route.params.kode || '')
const surat = ref(null)
const galat = ref('')
const loading = ref(false)

async function periksa() {
  const k = kode.value.trim().toUpperCase()
  if (!k) return
  loading.value = true
  surat.value = null
  galat.value = ''
  try {
    surat.value = (await api.get(`/cek-surat/${encodeURIComponent(k)}`)).data.data
  } catch (e) {
    galat.value = e.response?.status === 404 ? e.response.data.message : 'Tidak dapat memeriksa saat ini. Coba lagi nanti.'
  } finally {
    loading.value = false
  }
}
onMounted(() => kode.value && periksa())
</script>

<template>
  <div class="pub">
    <header>
      <RouterLink to="/masuk" class="brand"><span class="logo"><Home :size="18" /></span><b>SIRTA</b><span class="muted small">Pemeriksa keaslian surat</span></RouterLink>
    </header>

    <main>
      <div v-if="loading" class="loading"><div class="spinner" /></div>

      <div v-else-if="surat" class="card card-pad hasil sah">
        <BadgeCheck :size="52" />
        <h1>Surat ini asli</h1>
        <p class="muted">Tercatat resmi diterbitkan oleh RT 03 / RW 20, Perum Griya Kreasi Aqilla.</p>
        <dl class="kv">
          <dt>Nomor surat</dt><dd>{{ surat.nomor }}</dd>
          <dt>Tanggal surat</dt><dd>{{ formatTanggal(surat.tanggal) }}</dd>
          <dt>Perihal</dt><dd>{{ surat.perihal }}</dd>
          <dt>Ditujukan kepada</dt><dd>{{ surat.tujuan }}</dd>
          <dt>Penandatangan</dt><dd>{{ surat.penandatangan }} — {{ surat.jabatan }}</dd>
          <dt>Diterbitkan</dt><dd>{{ formatWaktu(surat.diterbitkan_at) }}</dd>
        </dl>
        <p class="small muted">Cocokkan nomor, tanggal, dan perihal di atas dengan surat yang Anda pegang. Jika berbeda, surat tersebut tidak asli.</p>
      </div>

      <div v-else-if="galat" class="card card-pad hasil palsu">
        <ShieldAlert :size="52" />
        <h1>Tidak dapat dipastikan asli</h1>
        <p>{{ galat }}</p>
        <p class="small muted">Periksa kembali kode pada surat, atau hubungi pengurus RT.</p>
      </div>

      <div v-if="!surat" class="card card-pad" style="margin-top:16px">
        <h3 style="font-size:15px">Periksa dengan kode</h3>
        <p class="muted small">Pindai kode QR pada surat, atau ketik kode yang tercetak di bawahnya.</p>
        <form class="row" style="margin-top:12px" @submit.prevent="periksa">
          <div class="search"><Search :size="18" /><input v-model="kode" class="input mono" placeholder="mis. K4QOWG2RL2" aria-label="Kode surat" maxlength="16"></div>
          <button class="btn btn-primary" :disabled="loading || !kode.trim()">Periksa</button>
        </form>
      </div>
    </main>
  </div>
</template>

<style scoped>
.pub { min-height: 100vh; }
header { display: flex; justify-content: space-between; align-items: center; padding: 14px 28px; background: var(--surface); border-bottom: 1px solid var(--line); }
.brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none !important; }
.logo { width: 34px; height: 34px; border-radius: 10px; background: var(--primary); color: #fff; display: grid; place-items: center; }
main { max-width: 560px; margin: 0 auto; padding: 40px 20px 60px; }
h1 { font-size: 26px; letter-spacing: -.01em; }
.hasil { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 32px 24px; }
.hasil .kv { text-align: left; width: 100%; background: var(--surface-2); border-radius: 12px; padding: 16px; margin: 8px 0; }
.sah { border-color: var(--ok); } .sah > svg { color: var(--ok); }
.palsu { border-color: var(--bad); } .palsu > svg { color: var(--bad); }
@media (max-width: 480px) { header { padding: 12px 16px; } .brand .muted { display: none; } }
</style>
