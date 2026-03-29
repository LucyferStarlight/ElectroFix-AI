-- ElectroFix-AI: SQL base para crear BD y usuario.
-- Nota: en hosting compartido cPanel/Neubox normalmente debes crear BD/usuario
-- desde la interfaz "MySQL Databases". Este script sirve como referencia exacta
-- (o ejecución directa en VPS/servidor con privilegios CREATE USER).

-- 1) Base de datos (nombre lógico del proyecto)
CREATE DATABASE IF NOT EXISTS `Elect152_electrofix_ai`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2) Usuario de base de datos
-- Cambia la contraseña antes de producción si decides reutilizar este ejemplo.
CREATE USER IF NOT EXISTS 'elect152_electrofix_ai_user'@'localhost'
  IDENTIFIED BY 'A8E?EQ+MbeG7a@}E';

-- 3) Permisos
GRANT ALL PRIVILEGES ON `Elect152_electrofix_ai`.* TO 'elect152_electrofix_ai_user'@'localhost';
FLUSH PRIVILEGES;

-- En cPanel, si te fuerza prefijo de cuenta (ej. elect152_), usa estos valores:
-- DB_DATABASE=Elect152_electrofix_ai
-- DB_USERNAME=elect152_electrofix_ai_user
-- (o la variante corta que te permita cPanel)
