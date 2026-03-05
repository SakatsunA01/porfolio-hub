import { defineStore } from 'pinia'
import api from '../services/api'

type StorefrontSettings = {
  name: string
  logo_url: string | null
  brand_palette: string[]
  manifesto_text: string | null
  philosophy_text: string | null
  contact_text: string | null
  team_text: string | null
}

const defaultPalette = ['#F7F5F0', '#ECE7DF', '#22221F', '#5A5A55', '#4F5D47']
const backendUrl = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

const isValidHex = (value: string) => /^#[A-Fa-f0-9]{6}$/.test(value)
const normalizeHex = (value: string, fallback: string) => {
  const normalized = value?.trim().startsWith('#') ? value.trim() : `#${value?.trim() || ''}`
  return isValidHex(normalized) ? normalized.toUpperCase() : fallback
}

const hexToRgb = (hex: string) => {
  const normalized = hex.replace('#', '')
  const safeHex = normalized.length === 6 ? normalized : '000000'
  const int = parseInt(safeHex, 16)
  const r = (int >> 16) & 255
  const g = (int >> 8) & 255
  const b = int & 255
  return `${r} ${g} ${b}`
}

const normalizePalette = (palette: unknown): string[] => {
  if (!Array.isArray(palette) || palette.length < 5) {
    return [...defaultPalette]
  }

  const next = defaultPalette.map((fallback, index) =>
    normalizeHex(String(palette[index] ?? fallback), fallback),
  )

  return next
}

const normalizeLogoUrl = (value: string | null | undefined): string | null => {
  if (!value) {
    return null
  }

  if (/^https?:\/\//i.test(value)) {
    return value
  }

  if (value.startsWith('/') && backendUrl) {
    return `${backendUrl}${value}`
  }

  return value
}

const applyPaletteToDocument = (palette: string[]) => {
  if (typeof document === 'undefined') {
    return
  }

  const [bgPrimary, bgSecondary, textPrimary, textSecondary, accentOlive] = palette
  const accentClay = palette[4] || defaultPalette[4]
  const root = document.documentElement

  root.style.setProperty('--bg-primary-rgb', hexToRgb(bgPrimary || defaultPalette[0]))
  root.style.setProperty('--bg-secondary-rgb', hexToRgb(bgSecondary || defaultPalette[1]))
  root.style.setProperty('--text-primary-rgb', hexToRgb(textPrimary || defaultPalette[2]))
  root.style.setProperty('--text-secondary-rgb', hexToRgb(textSecondary || defaultPalette[3]))
  root.style.setProperty('--accent-olive-rgb', hexToRgb(accentOlive || defaultPalette[4]))
  root.style.setProperty('--accent-clay-rgb', hexToRgb(accentClay))
}

export const useStorefrontSettingsStore = defineStore('storefront-settings', {
  state: () => ({
    isLoading: false,
    hasLoaded: false,
    settings: {
      name: 'Axis Tech',
      logo_url: null,
      brand_palette: defaultPalette,
      manifesto_text: null,
      philosophy_text: null,
      contact_text: null,
      team_text: null,
    } as StorefrontSettings,
  }),
  actions: {
    async fetchSettings(force = false) {
      if (this.isLoading) {
        return
      }

      if (this.hasLoaded && !force) {
        applyPaletteToDocument(this.settings.brand_palette)
        return
      }

      this.isLoading = true
      try {
        const response = await api.get<{ data: StorefrontSettings }>('/store/settings')
        const data = response.data.data
        this.settings = {
          ...this.settings,
          ...data,
          logo_url: normalizeLogoUrl(data.logo_url),
          brand_palette: normalizePalette(data.brand_palette),
        }
        this.hasLoaded = true
      } catch {
        this.settings.brand_palette = [...defaultPalette]
      } finally {
        applyPaletteToDocument(this.settings.brand_palette)
        this.isLoading = false
      }
    },
  },
})
