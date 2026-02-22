# SAK-Commerce API Plan

This document outlines the plan for the REST API that will power the SAK-Commerce application. The backend will be built with Laravel.

## 1. Products

### 1.1. Get All Products

- **Endpoint:** `GET /api/products`
- **Description:** Retrieves a list of all available products. Can be filtered by category.
- **Query Parameters:**
  - `category` (optional): Filters products by the specified category name (e.g., `?category=Ropa%20Hombre`).
- **Success Response (200 OK):**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Basic Tee",
      "category": "Ropa Hombre",
      "href": "/product/1",
      "price": 35.0,
      "imageSrc": "https://tailwindui.com/img/ecommerce-images/product-page-01-related-product-01.jpg",
      "imageAlt": "Front of men's Basic Tee in black."
    },
    {
      "id": 15,
      "name": "Wireless Headphones",
      "category": "Tecnologia",
      "href": "/product/15",
      "price": 199.0,
      "imageSrc": "https://tailwindui.com/img/ecommerce-images/category-page-04-image-card-01.jpg",
      "imageAlt": "Wireless headphones on a stand."
    },
    {
      "id": 2,
      "name": "Nomad Tumbler",
      "category": "Hogar",
      "href": "/product/2",
      "price": 35.0,
      "imageSrc": "https://tailwindcss.com/img/ecommerce-images/category-page-04-image-card-02.jpg",
      "imageAlt": "Insulated tumbler."
    }
  ],
  "links": {
    "first": "http://sak-commerce.test/api/products?page=1",
    "last": "http://sak-commerce.test/api/products?page=8",
    "prev": null,
    "next": "http://sak-commerce.test/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 8,
    "path": "http://sak-commerce.test/api/products",
    "per_page": 3,
    "to": 3,
    "total": 24
  }
}
```

### 1.2. Get a Single Product

- **Endpoint:** `GET /api/products/{id}`
- **Description:** Retrieves the full details for a single product.
- **URL Parameters:**
  - `id` (required): The ID of the product to retrieve.
- **Success Response (200 OK):**

```json
{
  "data": {
    "id": 1,
    "name": "Basic Tee",
    "category": "Ropa Hombre",
    "href": "/product/1",
    "price": "35.00",
    "description": "A classic tee made from ultra-soft 100% cotton. Perfect for everyday wear.",
    "breadcrumbs": [
      { "id": 1, "name": "Hombre", "href": "#" },
      { "id": 2, "name": "Ropa", "href": "#" }
    ],
    "images": [
      {
        "id": 1,
        "src": "https://tailwindui.com/img/ecommerce-images/product-page-01-related-product-01.jpg",
        "alt": "Front of men's Basic Tee in black."
      },
      {
        "id": 2,
        "src": "https://tailwindui.com/img/ecommerce-images/product-page-02-tertiary-product-shot-01.jpg",
        "alt": "Model wearing plain black basic tee."
      }
    ],
    "colors": [
      { "name": "Black", "class": "bg-gray-900", "selectedClass": "ring-gray-900" },
      { "name": "White", "class": "bg-white", "selectedClass": "ring-gray-400" }
    ],
    "sizes": [
      { "name": "S", "inStock": true },
      { "name": "M", "inStock": true },
      { "name": "L", "inStock": true }
    ],
    "highlights": [
      "Hand cut and sewn locally",
      "Ultra-soft 100% cotton"
    ],
    "details": "The perfect everyday tee."
  }
}
```

- **Error Response (404 Not Found):**

```json
{
  "message": "Producto no encontrado."
}
```

## 2. Orders (Future)

- **Endpoint:** `POST /api/orders`
- **Description:** Creates a new order from the items in the cart.
- **Request Body:**

```json
{
  "cart": [
    {
      "productId": 1,
      "variantId": "1-Black-S",
      "quantity": 2
    },
    {
      "productId": 15,
      "variantId": "15-Space Gray-One Size",
      "quantity": 1
    }
  ],
  "customerDetails": {
    "name": "Sergio R.",
    "email": "sergio@example.com",
    "address": "123 Fake St"
  }
}
```