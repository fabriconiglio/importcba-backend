# Integración de Imágenes de Productos

## Resumen

Esta documentación describe la implementación completa del sistema de gestión de imágenes para productos, incluyendo el backend en Laravel y el frontend en Next.js.

## Características Implementadas

### Backend (Laravel)

#### 1. Modelos y Relaciones
- **Product**: Modelo principal con relaciones a imágenes
- **ProductImage**: Modelo para almacenar información de imágenes
- **Relaciones**: Un producto puede tener múltiples imágenes, una imagen principal

#### 2. Base de Datos
```sql
-- Tabla product_images
CREATE TABLE product_images (
    id UUID PRIMARY KEY,
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    small_url VARCHAR(500),
    medium_url VARCHAR(500),
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 3. Controladores
- **ProductImageController**: Maneja operaciones CRUD para imágenes
- **ProductController**: Incluye endpoint para obtener imágenes de un producto

#### 4. Servicios
- **ImageService**: Procesa y optimiza imágenes automáticamente
- **Funcionalidades**:
  - Redimensionamiento automático
  - Generación de thumbnails
  - Optimización de calidad
  - Múltiples tamaños (thumb, small, medium, original)

#### 5. Filament Admin Panel
- **ImagesRelationManager**: Interfaz para gestionar imágenes en Filament
- **Funcionalidades**:
  - Subida de imágenes con drag & drop
  - Establecer imagen principal
  - Reordenamiento
  - Eliminación
  - Vista previa

#### 6. Observers
- **ProductImageObserver**: Maneja automáticamente:
  - Asignación de orden automático
  - Gestión de imagen principal única
  - Limpieza al eliminar

### Frontend (Next.js)

#### 1. Componentes
- **ProductImageManager**: Componente principal para gestión de imágenes
- **Funcionalidades**:
  - Subida por drag & drop
  - Vista previa de imágenes
  - Establecer imagen principal
  - Eliminación
  - Reordenamiento visual

#### 2. Hooks Personalizados
- **useProductImages**: Hook para manejar estado y operaciones de imágenes
- **Funcionalidades**:
  - Carga de imágenes
  - Subida múltiple
  - Eliminación
  - Actualización de orden
  - Manejo de errores

#### 3. Páginas de Administración
- **Gestión de Imágenes**: Página dedicada para administrar imágenes de productos
- **Estadísticas**: Información sobre imágenes del producto
- **Instrucciones**: Guía de uso

## Endpoints de API

### Obtener Imágenes de un Producto
```http
GET /api/v1/products/{id}/images
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "product_id": "uuid",
    "product_name": "Nombre del Producto",
    "images": [
      {
        "id": "uuid",
        "url": "https://example.com/images/product.jpg",
        "thumbnail_url": "https://example.com/images/thumb_product.jpg",
        "small_url": "https://example.com/images/small_product.jpg",
        "medium_url": "https://example.com/images/medium_product.jpg",
        "is_primary": true,
        "alt_text": "Descripción de la imagen",
        "sort_order": 0
      }
    ],
    "total_images": 1
  },
  "message": "Imágenes del producto obtenidas correctamente"
}
```

### Subir Imágenes
```http
POST /api/v1/products/{id}/images
Content-Type: multipart/form-data

images[]: [archivos]
alt_text: "Descripción opcional"
is_primary: false
```

### Establecer Imagen Principal
```http
POST /api/v1/products/{id}/images/{imageId}/primary
```

### Eliminar Imagen
```http
DELETE /api/v1/products/{id}/images/{imageId}
```

### Reordenar Imágenes
```http
POST /api/v1/products/{id}/images/reorder
Content-Type: application/json

{
  "images": [
    {"id": "uuid1", "sort_order": 0},
    {"id": "uuid2", "sort_order": 1}
  ]
}
```

## Configuración

### 1. Storage
Asegúrate de que el disco `public` esté configurado correctamente:

```php
// config/filesystems.php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### 2. Enlaces Simbólicos
Ejecuta el comando para crear enlaces simbólicos:
```bash
php artisan storage:link
```

