const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

export function formatTanggal(value, short = false) {
  if (!value) return '—'
  const d = new Date(value)
  if (isNaN(d)) return '—'
  const bln = short ? BULAN[d.getMonth()].slice(0, 3) : BULAN[d.getMonth()]
  return `${d.getDate()} ${bln} ${d.getFullYear()}`
}

export function formatWaktu(value) {
  if (!value) return '—'
  const d = new Date(value)
  return `${formatTanggal(d, true)}, ${String(d.getHours()).padStart(2, '0')}.${String(d.getMinutes()).padStart(2, '0')}`
}

export function waktuRelatif(value) {
  const diff = (Date.now() - new Date(value).getTime()) / 1000
  if (diff < 60) return 'baru saja'
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} hari lalu`
  return formatTanggal(value, true)
}

export function formatUkuran(bytes) {
  if (!bytes) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

export const hariIni = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

export const inisial = (nama = '') =>
  nama.split(' ').filter(Boolean).map((n) => n[0]).slice(0, 2).join('').toUpperCase()

export function sapaan() {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
}

/** Isi placeholder {{kunci}} pada template surat. Placeholder yang tidak diketahui dibiarkan. */
export function isiTemplate(isi, data) {
  return (isi || '').replace(/\{\{(\w+)\}\}/g, (m, k) => (data[k] !== undefined && data[k] !== '' ? data[k] : m))
}

/** Cetak surat dalam jendela baru dengan tata letak surat resmi. */
export function cetakSurat({ nomor, tanggal, isi }) {
  const teks = isiTemplate(isi, { nomor, tanggal: formatTanggal(tanggal) })
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const baris = teks.split('\n')
  const kop = baris.slice(0, 3)
  const badan = baris.slice(3).join('\n')
  const w = window.open('', '_blank', 'width=900,height=1000')
  if (!w) return false
  w.document.write(`<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Surat ${esc(nomor)}</title>
<style>
@page{size:A4;margin:22mm 22mm}
body{font-family:'Times New Roman',serif;font-size:12pt;line-height:1.55;color:#000;margin:0}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:10px;margin-bottom:22px}
.kop b{display:block;font-size:14pt;letter-spacing:.5px}
.kop span{display:block;font-size:11pt}
pre{font-family:inherit;white-space:pre-wrap;margin:0}
</style></head><body>
<div class="kop"><b>${esc(kop[0] || '')}</b><span>${esc(kop[1] || '')}</span><span>${esc(kop[2] || '')}</span></div>
<pre>${esc(badan.trim())}</pre>
<script>window.onload=()=>setTimeout(()=>window.print(),200)<\/script>
</body></html>`)
  w.document.close()
  return true
}
