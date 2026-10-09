<script setup>
import { AlertTriangle, HelpCircle } from 'lucide-vue-next'
import { useUi } from '@/stores/ui'
const ui = useUi()
</script>

<template>
  <div v-if="ui.confirm" class="overlay" @click.self="ui.answer(false)" @keydown.esc="ui.answer(false)">
    <div class="modal sm" role="alertdialog" aria-modal="true">
      <div class="body">
        <div class="ic" :class="{ danger: ui.confirm.danger }">
          <component :is="ui.confirm.danger ? AlertTriangle : HelpCircle" :size="24" />
        </div>
        <h3>{{ ui.confirm.title }}</h3>
        <p class="muted">{{ ui.confirm.message }}</p>
      </div>
      <div class="modal-foot">
        <button class="btn btn-secondary" @click="ui.answer(false)">Batal</button>
        <button class="btn" :class="ui.confirm.danger ? 'btn-solid-danger' : 'btn-primary'" autofocus @click="ui.answer(true)">
          {{ ui.confirm.confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overlay { z-index: 250; }
.body { padding: 26px 24px 18px; text-align: center; }
.ic { width: 52px; height: 52px; border-radius: 16px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--primary-soft); color: var(--primary); }
.ic.danger { background: var(--bad-soft); color: var(--bad); }
h3 { font-size: 17px; margin-bottom: 6px; }
</style>
