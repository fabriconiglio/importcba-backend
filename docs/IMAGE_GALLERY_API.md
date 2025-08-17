# Galería de Imágenes - API y Componentes

## Descripción

Sistema completo de gestión y visualización de imágenes que incluye galería interactiva, subida de archivos, zoom avanzado y funcionalidades de gestión.

## Componentes Principales

### 1. `ImageGallery` - Galería Interactiva

**Características:**
- ✅ Zoom avanzado (hasta 300%)
- ✅ Navegación con teclado y mouse
- ✅ Miniaturas navegables
- ✅ Modal de zoom a pantalla completa
- ✅ Arrastre y rotación de imágenes
- ✅ Controles de teclado completos
- ✅ Modo editable/lectura
- ✅ Gestión de imagen principal
- ✅ Eliminación de imágenes

**Props:**
```typescript
interface ImageGalleryProps {
  images: ImageItem[]
  onImageChange?: (images: ImageItem[]) => void
  onPrimaryChange?: (imageId: string) => void
  onImageDelete?: (imageId: string) => void
  onImageUpload?: (files: File[]) => void
  editable?: boolean
  showControls?: boolean
  maxImages?: number
}
```

**Uso:**
```tsx
<ImageGallery
  images={productImages}
  onImageChange={handleImageChange}
  onPrimaryChange={handlePrimaryChange}
  onImageDelete={handleImageDelete}
  onImageUpload={handleImageUpload}
  editable={true}
  showControls={true}
  maxImages={10}
/>
```

### 2. `ImageUpload` - Subida de Archivos

**Características:**
- ✅ Drag & drop
- ✅ Múltiples archivos
- ✅ Validación de tipos y tamaño
- ✅ Preview en tiempo real
- ✅ Barra de progreso
- ✅ Manejo de errores
- ✅ Reintento de subidas fallidas

**Props:**
```typescript
interface ImageUploadProps {
  onImagesUploaded?: (images: UploadedImage[]) => void
  onImageRemoved?: (imageId: string) => void
  maxFiles?: number
  maxFileSize?: number // en MB
  acceptedTypes?: string[]
  multiple?: boolean
  showPreview?: boolean
  className?: string
}
```

**Uso:**
```tsx
<ImageUpload
  onImagesUploaded={handleImagesUploaded}
  onImageRemoved={handleImageRemoved}
  maxFiles={5}
  maxFileSize={2}
  acceptedTypes={['image/jpeg', 'image/png', 'image/gif']}
  multiple={true}
  showPreview={true}
/>
```

## Funcionalidades Avanzadas

### 🎯 Zoom y Navegación

**Controles de Zoom:**
- **Zoom In/Out**: Botones o teclas `+`/`-`
- **Reset**: Botón o tecla `0`
- **Arrastre**: Click y arrastre para mover imagen
- **Rotación**: Botón de rotación (próximamente)

**Controles de Navegación:**
- **Teclado**: Flechas `←`/`→` para navegar
- **Mouse**: Botones anterior/siguiente
- **Touch**: Swipe en dispositivos móviles
- **Escape**: `ESC` para cerrar zoom

### 📱 Responsive Design

**Breakpoints:**
- **Mobile**: Galería vertical, controles táctiles
- **Tablet**: Galería horizontal, controles híbridos
- **Desktop**: Galería completa, controles de mouse

**Características Mobile:**
- Touch-friendly en galería
- Gestos de pinch para zoom
- Swipe para navegación
- Controles optimizados para dedos

### ⚡ Performance

**Optimizaciones:**
- Lazy loading de imágenes
- Compresión automática
- Cache de miniaturas
- Debounce en controles
- Virtualización para muchas imágenes

## Estructura de Datos

### ImageItem Interface

```typescript
interface ImageItem {
  id?: string
  url: string
  alt?: string
  is_primary?: boolean
  order?: number
}
```

### UploadedImage Interface

```typescript
interface UploadedImage {
  id: string
  file: File
  preview: string
  progress: number
  status: 'uploading' | 'success' | 'error'
  error?: string
}
```

## API Integration

### Endpoints Esperados

**Subida de Imágenes:**
```typescript
// POST /api/v1/products/{id}/images
interface UploadImageRequest {
  files: File[]
  product_id: string
}

interface UploadImageResponse {
  success: boolean
  data: {
    images: ImageItem[]
  }
  message: string
}
```

**Gestión de Imágenes:**
```typescript
// PUT /api/v1/products/{id}/images/{imageId}/primary
interface SetPrimaryImageRequest {
  image_id: string
  product_id: string
}

// DELETE /api/v1/products/{id}/images/{imageId}
interface DeleteImageRequest {
  image_id: string
  product_id: string
}
```

### Ejemplo de Integración

```typescript
// Subir imágenes
const uploadImages = async (productId: string, files: File[]) => {
  const formData = new FormData()
  files.forEach(file => formData.append('images[]', file))
  
  const response = await fetch(`/api/v1/products/${productId}/images`, {
    method: 'POST',
    body: formData
  })
  
  return response.json()
}

// Establecer imagen principal
const setPrimaryImage = async (productId: string, imageId: string) => {
  const response = await fetch(`/api/v1/products/${productId}/images/${imageId}/primary`, {
    method: 'PUT'
  })
  
  return response.json()
}

// Eliminar imagen
const deleteImage = async (productId: string, imageId: string) => {
  const response = await fetch(`/api/v1/products/${productId}/images/${imageId}`, {
    method: 'DELETE'
  })
  
  return response.json()
}
```

