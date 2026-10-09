<script setup>
import { onMounted } from 'vue'
import { useUi } from '@/stores/ui'
import ToastHost from '@/components/ToastHost.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const ui = useUi()
onMounted(() => ui.applyTheme())
</script>

<template>
  <RouterView />
  <ToastHost />
  <ConfirmDialog />
</template>
