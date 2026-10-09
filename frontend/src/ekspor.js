import writeExcelFile from 'write-excel-file/browser'
import api from '@/api'
import { formatTanggal } from '@/utils'

/**
 * Ekspor laporan. Satu struktur dipakai untuk Excel dan PDF:
 *
 *   laporan = {
 *     berkas: 'laporan-bulanan-2026-10', judul, periode?,
 *     ringkasan: [[label, nilai], …],
 *     bagian: [{ judul, kolom: [{ header, width, align?, tipe? }], baris: [[nilai, …], …] }],
 *     lanskap?: boolean
 *   }
 *   tipe kolom: 'tanggal' (nilai "YYYY-MM-DD"), 'angka', atau teks (bawaan).
 */

const WARNA = '#0f7b6c'
const esc = (s = '') => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

// ---------------------------------------------------------------- Excel
const tanggalExcel = (s) => {
  const [y, m, d] = String(s).split('-').map(Number)
  return { value: new Date(Date.UTC(y, m - 1, d)), type: Date, format: 'dd/mm/yyyy', align: 'left' }
}

function sel(nilai, kolom) {
  if (nilai === null || nilai === undefined || nilai === '') return null
  if (kolom.tipe === 'tanggal') return tanggalExcel(nilai)
  if (kolom.tipe === 'angka') return { value: Number(nilai), align: 'right' }
  return { value: String(nilai), align: kolom.align || 'left', wrap: true, alignVertical: 'top' }
}

const NAMA_SHEET_LARANG = /[\\/?*[\]:]/g

export async function unduhExcel(laporan) {
  const lebar = Math.max(2, ...laporan.bagian.map((b) => b.kolom.length))
  const sheets = []

  // Lembar pertama: judul + ringkasan.
  const rin = [
    [{ value: laporan.judul, fontWeight: 'bold', fontSize: 15, textColor: WARNA, columnSpan: lebar }, ...Array(lebar - 1).fill(null)],
    laporan.periode ? [{ value: laporan.periode, textColor: '#5b6b68', columnSpan: lebar }, ...Array(lebar - 1).fill(null)] : [],
    [],
    ...laporan.ringkasan.map(([l, v]) => [{ value: l, fontWeight: 'bold', backgroundColor: '#e0f3ee', borderColor: '#cfe6df' }, typeof v === 'number' ? { value: v, align: 'right', borderColor: '#cfe6df' } : { value: String(v ?? '—'), align: 'right', borderColor: '#cfe6df' }]),
  ]
  sheets.push({ data: rin, sheet: 'Ringkasan', columns: [{ width: 38 }, { width: 18 }] })

  for (const b of laporan.bagian) {
    const head = b.kolom.map((k) => ({ value: k.header, fontWeight: 'bold', backgroundColor: WARNA, textColor: '#ffffff', align: 'center', alignVertical: 'center', borderColor: '#0a5f53' }))
    const data = [
      [{ value: b.judul, fontWeight: 'bold', fontSize: 13, columnSpan: b.kolom.length }, ...Array(b.kolom.length - 1).fill(null)],
      [],
      head,
      ...b.baris.map((r) => r.map((v, i) => sel(v, b.kolom[i]))),
    ]
    sheets.push({
      data,
      sheet: b.judul.replace(NAMA_SHEET_LARANG, '').slice(0, 31) || 'Data',
      columns: b.kolom.map((k) => ({ width: k.width || 16 })),
      stickyRowsCount: 3,
      ...(laporan.lanskap ? { orientation: 'landscape' } : {}),
    })
  }

  await writeExcelFile(sheets).toFile(`${laporan.berkas}.xlsx`)
}

// ---------------------------------------------------------------- PDF (cetak)
const selHtml = (v, k) => {
  if (v === null || v === undefined || v === '') return '<td class="kosong">—</td>'
  const kelas = k.tipe === 'angka' ? ' class="r"' : k.align === 'center' ? ' class="c"' : ''
  return `<td${kelas}>${esc(k.tipe === 'tanggal' ? formatTanggal(v, true) : v)}</td>`
}

