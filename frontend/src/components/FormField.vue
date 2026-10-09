<script setup>
defineProps({ label: String, error: String, hint: String, required: Boolean, full: Boolean })
</script>

<template>
  <div class="field" :class="{ invalid: error, full }">
    <label>{{ label }} <span v-if="required" class="req" aria-hidden="true">*</span></label>
    <slot />
    <span v-if="error" class="err" role="alert">{{ error }}</span>
    <span v-else-if="hint" class="hint">{{ hint }}</span>
  </div>
</template>
