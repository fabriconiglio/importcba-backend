# SEO Implementation - Next.js Frontend

## Descripción

Implementación completa de SEO y metadatos dinámicos para el ecommerce, incluyendo Open Graph, Twitter Cards, Structured Data y optimización para motores de búsqueda.

## Características Implementadas

### **🎯 Metadatos Dinámicos**
- ✅ **Títulos dinámicos** por página y contenido
- ✅ **Descripciones optimizadas** con límite de caracteres
- ✅ **Keywords específicas** por tipo de contenido
- ✅ **Open Graph** completo para redes sociales
- ✅ **Twitter Cards** optimizadas
- ✅ **Canonical URLs** para evitar contenido duplicado

### **📊 Structured Data (JSON-LD)**
- ✅ **Product Schema** con precios, ofertas y reviews
- ✅ **CollectionPage Schema** para categorías
- ✅ **Brand Schema** para marcas
- ✅ **BreadcrumbList Schema** para navegación
- ✅ **FAQ Schema** para preguntas frecuentes

### **🔍 Optimización SEO**
- ✅ **Meta robots** configurados
- ✅ **Google verification** integrado
- ✅ **Sitemap** automático (preparado)
- ✅ **RSS feeds** (preparado)
- ✅ **Performance** optimizado

## Archivos Implementados

### **1. Utilidades SEO** (`lib/seo.ts`)

**Configuración base:**
```typescript
export const SITE_CONFIG = {
  name: 'Import Mayorista',
  description: 'Tienda online de productos para el hogar y cocina...',
  url: 'https://importmayorista.com',
  ogImage: '/images/logo/logo-import.png',
  keywords: 'import mayorista, productos hogar, bazar, cocina...'
}
```

**Funciones principales:**
- `generateBaseMetadata()` - Metadatos base del sitio
- `generateProductMetadata()` - Metadatos de productos
- `generateCategoryMetadata()` - Metadatos de categorías
- `generateBrandMetadata()` - Metadatos de marcas
- `generateCatalogMetadata()` - Metadatos de catálogo
- `generateBreadcrumbsData()` - Structured data breadcrumbs
- `generateFAQData()` - Structured data FAQ

### **2. Layout Principal** (`app/layout.tsx`)

**Metadatos base aplicados:**
```typescript
export const metadata: Metadata = generateBaseMetadata()
```

**Características:**
- Template de títulos dinámicos
- Configuración de robots
- Open Graph base
- Twitter Cards base
- Verificación de Google

### **3. Página de Inicio** (`app/page.tsx`)

**Metadatos específicos:**
```typescript
export const metadata: Metadata = generateCatalogMetadata()
```

### **4. Página de Producto** (`app/producto/[id]/page.tsx`)

**Metadatos dinámicos:**
```typescript
export async function generateMetadata({ params }: ProductDetailPageProps): Promise<Metadata> {
  // Fetch product data from API
  const response = await fetch(`/api/v1/products/${resolvedParams.id}`)
  const product = data.data
  
  // Transform to SEO format
  const productSEO: ProductSEO = {
    id: product.id,
    name: product.name,
    description: product.description,
    price: product.effective_price,
    originalPrice: product.original_price,
    images: product.images,
    category: product.category,
    brand: product.brand,
    slug: product.slug
  }
  
  return generateProductMetadata(productSEO)
}
```

**Características:**
- ✅ **ISR** con revalidación cada 10 minutos
- ✅ **generateStaticParams** para productos populares
- ✅ **Structured data** de producto automático
- ✅ **Open Graph** con imágenes del producto
- ✅ **Precios** en metadatos para comparadores

### **5. Página de Categoría** (`app/categoria/[slug]/page.tsx`)

