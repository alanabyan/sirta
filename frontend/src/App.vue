<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useUi } from '@/stores/ui'
import ToastHost from '@/components/ToastHost.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const ui = useUi()

// Beri label tiap sel tabel dari judul kolomnya, agar tabel dapat tampil sebagai kartu di layar kecil.
function labeli(root = document) {
  root.querySelectorAll('table.table').forEach((t) => {
    const judul = [...t.querySelectorAll('thead th')].map((th) => th.textContent.trim())
    t.querySelectorAll('tbody tr').forEach((tr) => {
      ;[...tr.children].forEach((td, i) => {
        if (td.dataset.label !== (judul[i] ?? '')) td.dataset.label = judul[i] ?? ''
      })
    })
  })
}
let pengamat
let tertunda = false
onMounted(() => {
  ui.applyTheme()
  labeli()
  pengamat = new MutationObserver(() => {
    if (tertunda) return
    tertunda = true
    requestAnimationFrame(() => { tertunda = false; labeli() })
  })
  pengamat.observe(document.body, { childList: true, subtree: true })
})
onUnmounted(() => pengamat?.disconnect())
</script>

<template>
  <RouterView />
  <ToastHost />
  <ConfirmDialog />
</template>
