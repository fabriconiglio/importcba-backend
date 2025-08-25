# TODO

## Hecho
- [x] Implementar sistema de autenticación con Sanctum
- [x] Implementar carrito persistente en el backend
- [x] Sistema de gestión de imágenes para productos (API funcionando: subida, variantes, eliminación, orden y principal)
- [x] Implementar paginación en productos (`per_page`, `page`) con payload seguro (sin traducción de links)
- [x] Filtros avanzados por categoría, marca, búsqueda, precio efectivo (`price_min`/`price_max`), stock (`in_stock`) y ordenamiento (`sort`)
- [x] Integrar paginación y filtros en el frontend (UI básica en `ProductGridApi`)

## Pendiente

### Backend (Laravel)
- [x] Completar `BrandController` (index/show/store/update/destroy) con validación y recursos
- [x] Endpoint de catálogo por `slug` de categoría y marca (seo-friendly)
- [x] Checkout: endpoints para iniciar/confirmar pedido, cálculo de envío y totales
- [x] Integrar medios de pago (definir proveedor; mock inicial + interfaz)
- [x] Cupones y reglas de descuento en backend (validación por fecha/uso mínimo)
- [x] Merge de carrito anónimo con carrito de usuario al hacer login
- [x] Reservas/ajuste de stock al confirmar pedido (consistencia)
- [x] Email de confirmación de pedido y recuperación de contraseña (config mail)
- [x] Documentación de API (OpenAPI/Swagger) y ejemplos
- [x] Tests (Feature) para auth, productos, carrito y pedidos

### Panel Admin (Filament)
- [x] Integrar subida/gestión de imágenes en formulario de Productos (gallery, primary, orden)
- [x] Vistas de gestión de pedidos (cambiar estado, ver items, exportar)
- [x] Gestión de cupones/envíos/pagos desde Filament
- [x] Dashboard con KPIs (ventas, tickets promedio, stock bajo)

### Frontend (Next.js)
- [x] Consumir login/registro reales (Sanctum), guardar token y proteger rutas
- [x] Sincronizar carrito local con carrito backend (al login y en cada cambio)
- [x] Página de catálogo dedicada con query params (page, sort, filtros) y SSR/ISR
- [x] Página de detalle de producto consumiendo API (imágenes, precio efectivo, stock)
- [x] Subida/visualización de imágenes en la UI (galería, zoom, thumbs)
- [x] Flujo de checkout (direcciones, envío, pago) consumiendo backend
- [x] SEO/Metadatos (title/description por categoría/producto)
- [x] Manejo de errores/toasts y estados vacíos/skeletons

### Integración y DevOps
- [ ] Variables `.env` para FE/BE coherentes (URL API, storage, mail, pagos)
- [x] CORS afinado por entorno y seguridad de tokens
- [ ] Docker/Sail para desarrollo y compose de stack
- [ ] Pipeline CI (lint/tests) y guía de despliegue
- [x] Integrar la paginación en el frontend

