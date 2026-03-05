export type ProductType = 'permanent' | 'limited' | 'preorder'

export interface ProductDetailItem {
  name: string
  description: string
}

export interface ProductVariant {
  id: number
  size: string
  stock: number
  sku: string | null
}

export interface Product {
  id: number
  category_id: number | null
  category: string
  name: string
  slug: string
  type: ProductType
  stock: number
  stock_global: number | null
  preorder_shipping_date: string | null
  price: number
  description: string
  description_short: string | null
  description_long: string | null
  details: ProductDetailItem[]
  image: string | null
  images: string[]
  variants: ProductVariant[]
  is_active: boolean
  is_dropship: boolean
}

export interface CatalogCategory {
  id: number
  name: string
  products_count: number
}
