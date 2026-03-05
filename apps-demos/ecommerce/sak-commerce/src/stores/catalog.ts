import { defineStore } from 'pinia'
import api from '../services/api'
import type { CatalogCategory, Product } from '../types/catalog'
import type { FacetState, SearchIntent, SocialProofBlock } from '../types/commerce'

type CatalogResponse<T> = {
  data: T
}

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    products: [] as Product[],
    categories: [] as CatalogCategory[],
    socialProof: null as SocialProofBlock | null,
    isLoadingProducts: false,
    isLoadingCategories: false,
    isSearching: false,
    isLoadingSocialProof: false,
    hasLoadedProducts: false,
    hasLoadedCategories: false,
    searchQuery: '',
    intent: null as SearchIntent | null,
    facets: {
      category: null,
      type: null,
      inStockOnly: false,
      priceRange: 'all',
    } as FacetState,
    searchResults: [] as Product[],
    suggestedIntents: [] as string[],
  }),

  getters: {
    productById: (state) => (id: number) =>
      state.products.find((product) => product.id === id),
    activeProducts(state) {
      if (state.searchQuery || state.intent || state.facets.category || state.facets.type || state.facets.inStockOnly || state.facets.priceRange !== 'all') {
        return state.searchResults
      }

      return state.products
    },
  },

  actions: {
    syncSuggestedIntents() {
      const fromCategories = this.categories
        .map((category) => category.name.trim())
        .filter((name) => name.length > 0)

      const fallback = this.products
        .map((product) => product.category?.trim())
        .filter((name): name is string => Boolean(name))

      this.suggestedIntents = Array.from(new Set([...fromCategories, ...fallback])).slice(0, 6)
    },
    async fetchProducts(force = false) {
      if (this.isLoadingProducts) {
        return
      }

      if (this.hasLoadedProducts && !force) {
        return
      }

      this.isLoadingProducts = true

      try {
        const response = await api.get<CatalogResponse<Product[]>>('/store/products')
        this.products = response.data.data
        this.syncSuggestedIntents()
        this.hasLoadedProducts = true
      } finally {
        this.isLoadingProducts = false
      }
    },

    async fetchProduct(id: number) {
      const existingProduct = this.productById(id)

      if (existingProduct) {
        return existingProduct
      }

      const response = await api.get<CatalogResponse<Product>>(`/store/products/${id}`)
      const product = response.data.data
      const index = this.products.findIndex((item) => item.id === product.id)

      if (index >= 0) {
        this.products.splice(index, 1, product)
      } else {
        this.products.push(product)
      }

      return product
    },

    async fetchCategories(force = false) {
      if (this.isLoadingCategories) {
        return
      }

      if (this.hasLoadedCategories && !force) {
        return
      }

      this.isLoadingCategories = true

      try {
        const response = await api.get<CatalogResponse<CatalogCategory[]>>('/store/categories')
        this.categories = response.data.data
        this.syncSuggestedIntents()
        this.hasLoadedCategories = true
      } finally {
        this.isLoadingCategories = false
      }
    },
    setSearchQuery(value: string) {
      this.searchQuery = value
    },
    setIntent(value: SearchIntent | null) {
      this.intent = value
    },
    setFacet<K extends keyof FacetState>(key: K, value: FacetState[K]) {
      this.facets[key] = value
    },
    clearSearch() {
      this.searchQuery = ''
      this.intent = null
      this.facets = {
        category: null,
        type: null,
        inStockOnly: false,
        priceRange: 'all',
      }
      this.searchResults = []
      this.syncSuggestedIntents()
    },
    async searchStorefront() {
      this.isSearching = true

      const params: Record<string, string | number | boolean> = {}

      if (this.searchQuery.trim()) {
        params.q = this.searchQuery.trim()
      }

      if (this.intent) {
        params.intent = this.intent
      }

      if (this.facets.category) {
        params.category = this.facets.category
      }

      if (this.facets.type) {
        params.type = this.facets.type
      }

      if (this.facets.inStockOnly) {
        params.in_stock = true
      }

      if (this.facets.priceRange !== 'all') {
        if (this.facets.priceRange === '0-100000') {
          params.max_price = 100000
        } else if (this.facets.priceRange === '100000-250000') {
          params.min_price = 100000
          params.max_price = 250000
        } else if (this.facets.priceRange === '250000+') {
          params.min_price = 250000
        }
      }

      try {
        const response = await api.get<CatalogResponse<Product[]> & {
          meta?: { intents?: string[] }
        }>('/store/search', { params })
        this.searchResults = response.data.data
        this.syncSuggestedIntents()
      } catch {
        const q = this.searchQuery.trim().toLowerCase()
        const semanticMap: Record<string, string[]> = {
          audio: ['parlante', 'speaker', 'auricular', 'headphone', 'bambu'],
          carga: ['cargador', 'wireless', 'carga', 'panel', 'energia'],
          accesorios: ['organizador', 'base', 'soporte', 'desk', 'stand'],
          eco: ['eco', 'sustentable', 'bambu', 'solar', 'bio'],
        }

        const intents = this.intent ? semanticMap[this.intent.toLowerCase()] ?? [] : []
        const queryTokens = q ? q.split(/\s+/g) : []

        const scored = this.products
          .map((product) => {
            const haystack = `${product.name} ${product.slug} ${product.category} ${product.description}`.toLowerCase()

            let lexical = 0
            if (q) {
              if (product.slug.toLowerCase() === q || product.variants.some((variant) => variant.sku?.toLowerCase() === q)) {
                lexical = 3
              } else if (product.name.toLowerCase().includes(q)) {
                lexical = 2.5
              } else if (haystack.includes(q)) {
                lexical = 1.2
              }
            } else {
              lexical = 1
            }

            const semanticHits = [...queryTokens, ...intents].filter((token) => token && haystack.includes(token)).length
            const semantic = Math.min(1.8, semanticHits * 0.45)
            const score = lexical * 0.7 + semantic * 0.3

            return { product, score }
          })
          .filter((item) => item.score > 0)
          .sort((a, b) => b.score - a.score)
          .map((item) => item.product)

        this.searchResults = this.applyFacetFilters(scored)
        this.syncSuggestedIntents()
      } finally {
        this.isSearching = false
      }
    },
    async fetchSocialProof(force = false) {
      if (this.isLoadingSocialProof) {
        return
      }

      if (this.socialProof && !force) {
        return
      }

      this.isLoadingSocialProof = true
      try {
        const response = await api.get<{ data: SocialProofBlock }>('/store/social-proof')
        this.socialProof = response.data.data
      } finally {
        this.isLoadingSocialProof = false
      }
    },

    async hydrateStorefront() {
      await Promise.all([
        this.fetchProducts(),
        this.fetchCategories(),
        this.fetchSocialProof(),
      ])
    },
    applyFacetFilters(products: Product[]) {
      return products.filter((product) => {
        if (this.facets.category && product.category !== this.facets.category) {
          return false
        }

        if (this.facets.type && product.type !== this.facets.type) {
          return false
        }

        if (this.facets.inStockOnly && product.type !== 'preorder' && product.stock <= 0) {
          return false
        }

        if (this.facets.priceRange === '0-100000' && product.price > 100000) {
          return false
        }

        if (this.facets.priceRange === '100000-250000' && (product.price < 100000 || product.price > 250000)) {
          return false
        }

        if (this.facets.priceRange === '250000+' && product.price < 250000) {
          return false
        }

        return true
      })
    },
  },
})