/** Jendela dibuka lebih dulu (saat masih di dalam klik) agar tidak diblokir pop-up blocker. */
export async function cetakLaporan(sumber) {
  const w = window.open('', '_blank', 'width=1000,height=1000')
  if (!w) return { ok: false, reason: 'popup' }
  w.document.write('<p style="font-family:sans-serif;padding:24px">Menyiapkan laporan…</p>')
  try {
    const laporan = await (typeof sumber === 'function' ? sumber() : sumber)
    const p = (await api.get('/pengaturan')).data.data
    const kop = (p.kop || '').split('\n').filter(Boolean)
    const kopHtml = kop.map((k, i) => (i === 0 ? `<b>${esc(k)}</b>` : `<span>${esc(k)}</span>`)).join('')
    const ringkasan = laporan.ringkasan.length
      ? `<table class="ring">${laporan.ringkasan.map(([l, v]) => `<tr><th>${esc(l)}</th><td>${esc(v ?? '—')}</td></tr>`).join('')}</table>`
      : ''
    const bagian = laporan.bagian.map((b) => `
      <h3>${esc(b.judul)}</h3>
      ${b.baris.length ? `<table class="data"><thead><tr><th class="no">No</th>${b.kolom.map((k) => `<th>${esc(k.header)}</th>`).join('')}</tr></thead>
      <tbody>${b.baris.map((r, i) => `<tr><td class="c">${i + 1}</td>${r.map((v, j) => selHtml(v, b.kolom[j])).join('')}</tr>`).join('')}</tbody></table>` : '<p class="kosong">Tidak ada data.</p>'}`).join('')

    w.document.open()
    w.document.write(`<!doctype html><html lang="id"><head><meta charset="utf-8"><title>${esc(laporan.judul)}</title>
<style>
@page{size:A4 ${laporan.lanskap ? 'landscape' : 'portrait'};margin:16mm 14mm}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;font-size:10pt;line-height:1.4;color:#111;margin:0}
.kop{text-align:center;border-bottom:3px double #000;padding-bottom:6px;margin-bottom:14px;font-family:'Times New Roman',serif}
.kop b{display:block;font-size:13pt}.kop span{display:block;font-size:10pt}
h1{font-size:14pt;margin:0 0 2px;text-align:center}
.periode{text-align:center;color:#444;margin-bottom:12px}
h3{font-size:11pt;margin:16px 0 6px;color:#0a5f53;page-break-after:avoid}
table{border-collapse:collapse;width:100%}
.ring{width:auto;min-width:55%;margin:0 auto 6px}
.ring th{background:#e0f3ee;text-align:left;font-weight:600;padding:4px 10px;border:1px solid #cfe6df}
.ring td{padding:4px 10px;text-align:right;border:1px solid #cfe6df;font-weight:700}
.data th{background:#0f7b6c;color:#fff;padding:5px 6px;border:1px solid #0a5f53;font-size:9pt;text-align:left}
.data td{padding:4px 6px;border:1px solid #cdd5d3;vertical-align:top;font-size:9pt}
.data tr:nth-child(even) td{background:#f5f8f7}
.data thead{display:table-header-group}.data tr{page-break-inside:avoid}
.no{width:28px;text-align:center}.c{text-align:center}.r{text-align:right}
.kosong{color:#888}
.ttd{margin-top:28px;width:230px;margin-left:auto;text-align:center;page-break-inside:avoid}
.ttd .spasi{height:62px}.ttd b{text-decoration:underline}
.foot{margin-top:18px;font-size:8pt;color:#777;border-top:1px solid #ddd;padding-top:4px}
@media screen{body{max-width:1000px;margin:0 auto;padding:16mm}}
</style></head><body>
<div class="kop">${kopHtml}</div>
<h1>${esc(laporan.judul)}</h1>
${laporan.periode ? `<div class="periode">${esc(laporan.periode)}</div>` : ''}
${ringkasan}
${bagian}
<div class="ttd"><div>${esc(p.kota)}, ${esc(formatTanggal(new Date().toISOString().slice(0, 10)))}</div><div>Mengetahui,<br>${esc(p.penandatangan_jabatan)}</div><div class="spasi"></div><b>${esc(p.penandatangan_nama)}</b></div>
<div class="foot">Dicetak dari SIRTA — Sistem Informasi RT</div>
<script>window.onload=()=>setTimeout(()=>window.print(),300)<\/script>
</body></html>`)
    w.document.close()
    return { ok: true }
  } catch {
    w.close()
    return { ok: false, reason: 'galat' }
  }
}
