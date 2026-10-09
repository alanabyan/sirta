<script setup>
import { computed } from 'vue'

const props = defineProps({ status: String, tone: String })

const TONES = {
  Aktif: 'ok', Selesai: 'ok', Diterbitkan: 'ok', Diverifikasi: 'ok',
  Baru: 'info', Draft: 'neutral',
  Diproses: 'warn', 'Menunggu Verifikasi': 'warn',
  Ditolak: 'bad', Meninggal: 'bad', Nonaktif: 'bad',
  Diarsipkan: 'violet', Pindah: 'neutral',
}
const tone = computed(() => props.tone || TONES[props.status] || 'neutral')
</script>

<template>
  <span class="badge" :class="`tone-${tone}`"><slot>{{ status }}</slot></span>
</template>
