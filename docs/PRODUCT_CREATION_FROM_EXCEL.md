# Creación de Productos desde Excel

## Descripción
Esta funcionalidad permite crear nuevos productos de forma masiva mediante archivos Excel. A diferencia de la funcionalidad de actualización, esta herramienta está diseñada específicamente para agregar productos que no existen en el sistema.

## Cómo usar la funcionalidad

### 1. Acceder al Panel de Productos
- Ingresa al panel administrativo de Filament
- Ve a la sección **Catálogo > Productos**

### 2. Descargar Plantilla para Nuevos Productos

1. Haz clic en el botón **"Plantilla Nuevos Productos"** (ícono de plus con círculo, color azul)
2. Se descargará un archivo Excel con:
   - **Encabezados explicativos** con todos los campos disponibles
   - **3 productos de ejemplo** que muestran diferentes casos de uso
   - **Comentarios en las celdas** con instrucciones específicas
   - **Formato visual** que destaca campos obligatorios en rojo

### 3. Completar el Archivo Excel

El archivo Excel contiene las siguientes columnas:

| Columna | Descripción | Obligatorio | Notas |
|---------|-------------|-------------|-------|
| **NOMBRE (*)** | Nombre del producto | ✅ Sí | Aparecerá en el catálogo |
| **SKU** | Código único del producto | ❌ No | Se genera automático si está vacío |
| **DESCRIPCIÓN** | Descripción completa | ❌ No | Recomendado para SEO |
| **DESCRIPCIÓN CORTA** | Descripción breve | ❌ No | Para listados de productos |
| **CATEGORÍA** | Nombre de la categoría | ❌ No | Debe existir en el sistema* |
| **MARCA** | Nombre de la marca | ❌ No | Debe existir en el sistema |
| **PRECIO (*)** | Precio regular | ✅ Sí | Solo números positivos |
| **PRECIO OFERTA** | Precio con descuento | ❌ No | Debe ser menor al precio regular |
| **STOCK** | Cantidad en inventario | ❌ No | Por defecto: 0 |
| **STOCK MÍNIMO** | Nivel mínimo de stock | ❌ No | Por defecto: 0 |
| **META TÍTULO** | Título para SEO | ❌ No | Se usa el nombre si está vacío |
| **META DESCRIPCIÓN** | Descripción para SEO | ❌ No | Se genera automática si está vacía |
| **PESO (KG)** | Peso del producto | ❌ No | Para cálculo de envíos |
| **LARGO (CM)** | Longitud del producto | ❌ No | Para cálculo de envíos |
| **ANCHO (CM)** | Ancho del producto | ❌ No | Para cálculo de envíos |
| **ALTO (CM)** | Altura del producto | ❌ No | Para cálculo de envíos |
| **ACTIVO** | Estado del producto | ❌ No | "SI" o "NO" (por defecto: SI) |
| **DESTACADO** | Producto destacado | ❌ No | "SI" o "NO" (por defecto: NO) |

*\*Si no especificas categoría, se asignará automáticamente a "General" si existe.*

#### Consejos para Completar:
- **Campos obligatorios**: Solo NOMBRE y PRECIO son obligatorios
- **SKU automático**: Si no proporcionas SKU, se generará basado en el nombre del producto
- **Slug automático**: Se genera automáticamente basado en el nombre
- **Para precios**: Usa solo números (ej: 1500.50, no $1,500.50)
- **Para campos booleanos**: Usa exactamente "SI" o "NO"
- **Para categorías y marcas**: Usa el nombre exacto como aparece en el sistema
- **Deja vacías** las celdas que no quieras especificar

### 4. Importar el Archivo

1. Elimina las filas de ejemplo del archivo o reemplázalas con tus productos reales
2. Haz clic en el botón **"Crear Productos desde Excel"** (ícono de plus, color naranja)
3. Selecciona tu archivo Excel completado
4. Haz clic en **"Importar"**
5. El sistema procesará el archivo y mostrará un resumen detallado

### 5. Revisar Resultados

El sistema mostrará una notificación detallada con:
- **✅ Productos creados exitosamente** con lista de nombres, SKUs y precios
- **❌ Errores encontrados** (si los hay) con detalles específicos por fila
- **Lista de productos creados** para verificación rápida

## Casos de Uso Comunes

### Agregar Catálogo Completo de un Proveedor
1. Descarga la plantilla
2. Completa todos los productos del proveedor
3. Asigna las categorías y marcas correspondientes
4. Importa el archivo
5. ¡Todos los productos se crearán automáticamente!

### Lanzamiento de Nueva Línea de Productos
1. Descarga la plantilla
2. Agrega los productos de la nueva línea
3. Marca algunos como "destacados" (SI)
4. Configura precios de lanzamiento en "precio_oferta"
5. Importa y lanza la nueva línea

### Migración desde Otro Sistema
1. Exporta productos desde tu sistema anterior
2. Adapta el formato a la plantilla de Import Mayorista
3. Mapea categorías y marcas a las existentes en el sistema
4. Importa por lotes para facilitar la revisión

## Validaciones y Reglas de Negocio

