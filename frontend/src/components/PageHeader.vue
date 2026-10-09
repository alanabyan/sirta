<script setup>
defineProps({ title: String, description: String })
</script>

<template>
  <div class="ph">
    <div class="grow">
      <h2>{{ title }}</h2>
      <p v-if="description">{{ description }}</p>
    </div>
    <div class="row row-wrap"><slot /></div>
  </div>
</template>

<style scoped>
.ph { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
h2 { font-size: 24px; font-weight: 750; letter-spacing: -.01em; }
p { color: var(--muted); margin-top: 4px; max-width: 640px; }
</style>
