# ElectroFix-AI

Sistema SaaS multiempresa para gestión de servicio técnico e inventario/facturación.

## Stack tecnológico
- PHP 8.3+
- Laravel 12
- Blade + Bootstrap 5 + JavaScript vanilla
- MySQL (XAMPP compatible)
- Stripe (estructura backend, sin credenciales reales)
- IA diagnóstica (stub local preparado para proveedor real)

## Arquitectura funcional actual
- Multiempresa por `company_id`.
- Roles:
  - `developer`: alcance global.
  - `admin`: administración de su empresa.
  - `worker`: operación diaria con módulos delegados.
- Control de acceso:
  - por rol (`EnsureRole`)
  - por módulo (`EnsureModuleAccess`)
- Módulos:
  - Órdenes de trabajo
  - Clientes
  - Equipos
  - Inventario
  - Facturación (POS + PDF)
  - Suscripción/planes por empresa
  - IA de diagnóstico con límites por plan/mes

## Reglas de negocio implementadas (resumen)
### Órdenes
- Admin puede asignar técnico en creación (`worker` o `admin` activo de su empresa).
- Worker no puede editar técnico en UI; se autoasigna con su nombre.
- Dashboard worker muestra órdenes delegadas a su nombre.

### IA en órdenes
- Se solicita en creación de orden (checkbox).
- Máximo una ejecución por orden (`ai_diagnosed_at`).
- Límite de síntomas: 600 caracteres.
- Planes habilitados: `enterprise`, `developer_test`.
- Límites mensuales por empresa:
  - `enterprise`: 200 consultas / 120,000 tokens estimados
  - `developer_test`: 500 consultas / 500,000 tokens estimados
- Bloqueos con mensaje funcional cuando no aplica plan/cuota/tokens.
- Costo sugerido condicional:
  - Solo reparación (mano de obra), o
  - Reparación + reemplazo (piezas + mano de obra), según bandera IA.

### Facturación
- Flujo separado por `Venta`, `Mixto`, `Reparación`.
- En venta: producto + cantidad limitada por stock.
- En reparación: servicios del cliente y actualización de estado ligada al tipo de documento.
- Mostrador: creación automática de orden completada para servicios.
- IVA configurable por empresa y desglose interno base + IVA.

## Estructura de datos relevante
- `companies`, `users`, `subscriptions`
- `customers`, `equipments`, `orders`
- `inventory_items`, `inventory_movements`
- `billing_documents`, `billing_document_items`
- `company_ai_usages`

## Instalación local (XAMPP + MySQL)
1. Clonar repositorio.
2. Instalar dependencias PHP:
   ```bash
   composer install
   ```
3. Crear `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configurar base de datos MySQL en `.env`:
   ```env
   APP_URL=http://localhost/Portafolio/ElectroFix-AI/public
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=electrofix_ai
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Ejecutar migraciones y seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```
6. Levantar servidor:
   ```bash
   php artisan serve
   ```

## Credenciales demo (seeders)
- Developer:
  - email: `developer@electrofix.ai`
  - password: `password123`
- Admin:
  - email: `admin@electrofix.ai`
  - password: `password123`
- Worker:
  - email: `worker@electrofix.ai`
  - password: `password123`

## Comandos útiles
- Limpiar cachés:
  ```bash
  php artisan optimize:clear
  ```
- Validar rutas clave:
  ```bash
  php artisan route:list | grep -E "worker.orders|worker.billing|worker.inventory"
  ```

## Pruebas
```bash
php artisan test
```

Nota: si el entorno no tiene `pdo_sqlite`, las pruebas con `RefreshDatabase` fallarán con driver sqlite en memoria. En ese caso:
- habilitar extensión sqlite para CLI PHP, o
- ajustar `phpunit.xml` para usar MySQL de pruebas.

## Preparación para commit/push
```bash
git add .
git commit -m "feat: complete SaaS modules (orders, inventory, billing, AI limits) and delivery docs"
git push origin <tu-rama>
```

## Licencia
Este proyecto no se distribuye bajo MIT para uso final del producto.
Revisar [LICENSE](LICENSE) para términos de uso autorizados.
