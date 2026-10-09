<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
defineProps({ page: Number, lastPage: Number, total: Number, from: Number, to: Number })
const emit = defineEmits(['change'])
</script>

<template>
  <div v-if="total" class="pagination">
    <span class="muted small">Menampilkan {{ from }}–{{ to }} dari {{ total }} data</span>
    <div v-if="lastPage > 1" class="row">
      <button class="btn btn-secondary btn-sm" :disabled="page <= 1" @click="emit('change', page - 1)">
        <ChevronLeft :size="16" /> Sebelumnya
      </button>
      <span class="small muted">Hal. {{ page }} / {{ lastPage }}</span>
      <button class="btn btn-secondary btn-sm" :disabled="page >= lastPage" @click="emit('change', page + 1)">
        Berikutnya <ChevronRight :size="16" />
      </button>
    </div>
  </div>
</template>
