-- Script de inicialización de la base de datos: agenda_citas
-- Se ejecuta automáticamente en Docker (carpeta docker-entrypoint-initdb.d)
-- o manualmente en phpMyAdmin para entornos locales (XAMPP).

CREATE DATABASE IF NOT EXISTS agenda_citas
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE agenda_citas;

CREATE TABLE IF NOT EXISTS citas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(150)      NOT NULL,
    email     VARCHAR(150)      NOT NULL,
    telefono  VARCHAR(30)       NOT NULL,
    fecha     DATE              NOT NULL,
    hora      TIME              NOT NULL,
    motivo    VARCHAR(255)      NOT NULL,
    estado    ENUM('Pendiente','Confirmada','Completada','Cancelada') NOT NULL DEFAULT 'Pendiente',
    notas     TEXT              NULL,
    creado_en TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (opcional, útil para pruebas y para que el CI tenga datos con los que validar)
INSERT INTO citas (nombre, email, telefono, fecha, hora, motivo, estado, notas) VALUES
('Ana Gómez',    'ana.gomez@example.com',    '3001234567', CURDATE(),               '09:00:00', 'Consulta general',         'Pendiente',  NULL),
('Carlos Pérez', 'carlos.perez@example.com', '3007654321', CURDATE(),               '10:30:00', 'Control de presión',       'Confirmada', 'Paciente hipertenso'),
('Laura Díaz',   'laura.diaz@example.com',   '3009998877', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'Revisión de exámenes', 'Pendiente', NULL);