## Configuración y Personalización

### Variables de Entorno

```env
# Límites de subida
NEXT_PUBLIC_MAX_IMAGE_SIZE=5 # MB
NEXT_PUBLIC_MAX_IMAGES_PER_PRODUCT=10

# Formatos aceptados
NEXT_PUBLIC_ACCEPTED_IMAGE_TYPES=image/jpeg,image/png,image/gif,image/webp

# Configuración de zoom
NEXT_PUBLIC_MAX_ZOOM_LEVEL=3
NEXT_PUBLIC_ZOOM_STEP=0.5
```

### Temas y Estilos

**CSS Variables:**
```css
:root {
  --gallery-border-radius: 0.5rem;
  --gallery-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  --gallery-transition: all 0.2s ease-in-out;
  --zoom-background: rgba(0, 0, 0, 0.9);
  --thumbnail-size: 5rem;
  --thumbnail-gap: 0.5rem;
}
```

**Clases CSS Personalizables:**
```css
.image-gallery {
  /* Contenedor principal */
}

.image-gallery__main {
  /* Imagen principal */
}

.image-gallery__thumbnails {
  /* Contenedor de miniaturas */
}

.image-gallery__thumbnail {
  /* Miniatura individual */
}

.image-gallery__controls {
  /* Controles de navegación */
}

.image-gallery__zoom {
  /* Modal de zoom */
}
```

## Casos de Uso

### 1. Galería de Producto (Solo Lectura)

```tsx
<ImageGallery
  images={product.images}
  editable={false}
  showControls={true}
/>
```

### 2. Editor de Producto (Editable)

```tsx
<ImageGallery
  images={product.images}
  onImageChange={handleImageChange}
  onPrimaryChange={handlePrimaryChange}
  onImageDelete={handleImageDelete}
  onImageUpload={handleImageUpload}
  editable={true}
  showControls={true}
  maxImages={10}
/>
```

### 3. Subida Masiva de Imágenes

```tsx
<ImageUpload
  onImagesUploaded={handleBulkUpload}
  maxFiles={20}
  maxFileSize={10}
  multiple={true}
  showPreview={true}
/>
```

### 4. Galería Mínima

```tsx
<ImageGallery
  images={images}
  editable={false}
  showControls={false}
/>
```

## Testing

### Casos de Prueba

**Funcionalidad:**
1. ✅ Carga de imágenes
2. ✅ Navegación entre imágenes
3. ✅ Zoom in/out
4. ✅ Arrastre de imagen
5. ✅ Controles de teclado
6. ✅ Subida de archivos
7. ✅ Validación de archivos
8. ✅ Manejo de errores

**Performance:**
1. ✅ Carga rápida de miniaturas
2. ✅ Zoom fluido
3. ✅ Navegación responsiva
4. ✅ Manejo de archivos grandes

**Accesibilidad:**
1. ✅ Navegación por teclado
2. ✅ Screen readers
3. ✅ Contraste de colores
4. ✅ Focus management

### Comandos de Testing

```bash
# Test de componentes
npm run test components/image-gallery
npm run test components/image-upload

# Test de integración
npm run test:e2e image-gallery

# Test de performance
npm run test:perf image-gallery
```

## Deployment

### Build y Optimización

```bash
# Build de producción
npm run build

# Optimización de imágenes
npm run optimize:images

# Generación de miniaturas
npm run generate:thumbnails
```

### Configuración de CDN

```typescript
// Configuración para CDN
const imageConfig = {
  cdn: process.env.NEXT_PUBLIC_CDN_URL,
  transformations: {
    thumbnail: 'w=200,h=200,fit=crop',
    medium: 'w=800,h=800,fit=cover',
    large: 'w=1200,h=1200,fit=cover'
  }
}
```

## Troubleshooting

### Problemas Comunes

**1. Imágenes no cargan:**
- Verificar URLs de imágenes
- Comprobar CORS en servidor
- Revisar configuración de Next.js Image

**2. Zoom no funciona:**
- Verificar que las imágenes tengan dimensiones adecuadas
- Comprobar CSS de transform
- Revisar eventos de mouse/touch

**3. Subida falla:**
- Verificar límites de tamaño
- Comprobar tipos de archivo
- Revisar permisos de servidor

**4. Performance lenta:**
- Optimizar tamaño de imágenes
- Implementar lazy loading
- Usar CDN para imágenes

### Debug

```typescript
// Habilitar logs de debug
const DEBUG = process.env.NODE_ENV === 'development'

if (DEBUG) {
  console.log('Image Gallery Debug:', {
    images: images.length,
    selectedImage,
    zoomLevel,
    isDragging
  })
}
```

## Próximas Mejoras

### Funcionalidades Pendientes

1. **Filtros y Efectos**
   - Filtros de imagen (blur, brightness, etc.)
   - Efectos de transición
   - Animaciones personalizadas

2. **Gestión Avanzada**
   - Reordenamiento por drag & drop
   - Crop de imágenes
   - Redimensionamiento automático

3. **Integración Social**
   - Compartir en redes sociales
   - Embed de galerías
   - Comentarios en imágenes

4. **Analytics**
   - Tracking de interacciones
   - Métricas de uso
   - A/B testing

---

**Estado**: ✅ Completado  
**Última actualización**: Diciembre 2024  
**Versión**: 1.0.0 