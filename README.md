# ElectroFix-AI

Sistema SaaS multiempresa para gestión de servicio técnico e inventario/facturación.

## Stack tecnológico
- PHP 8.3+
- Laravel 12
- Blade + Bootstrap 5 + JavaScript vanilla
- MySQL (XAMPP compatible / Docker)
- Redis (cache + colas)
- Docker + Nginx (opcional)
- Stripe (Checkout + webhooks)
- IA diagnóstica con Groq (`llama-3.1-8b-instant`)

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
- Planes habilitados según configuración del plan.
- Límites mensuales por empresa:
  - `starter`: 10 consultas / 8,000 tokens estimados
  - `pro`: 75 consultas / 50,000 tokens estimados
  - `enterprise`: 200 consultas / 120,000 tokens estimados
  - `developer_test`: 300 consultas / 500,000 tokens estimados
- Bloqueos con mensaje funcional cuando no aplica plan/cuota/tokens.
- Costo sugerido condicional:
  - Solo reparación (mano de obra), o
  - Reparación + reemplazo (piezas + mano de obra), según bandera IA.
- Proveedor:
  - Se usa Groq como proveedor único de IA.
  - Modelo activo: `llama-3.1-8b-instant`.
  - El sistema requiere conexión a internet para procesar diagnósticos.

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
- `support_requests`

## Instalación local (XAMPP + MySQL)
La configuración del repositorio ya quedó preparada para correr en local sin Redis:
- `AI_PROVIDER=groq`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- MySQL local por defecto (`127.0.0.1`, `root`, sin contraseña)

1. Clonar repositorio.
2. Instalar dependencias PHP:
   ```bash
   composer install
   ```
3. Crear `.env`:
   ```bash
   cp .env.local.example .env
   php artisan key:generate
   ```
   O en una sola línea:
   ```bash
   composer run setup-local
   ```
4. Configurar base de datos MySQL en `.env`:
   ```env
   APP_URL=http://127.0.0.1:8000
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
### Requisitos de PHP para local
- Extensión `dom` habilitada
- Extensión `pdo_mysql` habilitada
- Extensiones comunes de Laravel: `mbstring`, `openssl`, `json`, `curl`

En XAMPP esto se valida en `php.ini`. Si `php artisan` muestra `Class "DOMDocument" not found`, falta habilitar `ext-dom`.

## Instalación con Docker (recomendado)
1. Crear `.env`:
   ```bash
   cp .env.example .env
   ```
2. Levantar servicios:
   ```bash
   docker compose up -d --build
   ```
3. Generar key y migrar:
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --force
   ```
4. (Opcional) Seeders:
   ```bash
   docker compose exec app php artisan db:seed --force
   ```
5. Acceder a la app:
   - `http://localhost:8080`

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

Nota: las pruebas usan MySQL con variables `DB_TEST_*` (ver `phpunit.xml` y `.env.testing`).

## Preparación para commit/push
```bash
git add .
git commit -m "feat: complete SaaS modules (orders, inventory, billing, AI limits) and delivery docs"
git push origin <tu-rama>
```

## Deploy rápido
```bash
./deploy.sh
```

Opcional con seeders:
```bash
./deploy.sh --seed
```

## Licencia
Este proyecto no se distribuye bajo MIT para uso final del producto.
Revisar [LICENSE](LICENSE) para términos de uso autorizados.

## Soporte
- Formulario: `/support`
- Correo: `SUPPORT_EMAIL` (default `proyectosweb.haroldadir@gmail.com`)
- WhatsApp: `SUPPORT_WHATSAPP_URL`
- Sitio: `ARAKATADEVS_URL`

Variables nuevas en `.env`:
```env
SUPPORT_EMAIL=proyectosweb.haroldadir@gmail.com
SUPPORT_WHATSAPP_URL=https://wa.me/message/GKUTR2MK5DXIG1
ARAKATADEVS_URL=https://arakatadevs.com.mx
```

Nota: el correo se envía usando el mailer configurado en `MAIL_MAILER`.


## Stripe en local (sin fallos)
Para que la integración de suscripciones funcione de forma estable en local, valida este checklist:

1. **Dependencias y extensiones PHP**
   - Instalar dependencias:
     ```bash
     composer install
     ```
   - Verificar extensiones mínimas: `pdo_mysql` (o `pdo_sqlite`), `mbstring`, `openssl`, `json`, `curl`.

2. **Variables de entorno en `.env` (modo TEST)**
   - Copiar base:
     ```bash
     cp .env.example .env
     php artisan key:generate
     ```
   - Completar Stripe:
     ```env
     STRIPE_KEY=pk_test_xxx
     STRIPE_SECRET=sk_test_xxx
     STRIPE_WEBHOOK_SECRET=whsec_xxx
     ```
   - Definir los Price IDs (puedes usar cualquiera de los dos formatos):
     - Formato largo: `STRIPE_PRICE_STARTER_MONTHLY`, `STRIPE_PRICE_PRO_ANNUAL`, etc.
     - Formato corto: `STARTER_MONTHLY`, `PRO_ANNUAL`, `ENTERPRISE_SEMIANNUAL`, etc.

3. **Base de datos inicializada**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Levantar app local**
   ```bash
   php artisan serve
   ```

5. **Exponer webhook de Stripe**
   - Opción A (Stripe CLI):
     ```bash
     stripe listen --forward-to http://127.0.0.1:8000/api/billing/stripe/webhook
     ```
     Copia el `whsec_...` entregado por Stripe CLI a `STRIPE_WEBHOOK_SECRET`.
   - Opción B (ngrok):
     ```bash
     ngrok http 8000
     ```
     Luego configura Stripe para enviar webhooks a:
     `https://TU_SUBDOMINIO.ngrok.io/api/billing/stripe/webhook`

6. **Eventos requeridos en Stripe Dashboard / CLI**
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`

7. **Smoke checks recomendados**
   ```bash
   php -l app/Http/Controllers/Api/Billing/StripeController.php
   php -l app/Services/StripeWebhookService.php
   php artisan route:list --path=billing/stripe
   ```

> Nota: aunque recibas credenciales `pk_live` / `sk_live`, para pruebas locales seguras se recomienda usar siempre claves `pk_test` / `sk_test`.
