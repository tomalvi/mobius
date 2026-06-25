# API de Gestión de Pedidos con Eventos

API REST en Laravel para gestión de pedidos: usuarios, productos, pedidos y líneas de pedido, con control de stock mediante eventos/listeners y actualización de totales mediante un Observer.

## Stack

- Laravel 13
- Laravel Sanctum (autenticación por token)
- MySQL
- Vue 3 + Inertia (scaffolding del starter kit, no usado por la API REST)

## Decisiones de diseño

- **Observer (`OrderItemObserver`)**: recalcula `orders.total` sumando los `subtotal` de los items cada vez que se crea, actualiza o elimina un `OrderItem`. Usa `saveQuietly()` para no disparar eventos en cascada.
- **Event/Listener (`OrderCreated` / `DecreaseStock`)**: tras crear el pedido y sus items, se dispara `OrderCreated`. El listener `DecreaseStock` descuenta el stock de cada producto (con `lockForUpdate` para evitar condiciones de carrera) y lanza `InsufficientStockException` si no hay stock suficiente. El listener es síncrono (sin `ShouldQueue`) a propósito: se ejecuta dentro de la misma transacción que crea el pedido, así que si falla, todo se revierte. La conexión evento→listener se resuelve por auto-discovery de Laravel 11 (no se registra manualmente en `AppServiceProvider`, para evitar que el listener se ejecute dos veces).
- **Transacción DB**: todo el flujo de creación de pedido (orden + items + descuento de stock) corre dentro de una única `DB::transaction`.
- **Doble validación de stock**: se valida en el `StoreOrderRequest` (antes de tocar la base de datos, 422 con mensaje claro) y además en el listener como red de seguridad ante condiciones de carrera entre peticiones concurrentes.
- **Middleware `CheckOrderOwner`**: protege `GET /api/orders/{id}` y `PUT /api/orders/{id}/cancel`, devolviendo 403 si el pedido no pertenece al usuario autenticado y 404 si no existe.
- **API Resources**: las respuestas de pedidos/productos pasan por `OrderResource`, `OrderItemResource` y `ProductResource` para controlar exactamente qué campos se exponen.

## Instalación (MySQL local / XAMPP)

1. Arranca MySQL desde el panel de XAMPP.

2. Crea la base de datos (puedes usar phpMyAdmin o la consola de MySQL):
   ```sql
   CREATE DATABASE order_api;
   ```

3. Clona el repo e instala dependencias:
   ```bash
   git clone {url-de-tu-repo}
   cd {carpeta-del-proyecto}
   composer install
   ```

4. Configura el `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Y ajusta estas variables según tu instalación de XAMPP (normalmente usuario `root` sin contraseña):
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=order_api
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Migra y siembra datos de prueba:
   ```bash
   php artisan migrate --seed
   ```
   Esto crea un usuario demo (`demo@example.com` / `password`) y varios productos de prueba (incluyendo uno con stock 0, útil para probar el caso de stock insuficiente).

6. Instala dependencias de frontend (el proyecto incluye scaffolding de Vue/Inertia para el panel web, no afecta a la API REST que es lo evaluado aquí):
   ```bash
   npm install
   npm run build
   ```

7. Levanta el servidor:
   ```bash
   php artisan serve
   ```
   La API queda disponible en `http://127.0.0.1:8000`.

> Nota: este proyecto se generó con el starter kit de Laravel + Vue/Inertia. La API REST (la parte evaluada en esta prueba técnica) vive enteramente en `routes/api.php` y no depende del frontend Vue — puedes ignorar las rutas y vistas de `routes/web.php` para efectos de esta evaluación.

## Endpoints

| Método | URI | Auth | Descripción |
|---|---|---|---|
| POST | `/api/register` | No | Registra un usuario y devuelve token Sanctum |
| POST | `/api/login` | No | Login, devuelve token Sanctum |
| GET | `/api/products` | Sí | Lista productos (cacheado 5 min) — bonus |
| POST | `/api/orders` | Sí | Crea un pedido con items |
| GET | `/api/orders` | Sí | Lista los pedidos del usuario autenticado (paginado) |
| GET | `/api/orders/{id}` | Sí + dueño | Ver un pedido con sus items y productos |
| PUT | `/api/orders/{id}/cancel` | Sí + dueño | Cancela un pedido si está `pending` |

Todas las rutas autenticadas requieren el header:
```
Authorization: Bearer {token}
```

### Ejemplos

**Registro**
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Ana","email":"ana@example.com","password":"password123","password_confirmation":"password123"}'
```

**Login**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password"}'
```

**Listar productos**
```bash
curl http://127.0.0.1:8000/api/products -H "Authorization: Bearer {token}"
```

**Crear pedido**
```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"product_id":1,"quantity":2}]}'
```

**Listar pedidos**
```bash
curl http://127.0.0.1:8000/api/orders -H "Authorization: Bearer {token}"
```

**Ver pedido**
```bash
curl http://127.0.0.1:8000/api/orders/1 -H "Authorization: Bearer {token}"
```

**Cancelar pedido**
```bash
curl -X PUT http://127.0.0.1:8000/api/orders/1/cancel -H "Authorization: Bearer {token}"
```

## Códigos de error

| Código | Caso |
|---|---|
| 401 | Token ausente o inválido |
| 403 | Intentar ver/cancelar un pedido de otro usuario |
| 404 | Pedido no encontrado |
| 422 | Validación fallida, stock insuficiente, o cancelar un pedido no `pending` |

## Bonus implementados

- Transacción DB en la creación de pedido (orden + items + descuento de stock, todo o nada).
- Scope `pending()` en el modelo `Order` (`Order::pending()->get()`).
- Caché del listado de productos por 5 minutos (`Cache::remember`, clave `products.index`).

## Estructura relevante

```
app/
  Events/OrderCreated.php
  Listeners/DecreaseStock.php
  Observers/OrderItemObserver.php
  Exceptions/InsufficientStockException.php
  Http/Middleware/CheckOrderOwner.php
  Http/Requests/{RegisterRequest,LoginRequest,StoreOrderRequest}.php
  Http/Resources/{OrderResource,OrderItemResource,ProductResource}.php
  Http/Controllers/{UserController,OrderController,ProductController}.php
  Models/{User,Product,Order,OrderItem}.php
database/
  migrations/...
  factories/{UserFactory,ProductFactory}.php
  seeders/DatabaseSeeder.php
routes/api.php
bootstrap/app.php   (middleware aliases + manejo de excepciones)
```
