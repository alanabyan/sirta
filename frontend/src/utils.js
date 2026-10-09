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
