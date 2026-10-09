<script setup>
// Hanya dimuat saat `npm run dev` (lihat LoginView.vue); tidak ikut ke build produksi.
const emit = defineEmits(['pilih'])
const akun = [
  ['Administrator', 'admin'],
  ['Ketua RT', 'ketua.rt'],
  ['Sekretaris', 'sekretaris'],
  ['Operator', 'operator'],
  ['Bendahara', 'bendahara'],
]
</script>

<template>
  <div class="demo">
    <b>Akun contoh (hanya mode pengembangan)</b> — kata sandi semua akun: <code>password</code>
    <div class="row row-wrap" style="margin-top:8px">
      <button v-for="a in akun" :key="a[1]" type="button" class="chip" @click="emit('pilih', a[1])">{{ a[0] }}</button>
    </div>
  </div>
</template>

<style scoped>
.demo { background: var(--surface-2); border: 1px dashed var(--line); border-radius: 12px; padding: 12px 14px; font-size: 12.5px; color: var(--muted); }
.demo code { background: var(--primary-soft); color: var(--primary-strong); padding: 1px 6px; border-radius: 6px; }
.chip { border: 1px solid var(--line); background: var(--surface); border-radius: 99px; padding: 4px 12px; font-size: 12.5px; font-weight: 600; }
.chip:hover { border-color: var(--primary); color: var(--primary); }
</style>
