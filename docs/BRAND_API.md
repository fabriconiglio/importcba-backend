# API de Marcas (Brands)

## Descripción
API completa para la gestión de marcas en el sistema de ecommerce.

## Endpoints

### 1. Listar Marcas
**GET** `/api/v1/brands`

Obtiene una lista paginada de todas las marcas.

#### Parámetros de consulta:
- `active` (boolean): Filtrar por estado activo/inactivo
- `search` (string): Buscar por nombre o descripción
- `sort_by` (string): Campo para ordenar (default: 'name')
- `sort_order` (string): Orden ascendente/descendente (default: 'asc')
- `per_page` (integer): Elementos por página (default: 15)

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "0198a0db-5b54-7232-9fc7-f2b7d514cfac",
        "name": "Apple",
        "slug": "apple",
        "logo_url": "https://via.placeholder.com/150x50/000000/FFFFFF?text=Apple",
        "description": "Tecnología innovadora y diseño premium",
        "is_active": true,
        "created_at": "2025-08-13T00:36:27.000000Z",
        "updated_at": "2025-08-13T00:36:27.000000Z"
      }
    ],
    "meta": {
      "total": 11,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    }
  },
  "message": "Marcas obtenidas correctamente"
}
```

### 2. Obtener Marca Específica
**GET** `/api/v1/brands/{id}`

Obtiene una marca específica por su ID.

#### Parámetros:
- `id` (string): UUID de la marca

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "id": "0198a0db-5b54-7232-9fc7-f2b7d514cfac",
    "name": "Apple",
    "slug": "apple",
    "logo_url": "https://via.placeholder.com/150x50/000000/FFFFFF?text=Apple",
    "description": "Tecnología innovadora y diseño premium",
    "is_active": true,
    "products_count": 0,
    "created_at": "2025-08-13T00:36:27.000000Z",
    "updated_at": "2025-08-13T00:36:27.000000Z"
  },
  "message": "Marca obtenida correctamente"
}
```

### 3. Crear Marca
**POST** `/api/v1/brands`

Crea una nueva marca.

#### Cuerpo de la petición:
```json
{
  "name": "Nueva Marca",
  "description": "Descripción de la marca",
  "logo_url": "https://ejemplo.com/logo.png",
  "is_active": true
}
```

#### Validaciones:
- `name`: Requerido, string, máximo 255 caracteres, único
- `description`: Opcional, string, máximo 1000 caracteres
- `logo_url`: Opcional, URL válida, máximo 500 caracteres
- `is_active`: Opcional, boolean

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": {
    "id": "0198a0db-5b54-7232-9fc7-f2b7d514cfac",
    "name": "Nueva Marca",
    "slug": "nueva-marca",
    "logo_url": "https://ejemplo.com/logo.png",
    "description": "Descripción de la marca",
    "is_active": true,
    "created_at": "2025-08-13T00:36:27.000000Z",
    "updated_at": "2025-08-13T00:36:27.000000Z"
  },
  "message": "Marca creada correctamente"
}
```

### 4. Actualizar Marca
**PUT** `/api/v1/brands/{id}`

Actualiza una marca existente.

#### Parámetros:
- `id` (string): UUID de la marca

#### Cuerpo de la petición:
```json
{
  "name": "Marca Actualizada",
  "description": "Nueva descripción",
  "logo_url": "https://ejemplo.com/nuevo-logo.png",
  "is_active": false
}
```

#### Validaciones:
- `name`: Opcional, string, máximo 255 caracteres, único (excluyendo la marca actual)
- `description`: Opcional, string, máximo 1000 caracteres
- `logo_url`: Opcional, URL válida, máximo 500 caracteres
- `is_active`: Opcional, boolean

### 5. Eliminar Marca
**DELETE** `/api/v1/brands/{id}`

Elimina una marca (solo si no tiene productos asociados).

#### Parámetros:
- `id` (string): UUID de la marca

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "message": "Marca eliminada correctamente"
}
```

### 6. Listar Marcas Activas
**GET** `/api/v1/brands/active/list`

Obtiene solo las marcas activas (para el frontend).

#### Ejemplo de respuesta:
```json
{
  "success": true,
  "data": [
    {
      "id": "0198a0db-5b54-7232-9fc7-f2b7d514cfac",
      "name": "Apple",
      "slug": "apple",
      "logo_url": "https://via.placeholder.com/150x50/000000/FFFFFF?text=Apple",
      "description": "Tecnología innovadora y diseño premium",
      "is_active": true,
      "created_at": "2025-08-13T00:36:27.000000Z",
      "updated_at": "2025-08-13T00:36:27.000000Z"
    }
  ],
  "message": "Marcas activas obtenidas correctamente"
}
```

### 7. Obtener Marca por Slug
**GET** `/api/v1/brands/slug/{slug}`

Obtiene una marca activa por su slug.

#### Parámetros:
- `slug` (string): Slug de la marca

## Códigos de Estado HTTP

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `404`: Marca no encontrada
- `422`: Error de validación
- `500`: Error interno del servidor

## Características

- ✅ **Validación completa** de datos de entrada
- ✅ **Manejo de errores** con mensajes descriptivos
- ✅ **Paginación** automática
- ✅ **Filtros** por estado y búsqueda
- ✅ **Ordenamiento** personalizable
- ✅ **Recursos API** para formato consistente
- ✅ **UUID** como identificadores
- ✅ **Slugs automáticos** basados en el nombre
- ✅ **Protección** contra eliminación de marcas con productos
- ✅ **Relaciones** con productos cargadas cuando es necesario

## Ejemplos de Uso

### Crear una marca
```bash
curl -X POST http://127.0.0.1:8000/api/v1/brands \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nike",
    "description": "Calzado deportivo de alta calidad",
    "logo_url": "https://ejemplo.com/nike-logo.png",
    "is_active": true
  }'
```

### Buscar marcas
```bash
curl "http://127.0.0.1:8000/api/v1/brands?search=Apple&active=true&sort_by=name&sort_order=asc"
```

### Obtener marcas activas
```bash
curl "http://127.0.0.1:8000/api/v1/brands/active/list"
``` 