<script setup>
import { defineAsyncComponent, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Home, Eye, EyeOff, Search, FilePlus2, ShieldCheck, FileCheck2, Users } from 'lucide-vue-next'
import { useAuth } from '@/stores/auth'
import { errorMessage, fieldErrors } from '@/api'
import FormField from '@/components/FormField.vue'

const auth = useAuth()
const router = useRouter()
const route = useRoute()

const form = reactive({ username: '', password: '' })
const show = ref(false)
const loading = ref(false)
const error = ref('')
const errors = ref({})

async function submit() {
  loading.value = true
  error.value = ''
  errors.value = {}
  try {
    await auth.login(form.username.trim(), form.password)
    router.push(route.query.next || '/')
  } catch (e) {
    errors.value = fieldErrors(e)
    error.value = errors.value.username || errorMessage(e)
  } finally {
    loading.value = false
  }
}

// Kotak akun contoh hanya dimuat saat `npm run dev`; Vite membuang cabang ini dari build produksi.
const DemoAkun = import.meta.env.DEV ? defineAsyncComponent(() => import('@/components/DemoAkun.vue')) : null
</script>

<template>
  <div class="login">
    <section class="intro">
      <div class="brand"><span class="logo"><Home :size="22" /></span><b>SIRTA</b></div>
      <h1>Administrasi RT jadi lebih mudah dan tertata.</h1>
      <p>Kelola data warga, surat-menyurat, dan permohonan layanan dalam satu tempat — dari pengajuan hingga surat selesai.</p>
      <ul>
        <li><Users :size="20" /> <span><b>Data warga terpusat</b>Satu sumber data untuk semua keperluan.</span></li>
        <li><FileCheck2 :size="20" /> <span><b>Surat dibuat dari template</b>Nomor surat otomatis, tinggal cetak.</span></li>
        <li><ShieldCheck :size="20" /> <span><b>Hak akses sesuai peran</b>Setiap pengurus hanya melihat yang perlu.</span></li>
      </ul>
      <small>RT 03 / RW 20 · Perum Griya Kreasi Aqilla · Sukajaya, Cibitung, Bekasi</small>
    </section>

    <section class="panel">
      <form class="box" @submit.prevent="submit">
        <h2>Masuk ke akun Anda</h2>
        <p class="muted">Khusus pengurus RT.</p>

        <div v-if="error" class="callout tone-bad" role="alert">{{ error }}</div>

        <FormField label="Username">
          <input v-model="form.username" class="input" autocomplete="username" placeholder="mis. admin" required autofocus>
        </FormField>
        <FormField label="Kata sandi">
          <div class="pw">
            <input v-model="form.password" class="input" :type="show ? 'text' : 'password'" autocomplete="current-password" placeholder="Kata sandi" required>
            <button type="button" class="btn btn-icon" :aria-label="show ? 'Sembunyikan' : 'Tampilkan'" @click="show = !show">
              <component :is="show ? EyeOff : Eye" :size="18" />
            </button>
          </div>
        </FormField>

        <button class="btn btn-primary btn-block" :disabled="loading">{{ loading ? 'Memeriksa…' : 'Masuk' }}</button>

        <div class="warga-box"><b>Anda warga?</b><span>Tidak perlu akun.</span><div class="row"><RouterLink class="btn btn-soft btn-sm grow" to="/ajukan"><FilePlus2 :size="15" /> Ajukan surat</RouterLink><RouterLink class="btn btn-secondary btn-sm grow" to="/lacak"><Search :size="15" /> Lacak</RouterLink></div></div>

        <component :is="DemoAkun" v-if="DemoAkun" @pilih="(u) => { form.username = u; form.password = 'password' }" />
      </form>
    </section>
  </div>
</template>

<style scoped>
.login { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }
.intro { background: linear-gradient(160deg, #0a5f53, #0f7b6c 55%, #17917f); color: #fff; padding: 56px 64px; display: flex; flex-direction: column; justify-content: center; gap: 22px; position: relative; overflow: hidden; }
.intro::after { content: ''; position: absolute; width: 520px; height: 520px; right: -200px; bottom: -220px; border-radius: 50%; background: rgba(255,255,255,.07); }
.brand { display: flex; align-items: center; gap: 12px; font-size: 20px; letter-spacing: .06em; }
.logo { width: 44px; height: 44px; border-radius: 14px; background: #fff; color: #0f7b6c; display: grid; place-items: center; }
h1 { font-size: 36px; line-height: 1.2; letter-spacing: -.02em; max-width: 520px; }
.intro p { opacity: .88; font-size: 16px; max-width: 480px; }
ul { list-style: none; padding: 0; margin: 6px 0 0; display: flex; flex-direction: column; gap: 16px; }
li { display: flex; gap: 14px; align-items: flex-start; }
li svg { flex: none; margin-top: 2px; }
li span { font-size: 13.5px; opacity: .9; }
li b { display: block; font-size: 14.5px; }
small { opacity: .7; margin-top: 10px; }
.panel { display: grid; place-items: center; padding: 32px 20px; background: var(--bg); }
.box { width: min(420px, 100%); display: flex; flex-direction: column; gap: 16px; background: var(--surface); padding: 32px; border-radius: 20px; border: 1px solid var(--line); box-shadow: var(--shadow); }
.box h2 { font-size: 22px; }
.pw { position: relative; }
.pw .input { padding-right: 46px; }
.pw .btn { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); }
.warga-box { display: flex; flex-direction: column; gap: 8px; border-top: 1px solid var(--line); padding-top: 16px; font-size: 13px; }
.warga-box > span { color: var(--muted); margin-top: -6px; }
@media (max-width: 900px) {
  .login { grid-template-columns: 1fr; }
  .intro { padding: 32px 24px; }
  h1 { font-size: 26px; }
  ul, small { display: none; }
}
</style>
