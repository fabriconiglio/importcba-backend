U# Actualización Masiva de Productos mediante Excel

## Descripción
Esta funcionalidad permite actualizar múltiples productos de forma masiva mediante archivos Excel, ideal para actualizar precios, stock y otros datos de productos por categoría o marca.

## Cómo usar la funcionalidad

### 1. Acceder al Panel de Productos
- Ingresa al panel administrativo de Filament
- Ve a la sección **Catálogo > Productos**

### 2. Exportar Plantilla Excel

#### Opción A: Descargar Plantilla Completa
1. Haz clic en el botón **"Descargar Plantilla"** (ícono de documento)
2. Se descargará un archivo Excel con todos los productos actuales
3. Este archivo sirve como plantilla con el formato correcto

#### Opción B: Exportar por Filtros
1. Haz clic en el botón **"Exportar Excel"** (ícono de descarga verde)
2. Selecciona filtros opcionales:
   - **Categoría**: Para exportar solo productos de una categoría específica (ej: "Belleza")
   - **Marca**: Para exportar solo productos de una marca específica
3. Haz clic en **"Exportar"**
4. Se descargará un archivo Excel filtrado

### 3. Editar el Archivo Excel

El archivo Excel contiene las siguientes columnas:

| Columna | Descripción | Obligatorio | Notas |
|---------|-------------|-------------|-------|
| **ID** | Identificador único del producto | ✅ Sí | No modificar este valor |
| **Nombre** | Nombre del producto | ❌ No | Se puede actualizar |
| **SKU** | Código único del producto | ❌ No | Se puede actualizar |
| **Descripción** | Descripción completa | ❌ No | Se puede actualizar |
| **Categoría** | Nombre de la categoría | ❌ No | Debe existir en el sistema |
| **Marca** | Nombre de la marca | ❌ No | Debe existir en el sistema |
| **Precio** | Precio regular | ❌ No | Solo números positivos |
| **Precio Oferta** | Precio con descuento | ❌ No | Dejar vacío si no hay oferta |
| **Stock** | Cantidad en inventario | ❌ No | Solo números enteros positivos |
| **Stock Mínimo** | Nivel mínimo de stock | ❌ No | Solo números enteros positivos |
| **Meta Título** | Título para SEO | ❌ No | Se puede actualizar |
| **Imagen Principal** | URL de imagen | ❌ No | Solo lectura (no se actualiza) |
| **Activo** | Estado del producto | ❌ No | "SI" o "NO" |
| **Destacado** | Producto destacado | ❌ No | "SI" o "NO" |

#### Consejos para Editar:
- **No elimines la fila de encabezados**
- **No modifiques la columna ID** - es necesaria para identificar el producto
- **Para precios**: Usa solo números (ej: 1500.50, no $1,500.50)
- **Para campos booleanos**: Usa "SI" o "NO" (no "Sí", "si", "Yes", etc.)
- **Para categorías y marcas**: Usa el nombre exacto como aparece en el sistema

### 4. Importar el Archivo Modificado

1. Haz clic en el botón **"Importar Excel"** (ícono de subida azul)
2. Selecciona tu archivo Excel modificado
3. Haz clic en **"Importar"**
4. El sistema procesará el archivo y mostrará un resumen:
   - ✅ Productos actualizados exitosamente
   - ❌ Errores encontrados (si los hay)

### 5. Revisar Resultados

El sistema mostrará una notificación con:
- **Éxito**: Cantidad de productos actualizados
- **Errores**: Lista de errores específicos por fila
- **Detalles**: Información sobre qué falló y por qué

## Casos de Uso Comunes

### Actualizar Precios por Categoría
1. Exporta productos filtrando por categoría "Belleza"
2. Modifica la columna "Precio" con los nuevos valores
3. Importa el archivo
4. ¡Listo! Todos los productos de belleza tendrán precios actualizados

### Actualizar Stock Masivo
1. Exporta todos los productos o filtra por marca
2. Modifica las columnas "Stock" y "Stock Mínimo"
3. Importa el archivo

### Activar/Desactivar Productos
1. Exporta productos con filtros deseados
2. Cambia la columna "Activo" a "SI" o "NO"
3. Importa el archivo

## Errores Comunes y Soluciones

### Error: "Producto no encontrado"
- **Causa**: El ID del producto no existe o fue modificado
- **Solución**: No modifiques la columna ID, usa la plantilla original

### Error: "Categoría no encontrada"
- **Causa**: El nombre de categoría no coincide exactamente
- **Solución**: Verifica que el nombre sea exacto (mayúsculas, tildes, etc.)

### Error: "Precio debe ser mayor a 0"
- **Causa**: Precio negativo o cero
- **Solución**: Usa solo números positivos para precios

### Error: "Stock no puede ser negativo"
- **Causa**: Valor negativo en stock
- **Solución**: Usa solo números enteros positivos o cero

## Recomendaciones

### Antes de Importar
1. **Haz una copia de seguridad** de tus datos importantes
2. **Prueba con pocos productos** primero
3. **Revisa el formato** de las columnas antes de importar

### Durante la Edición
1. **No elimines filas** de productos que no quieras actualizar
2. **Deja vacías las celdas** que no quieras modificar
3. **Usa formatos consistentes** para fechas y números

### Después de Importar
1. **Revisa los productos actualizados** en el listado
2. **Verifica los precios** en el frontend
3. **Confirma que el stock** se actualizó correctamente

## Soporte Técnico

Si encuentras problemas:
1. Revisa la lista de errores en la notificación
2. Verifica que el formato del Excel sea correcto
3. Contacta al equipo técnico con detalles específicos del error

---

**Nota**: Esta funcionalidad está diseñada para ser segura y eficiente. Solo se actualizarán los campos que contengan datos válidos, el resto permanecerá sin cambios.
