# Observabilidad: ejemplos de eventos

Todos los eventos se escriben en `storage/logs/observability.log` en formato JSON.

## 1) Pago exitoso

```json
{
  "message": "payments.sync.completed",
  "context": {
    "order_id": 182,
    "user_id": 54,
    "action": "payments.stripe.checkout_completed",
    "event": "payments.sync.completed",
    "service": "ElectroFix-AI",
    "env": "local",
    "category": "payments",
    "amount": 1499.0,
    "stripe_payment_intent_id": "pi_3Qxyz...",
    "stripe_checkout_session_id": "cs_test_abc...",
    "source": "stripe",
    "status": "succeeded"
  },
  "level": 200,
  "level_name": "INFO"
}
```

## 2) Cambio de estado de orden

```json
{
  "message": "orders.status.changed",
  "context": {
    "order_id": 182,
    "user_id": 54,
    "action": "orders.status.transition",
    "event": "orders.status.changed",
    "service": "ElectroFix-AI",
    "env": "local",
    "category": "state_changes",
    "from_status": "quoted",
    "to_status": "approved"
  },
  "level": 200,
  "level_name": "INFO"
}
```

## 3) Error crítico global

```json
{
  "message": "app.unhandled_exception",
  "context": {
    "order_id": 182,
    "user_id": 54,
    "action": "worker.orders.status",
    "event": "app.unhandled_exception",
    "service": "ElectroFix-AI",
    "env": "local",
    "category": "errors",
    "status_code": 500,
    "path": "worker/orders/182/status",
    "method": "PATCH",
    "exception_class": "RuntimeException",
    "exception_message": "Unexpected transition failure...",
    "exception_code": "0",
    "trace_id": "9f2ca8cbf0d0d2a3a013"
  },
  "level": 600,
  "level_name": "CRITICAL"
}
```

## Preparado para exportadores externos

Variables disponibles (sin integración activa):

```env
OBSERVABILITY_EXTERNAL_ENABLED=false
OBSERVABILITY_EXTERNAL_DRIVER=none
OBSERVABILITY_EXTERNAL_ENDPOINT=
OBSERVABILITY_EXTERNAL_API_KEY=
OBSERVABILITY_EXTERNAL_TIMEOUT_SECONDS=3
```
