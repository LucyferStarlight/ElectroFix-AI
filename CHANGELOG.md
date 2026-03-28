# Changelog

## v1.0.0-Beta - 2026-03-28
- Fortalecimiento integral de arquitectura backend (services/actions, flujo IA persistente y controlado).
- Observabilidad estructurada en JSON para pagos, errores y cambios de estado con contexto `order_id`, `user_id`, `action`.
- Endurecimiento de seguridad: sanitización de inputs, manejo centralizado de excepciones y throttling en endpoints críticos.
- Optimización de rendimiento: índices críticos (`symptoms`, `equipment_id`, `status`) y reducción de riesgos N+1.
- Preparación de producción y CI/CD: cobertura mínima en pipeline, build/deploy condicionado a tests y guía compatible con hosting Neubox.
