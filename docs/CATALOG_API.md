# API de Catálogo SEO-Friendly

## Descripción
API de catálogo con URLs amigables para SEO que permiten navegar productos por categorías y marcas usando slugs.

## Endpoints

### 1. Productos por Categoría
**GET** `/api/v1/catalog/category/{categorySlug}`

Obtiene productos filtrados por categoría usando el slug de la categoría.

#### Parámetros de URL:
- `categorySlug` (string): Slug de la categoría (ej: "electronics", "clothing")

#### Parámetros de consulta:
- `search` (string): Buscar en nombre, descripción o SKU
- `price_min` (number): Precio mínimo
- `price_max` (number): Precio máximo
- `in_stock` (boolean): Solo productos en stock
- `featured` (boolean): Solo productos destacados
- `on_sale` (boolean): Solo productos con descuento
- `sort` (string): Ordenamiento (newest, oldest, price_asc, price_desc, name_asc, name_desc)
- `per_page` (integer): Elementos por página (default: 15)
- `page` (integer): Número de página

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": "0198a0db-5b54-7232-9fc7-f2b7d514cfac",
        "name": "iPhone 15 Pro",
        "slug": "iphone-15-pro",
        "sku": "IPH15PRO-256",
        "description": "El iPhone más avanzado",
        "short_description": "iPhone 15 Pro 256GB",
        "price": 999.99,
        "sale_price": null,
        "effective_price": 999.99,
        "original_price": null,
        "stock_quantity": 10,
        "in_stock": true,
        "low_stock": false,
        "is_featured": true,
        "has_discount": false,
        "discount_percentage": 0,
        "primary_image": "https://example.com/iphone15pro.jpg",
        "images": [
          {
            "id": "1",
            "url": "https://example.com/iphone15pro.jpg",
            "is_primary": true,
            "order": 1
          }
        ],
        "category": {
          "id": "cat-1",
          "name": "Electrónicos",
          "slug": "electronics"
        },
        "brand": {
          "id": "brand-1",
          "name": "Apple",
          "slug": "apple"
        },
        "created_at": "2025-08-13T00:36:27.000000Z",
        "updated_at": "2025-08-13T00:36:27.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1,
      "from": 1,
      "to": 1
    },
    "category": {
      "id": "cat-1",
      "name": "Electrónicos",
      "slug": "electronics",
      "description": "Productos electrónicos de alta calidad",
      "image": "https://example.com/electronics.jpg"
    },
    "filters": {
      "applied": {
        "sort": "newest"
      },
      "available": {
        "price_range": {
          "min": 999.99,
          "max": 999.99
        },
        "total_products": 1,
        "in_stock_count": 1,
        "featured_count": 1,
        "on_sale_count": 0
      }
    }
  },
  "message": "Productos de la categoría 'Electrónicos' obtenidos correctamente"
}
```

### 2. Productos por Marca
**GET** `/api/v1/catalog/brand/{brandSlug}`

Obtiene productos filtrados por marca usando el slug de la marca.

#### Parámetros de URL:
- `brandSlug` (string): Slug de la marca (ej: "apple", "samsung")

#### Parámetros de consulta:
- Mismos parámetros que el endpoint de categoría

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "products": [...],
    "pagination": {...},
    "brand": {
      "id": "brand-1",
      "name": "Apple",
      "slug": "apple",
      "description": "Tecnología innovadora y diseño premium",
      "logo_url": "https://example.com/apple-logo.png"
    },
    "filters": {...}
  },
  "message": "Productos de la marca 'Apple' obtenidos correctamente"
}
```

### 3. Productos por Categoría y Marca
**GET** `/api/v1/catalog/category/{categorySlug}/brand/{brandSlug}`

Obtiene productos filtrados por categoría y marca específicas.

#### Parámetros de URL:
- `categorySlug` (string): Slug de la categoría
- `brandSlug` (string): Slug de la marca

#### Parámetros de consulta:
- Mismos parámetros que los endpoints anteriores

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "products": [...],
    "pagination": {...},
    "category": {
      "id": "cat-1",
      "name": "Electrónicos",
      "slug": "electronics",
      "description": "Productos electrónicos de alta calidad",
      "image": "https://example.com/electronics.jpg"
    },
    "brand": {
      "id": "brand-1",
      "name": "Apple",
      "slug": "apple",
      "description": "Tecnología innovadora y diseño premium",
      "logo_url": "https://example.com/apple-logo.png"
    },
    "filters": {...}
  },
  "message": "Productos de 'Electrónicos' - 'Apple' obtenidos correctamente"
}
```

## Opciones de Ordenamiento

| Valor | Descripción |
|-------|-------------|
| `newest` | Más recientes primero (default) |
| `oldest` | Más antiguos primero |
| `price_asc` | Precio de menor a mayor |
| `price_desc` | Precio de mayor a menor |
| `name_asc` | Nombre A-Z |
| `name_desc` | Nombre Z-A |

## Filtros Disponibles

### Filtros de Búsqueda
- `search`: Busca en nombre, descripción y SKU del producto

### Filtros de Precio
- `price_min`: Precio mínimo (usa precio efectivo: sale_price o price)
- `price_max`: Precio máximo (usa precio efectivo: sale_price o price)

### Filtros de Stock
- `in_stock=true`: Solo productos con stock disponible
- `in_stock=false`: Solo productos sin stock

### Filtros Especiales
- `featured=true`: Solo productos destacados
- `on_sale=true`: Solo productos con descuento

## Características SEO-Friendly

### URLs Amigables
- ✅ **Categorías**: `/api/v1/catalog/category/electronics`
- ✅ **Marcas**: `/api/v1/catalog/brand/apple`
- ✅ **Combinado**: `/api/v1/catalog/category/electronics/brand/apple`

### Metadatos Incluidos
- ✅ **Información de categoría/marca** en cada respuesta
- ✅ **Filtros aplicados** para tracking
- ✅ **Filtros disponibles** para UI dinámica
- ✅ **Rangos de precio** para sliders
- ✅ **Contadores** para badges y estadísticas

### Paginación Completa
- ✅ **Paginación automática** con metadatos
- ✅ **Configuración de elementos por página**
- ✅ **Navegación por páginas**

## Ejemplos de Uso

### Obtener productos de electrónicos
```bash
curl "http://127.0.0.1:8000/api/v1/catalog/category/electronics?sort=price_asc&in_stock=true"
```

### Obtener productos de Apple con descuento
```bash
curl "http://127.0.0.1:8000/api/v1/catalog/brand/apple?on_sale=true&price_max=1000"
```

### Obtener productos de Apple en la categoría de teléfonos
```bash
curl "http://127.0.0.1:8000/api/v1/catalog/category/phones/brand/apple?featured=true"
```

### Buscar productos con filtros múltiples
```bash
curl "http://127.0.0.1:8000/api/v1/catalog/category/electronics?search=wireless&price_min=50&price_max=500&in_stock=true&sort=price_asc"
```

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `404`: Categoría o marca no encontrada
- `500`: Error interno del servidor

## Ventajas SEO

1. **URLs Descriptivas**: Las URLs contienen información relevante para SEO
2. **Estructura Jerárquica**: Permite navegación lógica por categorías y marcas
3. **Metadatos Ricos**: Incluye información de contexto en cada respuesta
4. **Filtros Avanzados**: Permite refinamiento de búsquedas
5. **Paginación Eficiente**: Manejo optimizado de grandes volúmenes de datos
6. **Respuestas Consistentes**: Formato uniforme en todas las respuestas 