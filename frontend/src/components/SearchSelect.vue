<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { ChevronDown, Search, Check } from 'lucide-vue-next'

/** Dropdown yang bisa diketik. options: [{ value, label, sub? }] */
const props = defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih…' },
  searchPlaceholder: { type: String, default: 'Ketik untuk mencari…' },
  emptyText: { type: String, default: 'Tidak ada hasil' },
  clearable: Boolean,
  invalid: Boolean,
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const q = ref('')
const aktif = ref(0)
const root = ref(null)
const input = ref(null)
const listEl = ref(null)

const terpilih = computed(() => props.options.find((o) => o.value === props.modelValue))
const hasil = computed(() => {
  const k = q.value.trim().toLowerCase()
  return k ? props.options.filter((o) => `${o.label} ${o.sub ?? ''}`.toLowerCase().includes(k)) : props.options
})

watch(hasil, () => (aktif.value = 0))

async function buka() {
  open.value = true
  q.value = ''
  aktif.value = Math.max(0, props.options.findIndex((o) => o.value === props.modelValue))
  await nextTick()
  input.value?.focus()
  listEl.value?.children[aktif.value]?.scrollIntoView({ block: 'nearest' })
}
function tutup() {
  open.value = false
}
function pilih(o) {
  emit('update:modelValue', o.value)
  tutup()
  root.value?.querySelector('.trigger')?.focus()
}
function geser(d) {
  if (!hasil.value.length) return
  aktif.value = (aktif.value + d + hasil.value.length) % hasil.value.length
  nextTick(() => listEl.value?.children[aktif.value]?.scrollIntoView({ block: 'nearest' }))
}
function onKey(e) {
  if (e.key === 'ArrowDown') { e.preventDefault(); geser(1) }
  else if (e.key === 'ArrowUp') { e.preventDefault(); geser(-1) }
  else if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); hasil.value[aktif.value] && pilih(hasil.value[aktif.value]) }
  else if (e.key === 'Escape') { e.stopPropagation(); tutup() }
  else if (e.key === 'Tab') tutup()
}
function onFocusOut(e) {
  if (!root.value?.contains(e.relatedTarget)) tutup()
}
</script>

<template>
  <div ref="root" class="ss" @focusout="onFocusOut">
    <button type="button" class="trigger select" :class="{ invalid }" :aria-expanded="open" aria-haspopup="listbox" @click="open ? tutup() : buka()" @keydown.down.prevent="buka" @keydown.enter.prevent="open ? tutup() : buka()">
      <span v-if="terpilih" class="truncate">{{ terpilih.label }}</span>
      <span v-else class="ph truncate">{{ placeholder }}</span>
      <ChevronDown :size="16" class="chev" />
    </button>

    <div v-if="open" class="pop" @keydown="onKey">
      <div class="find">
        <Search :size="15" />
        <input ref="input" v-model="q" :placeholder="searchPlaceholder" autocomplete="off" role="combobox" :aria-expanded="true" aria-controls="ss-list">
      </div>
      <ul id="ss-list" ref="listEl" role="listbox">
        <li v-if="clearable && modelValue !== '' && !q" class="opt clear" tabindex="-1" @mousedown.prevent="emit('update:modelValue', ''); tutup()">— Kosongkan pilihan —</li>
        <li v-for="(o, i) in hasil" :key="o.value" role="option" :aria-selected="o.value === modelValue" class="opt" :class="{ on: i === aktif, sel: o.value === modelValue }" @mousedown.prevent="pilih(o)" @mousemove="aktif = i">
          <span class="grow"><b>{{ o.label }}</b><small v-if="o.sub">{{ o.sub }}</small></span>
          <Check v-if="o.value === modelValue" :size="16" />
        </li>
        <li v-if="!hasil.length" class="none">{{ emptyText }}</li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.ss { position: relative; }
.trigger { display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; cursor: pointer; appearance: none; }
.trigger.invalid { border-color: var(--bad); }
.ph { color: var(--faint); }
.chev { flex: none; color: var(--muted); }
.pop { position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px); background: var(--surface); border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow-lg); overflow: hidden; }
.find { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-bottom: 1px solid var(--line); color: var(--faint); }
.find input { border: 0; outline: 0; background: transparent; flex: 1; font-size: 14px; min-width: 0; }
ul { list-style: none; margin: 0; padding: 4px; max-height: 240px; overflow-y: auto; }
.opt { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; cursor: pointer; }
.opt b { font-weight: 600; font-size: 13.5px; display: block; }
.opt small { color: var(--muted); font-size: 12px; display: block; }
.opt.on { background: var(--surface-2); }
.opt.sel { color: var(--primary); }
.opt.clear { color: var(--muted); font-size: 13px; }
.none { padding: 14px; text-align: center; color: var(--muted); font-size: 13px; }
</style>
