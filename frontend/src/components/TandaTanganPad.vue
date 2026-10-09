<script setup>
import { onMounted, ref } from 'vue'
import BaseModal from './BaseModal.vue'

const emit = defineEmits(['close', 'selesai'])
const canvas = ref(null)
const ada = ref(false)
let ctx
let menggambar = false

onMounted(() => {
  const c = canvas.value
  // Resolusi ganda agar goresan tajam di layar retina.
  const rasio = Math.max(window.devicePixelRatio || 1, 2)
  c.width = c.clientWidth * rasio
  c.height = c.clientHeight * rasio
  ctx = c.getContext('2d')
  ctx.scale(rasio, rasio)
  ctx.lineWidth = 2.6
  ctx.lineCap = ctx.lineJoin = 'round'
  ctx.strokeStyle = '#111827'
})

const titik = (e) => {
  const r = canvas.value.getBoundingClientRect()
  return [e.clientX - r.left, e.clientY - r.top]
}
function mulai(e) {
  menggambar = true
  canvas.value.setPointerCapture(e.pointerId)
  const [x, y] = titik(e)
  ctx.beginPath()
  ctx.moveTo(x, y)
  ctx.lineTo(x + 0.01, y + 0.01)
  ctx.stroke()
  ada.value = true
}
function gerak(e) {
  if (!menggambar) return
  const [x, y] = titik(e)
  ctx.lineTo(x, y)
  ctx.stroke()
}
const selesaiGores = () => (menggambar = false)
function bersihkan() {
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
  ada.value = false
}
function simpan() {
  canvas.value.toBlob((b) => emit('selesai', new File([b], 'tanda-tangan.png', { type: 'image/png' })), 'image/png')
}
</script>

<template>
  <BaseModal title="Gambar tanda tangan" subtitle="Tanda tangani di kotak berikut memakai mouse, jari, atau pena layar sentuh." @close="emit('close')" @save="simpan">
    <canvas ref="canvas" class="pad" @pointerdown.prevent="mulai" @pointermove.prevent="gerak" @pointerup="selesaiGores" @pointerleave="selesaiGores" @pointercancel="selesaiGores" />
    <p class="muted small" style="margin-top:8px">Latar dibuat transparan, jadi tanda tangan menyatu rapi dengan surat.</p>
    <template #footer>
      <button type="button" class="btn btn-secondary" @click="bersihkan">Hapus coretan</button>
      <button type="button" class="btn btn-primary" :disabled="!ada" @click="simpan">Gunakan tanda tangan ini</button>
    </template>
  </BaseModal>
</template>

<style scoped>
.pad { width: 100%; height: 220px; border: 2px dashed var(--line); border-radius: 14px; background: #fff; cursor: crosshair; touch-action: none; display: block; }
</style>