### Validaciones Automáticas
- **Precios positivos**: No se permiten precios menores o iguales a 0
- **Precio de oferta válido**: Debe ser menor al precio regular
- **Stock no negativo**: No se permite stock negativo
- **SKU único**: No se pueden crear productos con SKUs duplicados
- **Slug único**: Se genera automáticamente un slug único
- **Categorías existentes**: Deben existir previamente en el sistema
- **Marcas existentes**: Deben existir previamente en el sistema

### Generación Automática
- **SKU**: Se genera basado en el nombre si no se proporciona
- **Slug**: Se genera automáticamente para URLs amigables
- **Meta título**: Se usa el nombre del producto si está vacío
- **Meta descripción**: Se genera desde la descripción si está vacía

## Errores Comunes y Soluciones

### Error: "El nombre del producto es obligatorio"
- **Causa**: Celda NOMBRE vacía
- **Solución**: Completa el nombre en todas las filas

### Error: "El precio del producto es obligatorio"
- **Causa**: Celda PRECIO vacía o inválida
- **Solución**: Agrega un precio numérico válido

### Error: "Ya existe un producto con el SKU: XXXX"
- **Causa**: El SKU ya existe en la base de datos
- **Solución**: Cambia el SKU o déjalo vacío para generación automática

### Error: "Categoría 'XXXX' no encontrada"
- **Causa**: La categoría especificada no existe
- **Solución**: Verifica el nombre exacto o crea la categoría primero

### Error: "El precio de oferta debe ser menor al precio regular"
- **Causa**: Precio de oferta igual o mayor al precio normal
- **Solución**: Ajusta los precios o deja vacío el precio de oferta

### Error: "El stock no puede ser negativo"
- **Causa**: Valor negativo en stock
- **Solución**: Usa solo números positivos o cero

## Diferencias con la Actualización Masiva

| Aspecto | Creación | Actualización |
|---------|----------|---------------|
| **Propósito** | Crear productos nuevos | Modificar productos existentes |
| **Identificación** | No requiere ID/SKU existente | Requiere ID o SKU existente |
| **Campos obligatorios** | NOMBRE y PRECIO | Solo campos a modificar |
| **SKU duplicado** | Error si ya existe | Busca el producto existente |
| **Validaciones** | Más estrictas para creación | Más flexibles para actualización |
| **Generación automática** | SKU, slug, meta datos | No genera nuevos datos |

## Recomendaciones de Uso

### Antes de Importar
1. **Prepara categorías y marcas**: Crea las categorías y marcas necesarias antes de importar
2. **Prueba con pocos productos**: Haz una prueba con 5-10 productos primero
3. **Revisa el formato**: Asegúrate de que los datos estén en el formato correcto
4. **Haz backup**: Aunque se crean productos nuevos, siempre es buena práctica

### Durante la Creación
1. **Usa nombres descriptivos**: Los nombres serán visibles en el catálogo
2. **Completa descripciones**: Mejoran el SEO y la experiencia del usuario
3. **Asigna categorías correctas**: Facilita la navegación del catálogo
4. **Configura stock inicial**: Define stock realista desde el principio

### Después de Importar
1. **Revisa los productos creados**: Verifica que se crearon correctamente
2. **Agrega imágenes**: Los productos se crean sin imágenes, agrégalas manualmente
3. **Configura atributos**: Si los productos tienen variantes, configúralas después
4. **Prueba en frontend**: Verifica que se muestren correctamente en el catálogo

## Flujo de Trabajo Recomendado

### Para Nuevos Catálogos
1. **Planificación**: Define categorías, marcas y estructura
2. **Preparación**: Crea categorías y marcas en el sistema
3. **Descarga plantilla**: Obtén la plantilla con ejemplos
4. **Completar datos**: Llena el Excel con tus productos
5. **Importación por lotes**: Importa de a 50-100 productos por vez
6. **Verificación**: Revisa y ajusta productos creados
7. **Imágenes y detalles**: Agrega imágenes y configuraciones específicas

### Para Actualizaciones Periódicas
1. **Exporta productos existentes** (para actualización)
2. **Descarga plantilla nuevos productos** (para productos nuevos)
3. **Separa por tipo**: Usa cada herramienta según corresponda
4. **Procesa por lotes**: Mantén importaciones manejables
5. **Documenta cambios**: Lleva registro de productos agregados

## Limitaciones Actuales

- **Imágenes**: No se pueden importar imágenes via Excel (se agregan manualmente)
- **Atributos/Variantes**: No se importan atributos complejos (se configuran después)
- **Categorías múltiples**: Solo se asigna una categoría por producto
- **Relaciones complejas**: No se importan relaciones con otros productos

## Soporte Técnico

Si encuentras problemas:
1. **Revisa la notificación de errores**: Contiene información específica
2. **Verifica el formato**: Asegúrate de seguir las instrucciones de la plantilla
3. **Prueba con menos productos**: Reduce el lote para identificar problemas
4. **Contacta soporte**: Con detalles específicos del error y el archivo utilizado

---

**Nota**: Esta funcionalidad está optimizada para la creación eficiente de catálogos completos. Combínala con la funcionalidad de actualización masiva para un manejo completo de tu inventario desde Excel.
