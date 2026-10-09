import { defineStore } from 'pinia'

let toastId = 0

export const useUi = defineStore('ui', {
  state: () => ({
    toasts: [],
    confirm: null, // { title, message, confirmText, danger, resolve }
    theme: localStorage.getItem('sirta_theme') || 'light',
    sidebarOpen: false,
  }),
  actions: {
    toast(message, type = 'success') {
      const id = ++toastId
      this.toasts.push({ id, message, type })
      setTimeout(() => this.dismiss(id), type === 'error' ? 6000 : 3500)
    },
    success(m) { this.toast(m, 'success') },
    error(m) { this.toast(m, 'error') },
    dismiss(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id)
    },
    /** Dialog konfirmasi berbasis promise: `if (await ui.ask({...})) ...` */
    ask({ title, message, confirmText = 'Ya, lanjutkan', danger = false }) {
      return new Promise((resolve) => {
        this.confirm = { title, message, confirmText, danger, resolve }
      })
    },
    answer(value) {
      this.confirm?.resolve(value)
      this.confirm = null
    },
    applyTheme() {
      document.documentElement.dataset.theme = this.theme
    },
    toggleTheme() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark'
      localStorage.setItem('sirta_theme', this.theme)
      this.applyTheme()
    },
  },
})
