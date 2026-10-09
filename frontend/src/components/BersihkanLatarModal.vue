<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { bersihkanLatar, kanvasDari, keFilePng, muatGambar, sarankanMode } from '@/gambar'
import { useUi } from '@/stores/ui'
import BaseModal from './BaseModal.vue'

const props = defineProps({ src: String, label: String })
const emit = defineEmits(['close', 'selesai'])
const ui = useUi()

const mode = ref('kertas')
const sens = ref(0.5)
const potongOtomatis = ref(true)
const loading = ref(true)
const sibuk = ref(false)
const tampil = ref(null)
let sumber = null // kanvas kerja (maks. 900 px)
let hasil = null

function gambarUlang() {
  if (!sumber || !tampil.value) return
  hasil = bersihkanLatar(sumber, { mode: mode.value, sens: sens.value, potongOtomatis: potongOtomatis.value })
  const c = tampil.value
  c.width = hasil.width
  c.height = hasil.height
  const x = c.getContext('2d')
  x.clearRect(0, 0, c.width, c.height)
  x.drawImage(hasil, 0, 0)
}

let timer
watch([mode, sens, potongOtomatis], () => {
  clearTimeout(timer)
  timer = setTimeout(gambarUlang, 120)
})
watch(mode, (m) => (sens.value = m === 'kertas' ? 0.5 : 0.35))

onMounted(async () => {
  try {
    sumber = kanvasDari(await muatGambar(props.src))
    mode.value = sarankanMode(sumber)
    sens.value = mode.value === 'kertas' ? 0.5 : 0.35
    loading.value = false
    await nextTick() // tunggu kanvas tampil
    gambarUlang()
  } catch (e) {
    ui.error(e.message)
    emit('close')
  }
})

async function gunakan() {
  sibuk.value = true
  try {
    emit('selesai', await keFilePng(hasil, `${props.label || 'gambar'}-bersih.png`.toLowerCase().replace(/\s+/g, '-')))
  } catch (e) {
    ui.error(e.message)
  } finally {
    sibuk.value = false
  }
}
</script>

<template>
  <BaseModal :title="`Bersihkan latar — ${label}`" subtitle="Buang latar foto sehingga hanya tinta yang tersisa. Atur kepekaan sampai hasilnya rapi." size="lg" @close="emit('close')" @save="gunakan">
    <div v-if="loading" class="loading"><div class="spinner" /></div>
    <div v-else class="wrap">
      <div class="atur">
        <div class="field">
          <label>Jenis latar</label>
          <div class="seg">
            <button type="button" :class="{ on: mode === 'kertas' }" @click="mode = 'kertas'"><b>Kertas terang</b><small>Foto di kertas putih (terbaik untuk stempel &amp; tanda tangan)</small></button>
            <button type="button" :class="{ on: mode === 'warna' }" @click="mode = 'warna'"><b>Warna lain</b><small>Latar berwarna / bertekstur</small></button>
          </div>
        </div>
        <div class="field">
          <label>Kepekaan <span class="muted">({{ Math.round(sens * 100) }}%)</span></label>
          <input v-model.number="sens" type="range" min="0" max="1" step="0.01">
          <span class="hint">{{ mode === 'kertas' ? 'Geser ke kanan bila tinta tipis ikut hilang; ke kiri bila masih ada bintik latar.' : 'Geser ke kanan bila latar belum terbuang; ke kiri bila bagian gambar ikut terhapus.' }}</span>
        </div>
        <label class="cek"><input v-model="potongOtomatis" type="checkbox"> Potong otomatis di sekitar gambar</label>
      </div>

      <div class="pratinjau">
        <div class="kotak asli"><span>Asli</span><img :src="src" alt="Gambar asli"></div>
        <div class="kotak hasil"><span>Hasil</span><canvas ref="tampil" /></div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-secondary" @click="emit('close')">Batal</button>
      <button type="button" class="btn btn-primary" :disabled="loading || sibuk" @click="gunakan">{{ sibuk ? 'Memproses…' : 'Gunakan hasil ini' }}</button>
    </template>
  </BaseModal>
</template>

<style scoped>
.wrap { display: flex; flex-direction: column; gap: 18px; }
.atur { display: grid; grid-template-columns: 1.3fr 1fr; gap: 18px; align-items: start; }
.seg { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.seg button { text-align: left; border: 1.5px solid var(--line); background: var(--surface); border-radius: 12px; padding: 10px 12px; display: flex; flex-direction: column; }
.seg button.on { border-color: var(--primary); background: var(--primary-soft); }
.seg b { font-size: 13.5px; }
.seg small { color: var(--muted); font-size: 12px; }
input[type=range] { width: 100%; accent-color: var(--primary); }
.cek { grid-column: 1 / -1; display: flex; gap: 8px; align-items: center; font-weight: 600; font-size: 13px; }
.pratinjau { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.kotak { position: relative; height: var(--tinggi); border: 1px solid var(--line); border-radius: 14px; display: grid; place-items: center; overflow: hidden; padding: 10px; }
.kotak span { position: absolute; top: 8px; left: 10px; font-size: 11px; font-weight: 700; background: var(--surface); padding: 2px 8px; border-radius: 99px; z-index: 1; }
.kotak.asli { background: var(--surface-2); }
.kotak.hasil { background: repeating-conic-gradient(#eef1f0 0% 25%, #fff 0% 50%) 0 0 / 16px 16px; }
.kotak { --tinggi: 280px; }
.kotak img, .kotak canvas { max-width: 100%; max-height: calc(var(--tinggi) - 24px); width: auto; height: auto; object-fit: contain; }
@media (max-width: 760px) { .atur, .pratinjau { grid-template-columns: 1fr; } .kotak { --tinggi: 220px; } }
</style>