**Metadatos dinámicos:**
```typescript
export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  // Fetch category data from API
  const response = await fetch(`/api/v1/categories/${resolvedParams.slug}`)
  const category = data.data
  
  const categorySEO: CategorySEO = {
    id: category.id,
    name: category.name,
    description: category.description,
    slug: category.slug,
    image: category.image,
    productCount: category.products_count
  }
  
  return generateCategoryMetadata(categorySEO)
}
```

**Características:**
- ✅ **ISR** con revalidación cada 30 minutos
- ✅ **generateStaticParams** para categorías populares
- ✅ **CollectionPage Schema** automático
- ✅ **Breadcrumbs** structured data

### **6. Página de Catálogo** (`app/catalogo/page.tsx`)

**Metadatos con filtros:**
```typescript
export async function generateMetadata({ searchParams }: CatalogPageProps): Promise<Metadata> {
  const filters = {
    category: searchParams.category,
    brand: searchParams.brand,
    search: searchParams.search,
    page: searchParams.page ? parseInt(searchParams.page) : undefined
  }
  
  return generateCatalogMetadata(filters)
}
```

**Características:**
- ✅ **Títulos dinámicos** según filtros
- ✅ **Descripciones contextuales**
- ✅ **Paginación** en metadatos

### **7. Componente Structured Data** (`components/structured-data.tsx`)

**Uso:**
```typescript
// Breadcrumbs
<BreadcrumbsStructuredData breadcrumbs={[
  { name: 'Inicio', url: '/' },
  { name: 'Categoría', url: '/categoria/bazar' },
  { name: 'Producto', url: '/producto/123' }
]} />

// FAQ
<FAQStructuredData faqs={[
  { question: '¿Cómo comprar?', answer: 'Puedes comprar online...' },
  { question: '¿Envío gratis?', answer: 'Sí, en compras mayores a...' }
]} />
```

## Tipos de Metadatos Generados

### **📄 Producto**
```html
<title>Set de Tazas x6 - Diseño Moderno | Import Mayorista</title>
<meta name="description" content="Descubre Set de Tazas x6 - Diseño Moderno en Import Mayorista. Precio: $3.500. Envío gratis." />
<meta name="keywords" content="Set de Tazas x6, Bazar, Import Mayorista, comprar, online, mayorista" />

<!-- Open Graph -->
<meta property="og:title" content="Set de Tazas x6 - Diseño Moderno | Import Mayorista" />
<meta property="og:description" content="Descubre Set de Tazas x6 - Diseño Moderno..." />
<meta property="og:type" content="product" />
<meta property="og:image" content="https://importmayorista.com/images/product.jpg" />
<meta property="product:price:amount" content="3500" />
<meta property="product:price:currency" content="ARS" />

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Set de Tazas x6 - Diseño Moderno | Import Mayorista" />
<meta name="twitter:description" content="Descubre Set de Tazas x6..." />
<meta name="twitter:image" content="https://importmayorista.com/images/product.jpg" />

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Set de Tazas x6 - Diseño Moderno",
  "description": "Set de tazas de café...",
  "image": ["https://importmayorista.com/images/product.jpg"],
  "brand": {
    "@type": "Brand",
    "name": "Import Mayorista"
  },
  "category": "Bazar",
  "offers": {
    "@type": "Offer",
    "price": 3500,
    "priceCurrency": "ARS",
    "availability": "https://schema.org/InStock",
    "url": "https://importmayorista.com/producto/123"
  }
}
</script>
```

### **📂 Categoría**
```html
<title>Bazar y Cocina - Productos | Import Mayorista</title>
<meta name="description" content="Productos de Bazar y Cocina. Encuentra las mejores ofertas en bazar y cocina. 156 productos disponibles." />
<meta name="keywords" content="Bazar y Cocina, productos, categoría, comprar, online, mayorista" />

<!-- Open Graph -->
<meta property="og:title" content="Bazar y Cocina - Productos | Import Mayorista" />
<meta property="og:description" content="Productos de Bazar y Cocina..." />
<meta property="og:type" content="website" />
<meta property="og:url" content="https://importmayorista.com/categoria/bazar-cocina" />

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Bazar y Cocina",
  "description": "Productos de Bazar y Cocina...",
  "url": "https://importmayorista.com/categoria/bazar-cocina",
  "numberOfItems": 156
}
</script>
```

