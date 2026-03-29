# Changelog

## v1.0.3-Beta - 2026-03-29
- Ajustes de IU móvil: consolidación de navegación en menú desplegable con módulos por sección, ocultando la barra lateral de escritorio en pantallas pequeñas.
- Mejora de accesibilidad/UX visual en móvil: botón de menú tipo hamburguesa y refinamientos de estilos para superposición correcta del menú sobre el contenido.
- Incorporación de páginas de error dedicadas (`4xx/5xx`) para respuestas más claras en producción.
- Actualización de lógica comercial para promoción y periodos de prueba por plan (mensual, semestral, anual) con activación por fecha/hora de lanzamiento.
- Endurecimiento de configuración de despliegue en hosting compartido (Neubox/cPanel), incluyendo referencias SQL y ajustes operativos de producción.

## v1.0.1-Beta - 2026-03-28
- Corrección del workflow CI/CD para preparar entorno en `build` (`.env`, `APP_KEY`, limpieza de cachés) y evitar fallos en GitHub Actions.
- Hardening operativo para producción en hosting compartido (perfil Neubox en documentación y despliegue).
- Estabilización final de pruebas y pipeline sin cambios disruptivos en funcionalidades.

## v1.0.0-Beta - 2026-03-28
- Fortalecimiento integral de arquitectura backend (services/actions, flujo IA persistente y controlado).
- Observabilidad estructurada en JSON para pagos, errores y cambios de estado con contexto `order_id`, `user_id`, `action`.
- Endurecimiento de seguridad: sanitización de inputs, manejo centralizado de excepciones y throttling en endpoints críticos.
- Optimización de rendimiento: índices críticos (`symptoms`, `equipment_id`, `status`) y reducción de riesgos N+1.
- Preparación de producción y CI/CD: cobertura mínima en pipeline, build/deploy condicionado a tests y guía compatible con hosting Neubox.
