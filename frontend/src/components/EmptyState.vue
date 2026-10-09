<script setup>
import { Inbox } from 'lucide-vue-next'
defineProps({ title: { type: String, default: 'Belum ada data' }, text: String, icon: { default: () => Inbox } })
</script>

<template>
  <div class="empty">
    <div class="ico"><component :is="icon" :size="26" /></div>
    <h4>{{ title }}</h4>
    <p v-if="text">{{ text }}</p>
    <div v-if="$slots.default" style="margin-top:14px"><slot /></div>
  </div>
</template>