### **🏷️ Catálogo con Filtros**
```html
<title>Catálogo - Bazar | Import Mayorista</title>
<meta name="description" content="Productos de Bazar. Encuentra las mejores ofertas en bazar." />

<!-- Con búsqueda -->
<title>Búsqueda: tazas | Import Mayorista</title>
<meta name="description" content="Resultados de búsqueda para 'tazas'. Encuentra los productos que buscas." />

<!-- Con paginación -->
<title>Catálogo - Bazar - Página 2 | Import Mayorista</title>
```

## Configuración de Variables de Entorno

### **Frontend (.env.local)**
```env
# SEO Configuration
NEXT_PUBLIC_SITE_URL=https://importmayorista.com
NEXT_PUBLIC_GOOGLE_VERIFICATION=your_google_verification_code

# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

## Performance y Optimización

### **🚀 Métricas SEO**
- **First Contentful Paint**: < 1.5s
- **Largest Contentful Paint**: < 2.5s
- **Cumulative Layout Shift**: < 0.1
- **First Input Delay**: < 100ms

### **📈 Optimizaciones**
- **ISR** para páginas dinámicas
- **generateStaticParams** para pre-renderizado
- **Lazy loading** de imágenes
- **Compresión** de metadatos
- **Cache** de datos SEO

### **🔍 Crawlability**
- **Sitemap.xml** automático (preparado)
- **Robots.txt** optimizado
- **Canonical URLs** para evitar duplicados
- **Meta robots** configurados

## Testing SEO

### **🧪 Herramientas de Testing**
```bash
# Lighthouse SEO Audit
npm run lighthouse

# Meta tags validation
npm run test:seo

# Structured data validation
npm run test:structured-data
```

### **📊 Métricas a Monitorear**
1. **Core Web Vitals**
2. **Search Console** performance
3. **Google Analytics** organic traffic
4. **PageSpeed Insights** scores
5. **Structured Data** validation

## Próximas Mejoras

### **🔮 Funcionalidades Futuras**
1. **Sitemap automático** con API
2. **RSS feeds** para productos
3. **AMP pages** para móviles
4. **PWA** con service worker
5. **Analytics** avanzado

### **📱 Mobile SEO**
1. **Mobile-first** indexing
2. **Progressive Web App**
3. **App Store** optimization
4. **Deep linking** setup

### **🌍 Internacionalización**
1. **Multi-language** support
2. **hreflang** tags
3. **Currency** switching
4. **Regional** content

## Troubleshooting

### **❌ Problemas Comunes**

**1. Metadatos no se actualizan:**
```typescript
// Verificar revalidación
export const revalidate = 600 // 10 minutos

// Verificar ISR
export async function generateStaticParams() {
  // Asegurar que se generen páginas estáticas
}
```

**2. Structured Data no válido:**
```typescript
// Validar con Google Rich Results Test
// https://search.google.com/test/rich-results

// Verificar formato JSON-LD
<script type="application/ld+json">
  // JSON válido sin comentarios
</script>
```

**3. Open Graph no funciona:**
```typescript
// Verificar metadataBase
metadataBase: new URL(SITE_CONFIG.url)

// Verificar URLs absolutas
images: [
  {
    url: `${SITE_CONFIG.url}/images/product.jpg`,
    width: 800,
    height: 600,
  }
]
```

### **🔧 Debug SEO**
```typescript
// Habilitar logs de debug
const DEBUG_SEO = process.env.NODE_ENV === 'development'

if (DEBUG_SEO) {
  console.log('SEO Debug:', {
    title,
    description,
    openGraph,
    structuredData
  })
}
```

---

**Estado**: ✅ Completado  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 