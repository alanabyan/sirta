<script setup>
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next'
import { useUi } from '@/stores/ui'
const ui = useUi()
</script>

<template>
  <div class="toasts no-print" aria-live="polite">
    <TransitionGroup name="toast">
      <div v-for="t in ui.toasts" :key="t.id" class="toast" :class="t.type" role="status">
        <component :is="t.type === 'error' ? AlertCircle : CheckCircle2" :size="20" />
        <span class="grow">{{ t.message }}</span>
        <button class="close" aria-label="Tutup" @click="ui.dismiss(t.id)"><X :size="16" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toasts { position: fixed; right: 20px; bottom: 20px; z-index: 300; display: flex; flex-direction: column; gap: 10px; width: min(380px, calc(100vw - 40px)); }
.toast { display: flex; align-items: flex-start; gap: 10px; padding: 13px 14px; border-radius: 12px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-lg); font-weight: 550; font-size: 13.5px; }
.toast.success svg:first-child { color: var(--ok); }
.toast.error { border-color: var(--bad); }
.toast.error svg:first-child { color: var(--bad); }
.close { border: 0; background: transparent; color: var(--faint); padding: 2px; display: grid; }
.toast-enter-active, .toast-leave-active { transition: all .25s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>
