const BACKEND_API_URL = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'
const BACKEND_ASSET_BASE_URL = import.meta.env.VITE_BACKEND_ASSET_BASE_URL || ''

const PRODUCT_IMAGE_BY_NAME: Record<string, string> = {
  'burger clasica': 'burger-clasica',
  'milanesa completa': 'milanesa-completa',
  'papas crocantes': 'papas-crocantes',
  'coca-cola 500ml': 'coca-cola-500',
  'limonada natural': 'limonada-natural',
  'cheesecake frutos rojos': 'cheesecake-frutos-rojos',
}

const normalizeBase = (url: string) => url.replace(/\/+$/, '')

const getApiOrigin = (): string => {
  try {
    return normalizeBase(new URL(BACKEND_API_URL).origin)
  } catch {
    if (typeof window !== 'undefined' && window.location?.origin) {
      return normalizeBase(window.location.origin)
    }
    return 'http://127.0.0.1:8010'
  }
}

const getAssetOrigin = () => normalizeBase(BACKEND_ASSET_BASE_URL || getApiOrigin())

const normalizePath = (path: string) => (path.startsWith('/') ? path : `/${path}`)

const slugify = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')

export const resolveAssetUrl = (url: string | null | undefined): string | null => {
  if (!url) return null
  const trimmed = String(url).trim()
  if (!trimmed) return null
  if (/^https?:\/\//i.test(trimmed)) return trimmed
  if (trimmed.startsWith('//')) return `${window.location.protocol}${trimmed}`
  return `${getAssetOrigin()}${normalizePath(trimmed)}`
}

export const resolveProductImageUrl = (url: string | null | undefined, productName: string): string | null => {
  const direct = resolveAssetUrl(url)
  if (direct) return direct
  const normalized = productName.trim().toLowerCase()
  const slug = PRODUCT_IMAGE_BY_NAME[normalized] || slugify(productName)
  if (!slug) return null
  return `${getAssetOrigin()}/images/products/${slug}.svg`
}
