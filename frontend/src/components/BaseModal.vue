<script>
// Tumpukan jendela terbuka: Esc hanya menutup yang paling atas.
const tumpukan = []
</script>

<script setup>
import { X } from 'lucide-vue-next'
import { onMounted, onUnmounted } from 'vue'

defineProps({
  title: String,
  subtitle: String,
  size: { type: String, default: '' }, // '', 'sm', 'lg'
  saving: Boolean,
  saveText: { type: String, default: 'Simpan' },
  hideFooter: Boolean,
})
const emit = defineEmits(['close', 'save'])

const id = Symbol('modal')
const onKey = (e) => e.key === 'Escape' && tumpukan[tumpukan.length - 1] === id && emit('close')
onMounted(() => {
  tumpukan.push(id)
  document.addEventListener('keydown', onKey)
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  tumpukan.splice(tumpukan.indexOf(id), 1)
  document.removeEventListener('keydown', onKey)
  if (!tumpukan.length) document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div class="overlay" @mousedown.self="emit('close')">
      <form class="modal" :class="size" role="dialog" aria-modal="true" @submit.prevent="emit('save')">
        <div class="modal-head">
          <div>
            <h3>{{ title }}</h3>
            <p v-if="subtitle">{{ subtitle }}</p>
          </div>
          <button type="button" class="btn btn-icon" aria-label="Tutup" @click="emit('close')"><X :size="20" /></button>
        </div>
        <div class="modal-body"><slot /></div>
        <div v-if="!hideFooter" class="modal-foot">
          <slot name="footer">
            <button type="button" class="btn btn-secondary" @click="emit('close')">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner" style="width:16px;height:16px;border-top-color:#fff" />
              {{ saving ? 'Menyimpan…' : saveText }}
            </button>
          </slot>
        </div>
      </form>
    </div>
  </Teleport>
</template>
