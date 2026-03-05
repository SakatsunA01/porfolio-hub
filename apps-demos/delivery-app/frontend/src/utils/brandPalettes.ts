export interface BrandPalette {
  key: string
  label: string
  description: string
  value: string
  primary: string
  primaryStrong: string
  primarySoft: string
  surface: string
  canvas: string
  ink: string
  colors: [string, string, string, string, string]
}

export const BRAND_PALETTES: BrandPalette[] = [
  {
    key: 'pastel-peach',
    label: 'Pastel durazno',
    description: 'Calida, suave y de cafeteria.',
    value: '#E88C7D',
    primary: '#E88C7D',
    primaryStrong: '#C86D5F',
    primarySoft: '#F7D8CF',
    surface: '#FFF1EB',
    canvas: '#FFF8F5',
    ink: '#5E463F',
    colors: ['#E88C7D', '#F2B6A0', '#F7D8CF', '#FFF1EB', '#5E463F'],
  },
  {
    key: 'pastel-mint',
    label: 'Pastel menta',
    description: 'Fresca, liviana y moderna.',
    value: '#6FB8A8',
    primary: '#6FB8A8',
    primaryStrong: '#4D8F81',
    primarySoft: '#CFEAE3',
    surface: '#EEF8F5',
    canvas: '#F8FCFB',
    ink: '#355B54',
    colors: ['#6FB8A8', '#8FD0C0', '#CFEAE3', '#EEF8F5', '#355B54'],
  },
  {
    key: 'stone-gray',
    label: 'Grises piedra',
    description: 'Sobria, neutra y elegante.',
    value: '#7A7F87',
    primary: '#7A7F87',
    primaryStrong: '#565C64',
    primarySoft: '#D8DCE0',
    surface: '#EEF1F3',
    canvas: '#F8F9FA',
    ink: '#2F343A',
    colors: ['#7A7F87', '#9AA0A8', '#D8DCE0', '#EEF1F3', '#2F343A'],
  },
  {
    key: 'night-bistro',
    label: 'Bistro oscuro',
    description: 'Contraste fuerte para marcas nocturnas.',
    value: '#B28B5E',
    primary: '#B28B5E',
    primaryStrong: '#8E6A42',
    primarySoft: '#E4D3BC',
    surface: '#20252B',
    canvas: '#111417',
    ink: '#F4EDE3',
    colors: ['#B28B5E', '#8E6A42', '#E4D3BC', '#20252B', '#111417'],
  },
  {
    key: 'clear-day',
    label: 'Claro aireado',
    description: 'Luminosa, limpia y mas abierta.',
    value: '#5A8DEE',
    primary: '#5A8DEE',
    primaryStrong: '#3D6FC8',
    primarySoft: '#D9E6FF',
    surface: '#EEF4FF',
    canvas: '#FAFCFF',
    ink: '#2D4268',
    colors: ['#5A8DEE', '#8EB3F4', '#D9E6FF', '#EEF4FF', '#2D4268'],
  },
]

export const getBrandPalette = (key?: string | null, fallbackPrimary?: string | null): BrandPalette => {
  const matched = BRAND_PALETTES.find((palette) => palette.key === key)
  if (matched) return matched
  const fallback = BRAND_PALETTES[0] ?? {
    key: 'default',
    label: 'Default',
    description: 'Fallback palette.',
    value: '#E88C7D',
    primary: '#E88C7D',
    primaryStrong: '#C86D5F',
    primarySoft: '#F7D8CF',
    surface: '#FFF1EB',
    canvas: '#FFF8F5',
    ink: '#5E463F',
    colors: ['#E88C7D', '#F2B6A0', '#F7D8CF', '#FFF1EB', '#5E463F'],
  }
  if (!fallbackPrimary) return fallback
  return {
    ...fallback,
    value: fallbackPrimary,
    primary: fallbackPrimary,
  }
}
