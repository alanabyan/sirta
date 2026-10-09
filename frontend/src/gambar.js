/**
 * Pembersih latar gambar (dijalankan di browser, tanpa pustaka tambahan).
 *
 *  - mode "kertas": untuk foto stempel/tanda tangan di kertas terang. Warna kertas diperkirakan
 *    per area (sehingga bayangan & cahaya tidak merata tetap bersih), lalu hanya tinta yang dipertahankan.
 *  - mode "warna": untuk latar berwarna lain. Warna latar diambil dari tepi gambar, lalu area
 *    yang bersambung dengan tepi dan warnanya mirip dibuang.
 */

export const muatGambar = (src) =>
  new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('Gambar tidak dapat dibaca.'))
    img.src = src
  })

export function kanvasDari(img, maks = 900) {
  const r = Math.min(1, maks / Math.max(img.naturalWidth, img.naturalHeight))
  const c = document.createElement('canvas')
  c.width = Math.max(1, Math.round(img.naturalWidth * r))
  c.height = Math.max(1, Math.round(img.naturalHeight * r))
  c.getContext('2d').drawImage(img, 0, 0, c.width, c.height)
  return c
}

const smooth = (a, b, x) => {
  const t = Math.min(1, Math.max(0, (x - a) / (b - a)))
  return t * t * (3 - 2 * t)
}
const lum = (r, g, b) => 0.299 * r + 0.587 * g + 0.114 * b

/** Warna rata-rata sepanjang tepi gambar (median per kanal). */
function warnaTepi(d, w, h) {
  const R = [], G = [], B = []
  const ambil = (x, y) => {
    const i = (y * w + x) * 4
    R.push(d[i]); G.push(d[i + 1]); B.push(d[i + 2])
  }
  const tebal = Math.max(1, Math.round(Math.min(w, h) * 0.01))
  for (let t = 0; t < tebal; t++) {
    for (let x = 0; x < w; x += 2) { ambil(x, t); ambil(x, h - 1 - t) }
    for (let y = 0; y < h; y += 2) { ambil(t, y); ambil(w - 1 - t, y) }
  }
  const med = (a) => a.sort((p, q) => p - q)[a.length >> 1]
  return [med(R), med(G), med(B)]
}

/** Saran mode: tepi terang & hampir tak berwarna = kertas. */
export function sarankanMode(kanvas) {
  const { width: w, height: h } = kanvas
  const d = kanvas.getContext('2d').getImageData(0, 0, w, h).data
  const [r, g, b] = warnaTepi(d, w, h)
  const kroma = Math.max(r, g, b) - Math.min(r, g, b)
  return lum(r, g, b) > 165 && kroma < 55 ? 'kertas' : 'warna'
}

function modeKertas(img, w, h, sens) {
  const d = img.data
  const cell = 32
  const gw = Math.ceil(w / cell)
  const gh = Math.ceil(h / cell)
  const peta = new Float32Array(gw * gh * 3)
  const petaLum = new Float32Array(gw * gh)

  // Warna kertas tiap sel = rata-rata piksel paling terang (persentil ke-85 ke atas).
  for (let cy = 0; cy < gh; cy++) {
    for (let cx = 0; cx < gw; cx++) {
      const x0 = cx * cell, y0 = cy * cell
      const x1 = Math.min(w, x0 + cell), y1 = Math.min(h, y0 + cell)
      const ls = []
      for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) { const i = (y * w + x) * 4; ls.push(lum(d[i], d[i + 1], d[i + 2])) }
      const thr = [...ls].sort((a, b) => a - b)[Math.floor(ls.length * 0.85)]
      let r = 0, g = 0, b = 0, n = 0, k = 0
      for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) {
        const i = (y * w + x) * 4
        if (ls[k++] >= thr) { r += d[i]; g += d[i + 1]; b += d[i + 2]; n++ }
      }
      const o = (cy * gw + cx) * 3
      peta[o] = r / n; peta[o + 1] = g / n; peta[o + 2] = b / n
      petaLum[cy * gw + cx] = lum(r / n, g / n, b / n)
    }
  }
  // Sel yang hampir seluruhnya tertutup tinta (gelap) diganti dengan warna kertas umum.
  const urut = [...petaLum].sort((a, b) => a - b)
  const globLum = urut[Math.floor(urut.length * 0.8)]
  let gr = 0, gg = 0, gb = 0, gn = 0
  for (let i = 0; i < petaLum.length; i++) if (petaLum[i] >= globLum * 0.95) { gr += peta[i * 3]; gg += peta[i * 3 + 1]; gb += peta[i * 3 + 2]; gn++ }
  gr /= gn; gg /= gn; gb /= gn
  for (let i = 0; i < petaLum.length; i++) if (petaLum[i] < globLum * 0.72) { peta[i * 3] = gr; peta[i * 3 + 1] = gg; peta[i * 3 + 2] = gb }

  const t0 = 0.2 - 0.16 * sens
  const t1 = t0 + 0.23
  for (let y = 0; y < h; y++) {
    const fy = Math.min(gh - 1, Math.max(0, y / cell - 0.5))
    const y0 = Math.floor(fy), y1 = Math.min(gh - 1, y0 + 1), ty = fy - y0
    for (let x = 0; x < w; x++) {
      const fx = Math.min(gw - 1, Math.max(0, x / cell - 0.5))
      const x0 = Math.floor(fx), x1 = Math.min(gw - 1, x0 + 1), tx = fx - x0
      const i = (y * w + x) * 4
      let dist = 0
      for (let c = 0; c < 3; c++) {
        const a = peta[(y0 * gw + x0) * 3 + c] * (1 - tx) + peta[(y0 * gw + x1) * 3 + c] * tx
        const b = peta[(y1 * gw + x0) * 3 + c] * (1 - tx) + peta[(y1 * gw + x1) * 3 + c] * tx
        const bg = a * (1 - ty) + b * ty
        const df = bg - d[i + c]
        dist += df * df
      }
      d[i + 3] = Math.round(255 * smooth(t0, t1, Math.sqrt(dist) / 255))
    }
  }
  return img
}