### 3. Permisos
Asegúrate de que el directorio `storage/app/public` tenga permisos de escritura.

## Uso en Filament

### 1. Acceso a Gestión de Imágenes
1. Ve al panel de administración de Filament
2. Navega a "Productos"
3. Selecciona un producto
4. Ve a la pestaña "Imágenes"

### 2. Funcionalidades Disponibles
- **Agregar imagen**: Haz clic en "Agregar imagen"
- **Establecer principal**: Usa el botón de estrella
- **Eliminar**: Usa el botón de papelera
- **Reordenar**: Arrastra las imágenes para cambiar el orden

## Uso en Frontend

### 1. Componente Básico
```tsx
import ProductImageManager from '@/components/product-image-manager';

function ProductPage({ productId }: { productId: string }) {
  return (
    <ProductImageManager
      productId={productId}
      images={images}
      onImagesChange={handleImagesChange}
      readOnly={false}
    />
  );
}
```

### 2. Hook Personalizado
```tsx
import { useProductImages } from '@/lib/hooks/useProductImages';

function ProductImagesPage({ productId }: { productId: string }) {
  const {
    images,
    loading,
    error,
    uploadImages,
    deleteImage,
    setPrimaryImage,
    refreshImages
  } = useProductImages(productId);

  // Usar las funciones según necesites
}
```

## Optimización de Imágenes

### 1. Tamaños Automáticos
El sistema genera automáticamente:
- **Original**: Hasta 1200px (máximo)
- **Medium**: 600px
- **Small**: 300px
- **Thumbnail**: 150px

### 2. Formatos Soportados
- JPEG
- PNG
- GIF

### 3. Límites
- **Tamaño máximo**: 2MB por imagen
- **Calidad**: 80% para optimización

## Consideraciones de Seguridad

### 1. Validación de Archivos
- Verificación de tipo MIME
- Validación de extensión
- Límite de tamaño
- Sanitización de nombres

### 2. Permisos
- Solo usuarios autenticados pueden subir imágenes
- Validación de propiedad del producto

### 3. Almacenamiento Seguro
- Archivos almacenados fuera del directorio web
- URLs públicas generadas dinámicamente

## Mantenimiento

### 1. Limpieza de Archivos
```bash
# Limpiar imágenes huérfanas
php artisan images:cleanup

# Optimizar imágenes existentes
php artisan images:optimize
```

### 2. Backup
```bash
# Backup de imágenes
php artisan backup:images

# Restaurar imágenes
php artisan restore:images
```

## Troubleshooting

### Problemas Comunes

1. **Error de permisos**
   ```bash
   chmod -R 775 storage/app/public
   ```

2. **Enlace simbólico roto**
   ```bash
   php artisan storage:link
   ```

3. **Imágenes no se cargan**
   - Verificar configuración de storage
   - Revisar permisos de directorio
   - Comprobar enlaces simbólicos

4. **Error de memoria**
   - Aumentar `memory_limit` en php.ini
   - Optimizar tamaño de imágenes antes de subir

## Próximas Mejoras

1. **Compresión WebP**: Soporte para formato WebP
2. **CDN Integration**: Integración con CDN para mejor rendimiento
3. **Watermarks**: Marcas de agua automáticas
4. **Bulk Operations**: Operaciones masivas de imágenes
5. **AI Tagging**: Etiquetado automático con IA
6. **Image Analytics**: Estadísticas de uso de imágenes

## Contribución

Para contribuir a esta funcionalidad:

1. Sigue las convenciones de código establecidas
2. Añade tests para nuevas funcionalidades
3. Actualiza la documentación
4. Verifica la compatibilidad con versiones anteriores

## Licencia

Esta implementación está bajo la misma licencia que el proyecto principal. 