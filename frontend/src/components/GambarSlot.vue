<script setup>
import { ref } from 'vue'
import { Upload, Trash2, PenLine, ImageOff } from 'lucide-vue-next'

defineProps({
  label: String,
  hint: String,
  src: String, // URL pratinjau (gambar tersimpan atau pilihan baru)
  bisaGambar: Boolean,
})
const emit = defineEmits(['pilih', 'hapus', 'gambar'])
const input = ref(null)
const drag = ref(false)

function pilih(f) {
  if (f) emit('pilih', f)
  if (input.value) input.value.value = ''
}
</script>

<template>
  <div class="slot">
    <div class="head"><b>{{ label }}</b><small class="muted">{{ hint }}</small></div>
    <div class="area" :class="{ drag }" @dragover.prevent="drag = true" @dragleave="drag = false" @drop.prevent="drag = false; pilih($event.dataTransfer.files[0])">
      <img v-if="src" :src="src" :alt="label">
      <div v-else class="kosong"><ImageOff :size="28" /><span>Belum ada gambar</span></div>
    </div>
    <div class="row row-wrap">
      <label class="btn btn-secondary btn-sm">
        <Upload :size="15" /> {{ src ? 'Ganti' : 'Unggah' }}
        <input ref="input" type="file" class="sr-only" accept=".png,.jpg,.jpeg,image/png,image/jpeg" @change="pilih($event.target.files[0])">
      </label>
      <button v-if="bisaGambar" type="button" class="btn btn-soft btn-sm" @click="emit('gambar')"><PenLine :size="15" /> Gambar langsung</button>
      <button v-if="src" type="button" class="btn btn-danger btn-sm" @click="emit('hapus')"><Trash2 :size="15" /> Hapus</button>
    </div>
  </div>
</template>

<style scoped>
.slot { display: flex; flex-direction: column; gap: 10px; }
.head { display: flex; flex-direction: column; }
.area { height: 150px; border: 2px dashed var(--line); border-radius: 14px; display: grid; place-items: center; background:
  repeating-conic-gradient(#f1f4f3 0% 25%, #fff 0% 50%) 0 0 / 16px 16px; overflow: hidden; }
:root[data-theme='dark'] .area { background: repeating-conic-gradient(#1b2b29 0% 25%, #223432 0% 50%) 0 0 / 16px 16px; }
.area.drag { border-color: var(--primary); }
.area img { max-width: 100%; max-height: 100%; object-fit: contain; }
.kosong { display: flex; flex-direction: column; align-items: center; gap: 6px; color: var(--faint); font-size: 13px; background: var(--surface); padding: 10px 16px; border-radius: 10px; }
</style>