function modeWarna(img, w, h, sens) {
  const d = img.data
  const [br, bg, bb] = warnaTepi(d, w, h)
  const tol = 18 + sens * 110
  const jarak = (i) => Math.hypot(d[i] - br, d[i + 1] - bg, d[i + 2] - bb)
  const buang = new Uint8Array(w * h)
  const tumpuk = new Int32Array(w * h)
  let n = 0
  const dorong = (x, y) => {
    const p = y * w + x
    if (buang[p] || jarak(p * 4) > tol) return
    buang[p] = 1
    tumpuk[n++] = p
  }
  for (let x = 0; x < w; x++) { dorong(x, 0); dorong(x, h - 1) }
  for (let y = 0; y < h; y++) { dorong(0, y); dorong(w - 1, y) }
  while (n > 0) {
    const p = tumpuk[--n]
    const x = p % w, y = (p / w) | 0
    if (x > 0) dorong(x - 1, y)
    if (x < w - 1) dorong(x + 1, y)
    if (y > 0) dorong(x, y - 1)
    if (y < h - 1) dorong(x, y + 1)
  }
  for (let p = 0; p < w * h; p++) d[p * 4 + 3] = buang[p] ? 0 : 255
  // Haluskan tepi: piksel yang berbatasan dengan area terbuang dibuat setengah transparan sesuai kemiripan warna.
  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      const p = y * w + x
      if (buang[p]) continue
      if (buang[p - 1] || buang[p + 1] || buang[p - w] || buang[p + w]) d[p * 4 + 3] = Math.round(255 * smooth(tol, tol * 1.7, jarak(p * 4)))
    }
  }
  return img
}

function potong(kanvas) {
  const { width: w, height: h } = kanvas
  const d = kanvas.getContext('2d').getImageData(0, 0, w, h).data
  let x0 = w, y0 = h, x1 = -1, y1 = -1
  for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) if (d[(y * w + x) * 4 + 3] > 24) {
    if (x < x0) x0 = x
    if (x > x1) x1 = x
    if (y < y0) y0 = y
    if (y > y1) y1 = y
  }
  if (x1 < 0) return kanvas // tidak ada yang tersisa
  const pad = Math.round(Math.max(w, h) * 0.02) + 2
  x0 = Math.max(0, x0 - pad); y0 = Math.max(0, y0 - pad)
  x1 = Math.min(w - 1, x1 + pad); y1 = Math.min(h - 1, y1 + pad)
  const out = document.createElement('canvas')
  out.width = x1 - x0 + 1
  out.height = y1 - y0 + 1
  out.getContext('2d').drawImage(kanvas, x0, y0, out.width, out.height, 0, 0, out.width, out.height)
  return out
}

/** @returns {HTMLCanvasElement} kanvas PNG transparan */
export function bersihkanLatar(sumber, { mode = 'kertas', sens = 0.5, potongOtomatis = true } = {}) {
  const w = sumber.width, h = sumber.height
  const img = sumber.getContext('2d').getImageData(0, 0, w, h)
  const hasil = mode === 'kertas' ? modeKertas(img, w, h, sens) : modeWarna(img, w, h, sens)
  const c = document.createElement('canvas')
  c.width = w
  c.height = h
  c.getContext('2d').putImageData(hasil, 0, 0)
  return potongOtomatis ? potong(c) : c
}

/** PNG ≤ batas byte; bila terlalu besar, diperkecil bertahap. */
export async function keFilePng(kanvas, nama, batas = 900 * 1024) {
  let c = kanvas
  for (let i = 0; i < 6; i++) {
    const blob = await new Promise((r) => c.toBlob(r, 'image/png'))
    if (blob.size <= batas) return new File([blob], nama, { type: 'image/png' })
    const kecil = document.createElement('canvas')
    kecil.width = Math.round(c.width * 0.8)
    kecil.height = Math.round(c.height * 0.8)
    kecil.getContext('2d').drawImage(c, 0, 0, kecil.width, kecil.height)
    c = kecil
  }
  throw new Error('Gambar terlalu besar setelah diperkecil.')
}
