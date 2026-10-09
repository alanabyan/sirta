import QRCode from 'qrcode'
import api from '@/api'
import { formatTanggal, isiTemplate } from '@/utils'

/**
 * Cetak surat resmi: kop dari pengaturan, isi surat, blok tanda tangan + stempel,
 * dan kode QR untuk memeriksa keaslian. Surat berstatus Draft dicetak dengan watermark
 * dan tanpa tanda tangan/stempel.
 */

// Gambar tanda tangan/stempel bersifat privat; diunduh sekali lalu disimpan sampai pengaturan berubah.
let cache = { versi: null, gambar: {} }

const esc = (s = '') => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

const keDataUrl = (blob) =>
  new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload = () => resolve(r.result)
    r.onerror = reject
    r.readAsDataURL(blob)
  })

async function muatPengaturan() {
  const p = (await api.get('/pengaturan')).data.data
  if (cache.versi !== p.versi) cache = { versi: p.versi, gambar: {} }
  for (const [jenis, ada] of [['tanda_tangan', p.ada_tanda_tangan], ['stempel', p.ada_stempel]]) {
    if (ada && !cache.gambar[jenis]) {
      const { data } = await api.get(`/pengaturan/gambar/${jenis}`, { responseType: 'blob' })
      cache.gambar[jenis] = await keDataUrl(data)
    }
    if (!ada) delete cache.gambar[jenis]
  }
  return { p, ttd: cache.gambar.tanda_tangan, stempel: cache.gambar.stempel }
}

/** Buang kop & blok tanda tangan teks bawaan template lama; kini dibuat otomatis. */
function siapkanBadan(isi) {
  let teks = (isi || '').replace(/\r/g, '')
  if (/^RUKUN TETANGGA/i.test(teks)) teks = teks.split('\n').slice(3).join('\n')
  const i = teks.indexOf('{{ttd}}')
  if (i >= 0) return teks.slice(0, i).trimEnd()
  const lama = teks.search(/\n[^\n]*, \{\{tanggal\}\}\s*\n/)
  return (lama >= 0 ? teks.slice(0, lama) : teks).trimEnd()
}

function bangunHtml({ surat, p, ttd, stempel, qr, urlCek }) {
  const terbit = surat.status === 'Diterbitkan'
  const badan = isiTemplate(siapkanBadan(surat.isi), { nomor: surat.nomor, tanggal: formatTanggal(surat.tanggal) })
  const baris = badan.split('\n')
  while (baris.length && !baris[0].trim()) baris.shift()

  // Judul (baris kapital pertama) & baris "Nomor:" dipusatkan.
  let judul = ''
  let nomor = ''
  if (baris[0] && baris[0] === baris[0].toUpperCase() && baris[0].length < 70) judul = baris.shift()
  if (baris[0] && /^Nomor\s*:/i.test(baris[0])) nomor = baris.shift()
  const isiHtml = esc(baris.join('\n').trim())

  const kop = (p.kop || '').split('\n').filter(Boolean)
  const kopHtml = kop.map((k, i) => (i === 0 ? `<b>${esc(k)}</b>` : `<span>${esc(k)}</span>`)).join('')

  const gambar = terbit
    ? `${stempel ? `<img class="stempel" src="${stempel}" alt="">` : ''}${ttd ? `<img class="ttd" src="${ttd}" alt="">` : ''}`
    : ''

  return `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Surat ${esc(surat.nomor)}</title>
<style>
@page{size:A4;margin:20mm 22mm}
*{box-sizing:border-box}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.55;color:#000;margin:0;position:relative}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:20px}
.kop b{display:block;font-size:14pt;letter-spacing:.5px}
.kop span{display:block;font-size:11pt}
.judul{text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt;margin-top:6px}
.nomor{text-align:center;margin-bottom:16px}
pre{font-family:inherit;white-space:pre-wrap;margin:0;text-align:justify}
.bawah{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-top:26px;page-break-inside:avoid}
.qr{display:flex;gap:10px;align-items:flex-end;max-width:92mm;font-size:8.5pt;line-height:1.3}
.qr img{width:24mm;height:24mm;flex:none}
.ttd-blok{text-align:center;min-width:62mm}
.sign{position:relative;height:30mm;margin:2px 0}
.sign img{position:absolute;mix-blend-mode:multiply}
.sign .stempel{height:28mm;left:-4mm;top:1mm;opacity:.9}
.sign .ttd{height:25mm;left:50%;transform:translateX(-35%);top:3mm;max-width:50mm;object-fit:contain}
.nama{font-weight:bold;text-decoration:underline}
.wm{position:fixed;inset:0;display:grid;place-items:center;pointer-events:none;z-index:-1}
.wm span{font:bold 90pt Arial,sans-serif;color:rgba(200,0,0,.12);transform:rotate(-30deg);letter-spacing:6px}
.tidak-terbit{font-size:9pt;color:#a00;margin-top:6px}
@media screen{body{max-width:210mm;margin:0 auto;padding:16mm 20mm;background:#fff}}
</style></head><body>
${terbit ? '' : '<div class="wm"><span>DRAFT</span></div>'}
<div class="kop">${kopHtml}</div>
${judul ? `<div class="judul">${esc(judul)}</div>` : ''}
${nomor ? `<div class="nomor">${esc(nomor)}</div>` : ''}
<pre>${isiHtml}</pre>
<div class="bawah">
  <div>${terbit && qr ? `<div class="qr"><img src="${qr}" alt="Kode QR keaslian surat"><div>Surat ini ditandatangani secara elektronik.<br>Periksa keaslian: <b>${esc(urlCek)}</b></div></div>` : ''}</div>
  <div class="ttd-blok">
    <div>${esc(p.kota)}, ${esc(formatTanggal(surat.tanggal))}</div>
    <div>${esc(p.penandatangan_jabatan)}</div>
    <div class="sign">${gambar}</div>
    <div class="nama">${esc(p.penandatangan_nama)}</div>
    ${terbit ? '' : '<div class="tidak-terbit">Belum diterbitkan — tanpa tanda tangan &amp; stempel</div>'}
  </div>
</div>
<script>window.onload=()=>setTimeout(()=>window.print(),300)<\/script>
</body></html>`
}

/**
 * @returns {Promise<{ok:boolean, reason?:string}>}
 * Jendela dibuka lebih dulu (selagi masih dalam klik pengguna) agar tidak diblokir pop-up blocker.
 */
export async function cetakSurat(surat) {
  const w = window.open('', '_blank', 'width=900,height=1000')
  if (!w) return { ok: false, reason: 'popup' }
  w.document.write('<p style="font-family:sans-serif;padding:24px">Menyiapkan surat…</p>')
  try {
    const { p, ttd, stempel } = await muatPengaturan()
    const urlCek = surat.kode_verifikasi ? `${location.origin}/cek-surat/${surat.kode_verifikasi}` : ''
    const qr = surat.status === 'Diterbitkan' && urlCek ? await QRCode.toDataURL(urlCek, { margin: 0, width: 240, errorCorrectionLevel: 'M' }) : ''
    w.document.open()
    w.document.write(bangunHtml({ surat, p, ttd, stempel, qr, urlCek }))
    w.document.close()
    return { ok: true }
  } catch (e) {
    w.close()
    return { ok: false, reason: e.response?.status === 403 ? 'izin' : 'galat' }
  }
}

export const resetCacheCetak = () => (cache = { versi: null, gambar: {} })
