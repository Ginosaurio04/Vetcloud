-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-04-2026
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `veterinaria_unimar`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `puntos_fidelidad` int(11) DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT IGNORE INTO `clientes` (`id_cliente`, `cedula`, `nombre_completo`, `telefono`, `email`, `direccion`, `puntos_fidelidad`, `fecha_registro`) VALUES
(1, 'V-12345678', 'Gino Cova', '0412-1112233', 'gino.cova@email.com', 'Pampatar, Nueva Esparta', 150, '2026-03-30 04:41:46'),
(2, 'V-87654321', 'Francisco Albornoz', '0414-4445566', 'francisco.a@email.com', 'La Asunción, Nueva Esparta', 80, '2026-03-30 04:41:46'),
(3, 'V-11222333', 'Eyla Vasquez', '0424-7778899', 'eyla.v@email.com', 'Porlamar, Nueva Esparta', 200, '2026-03-30 04:41:46'),
(4, 'V-44555666', 'Maria Rodriguez', '0416-2223344', 'm.rodriguez@email.com', 'Juan Griego, Nueva Esparta', 45, '2026-03-30 04:41:46'),
(5, 'V-99888777', 'Carlos Perez', '0412-5556677', 'carlos.perez@email.com', 'El Valle, Nueva Esparta', 10, '2026-03-30 04:41:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE IF NOT EXISTS `mascotas` (
  `id_mascota` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `nombre_animal` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raza` varchar(80) DEFAULT NULL,
  `edad` varchar(30) DEFAULT NULL,
  `peso` decimal(6,2) DEFAULT NULL,
  `sexo` enum('Macho','Hembra') DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `notas_medicas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mascota`),
  KEY `fk_mascota_cliente` (`id_cliente`),
  CONSTRAINT `fk_mascota_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Datos de ejemplo para mascotas
--

INSERT IGNORE INTO `mascotas` (`id_mascota`, `id_cliente`, `nombre_animal`, `especie`, `raza`, `edad`, `peso`, `sexo`, `color`, `notas_medicas`) VALUES
(1, 1, 'Rocky', 'Perro', 'Labrador Retriever', '3 años', 28.50, 'Macho', 'Dorado', 'Vacunas al día. Alergia leve al pollo.'),
(2, 1, 'Mimi', 'Gato', 'Siamés', '2 años', 4.20, 'Hembra', 'Crema y marrón', 'Esterilizada. Sin condiciones conocidas.'),
(3, 2, 'Max', 'Perro', 'Pastor Alemán', '5 años', 35.00, 'Macho', 'Negro y fuego', 'Displasia leve de cadera. Tratamiento con condroprotectores.'),
(4, 3, 'Luna', 'Gato', 'Persa', '1 año', 3.80, 'Hembra', 'Blanco', 'Necesita cepillado frecuente. Vacunas pendientes.'),
(5, 3, 'Coco', 'Ave', 'Cacatúa', '4 años', 0.35, 'Macho', 'Blanco con cresta amarilla', 'Dieta especial. Revisión de plumas cada 3 meses.'),
(6, 4, 'Thor', 'Perro', 'Bulldog Francés', '2 años', 12.50, 'Macho', 'Atigrado', 'Problemas respiratorios leves. Evitar ejercicio intenso.'),
(7, 5, 'Nala', 'Gato', 'Bengalí', '3 años', 5.10, 'Hembra', 'Manchado dorado', 'Muy activa. Sin condiciones médicas.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `id_mascota` int(11) NOT NULL,
  `fecha_cita` datetime NOT NULL,
  `tipo_servicio` varchar(50) NOT NULL,
  `estado` enum('En Espera','En Consulta','En Peluquería','Finalizado','Cancelado') DEFAULT 'En Espera',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cita`),
  KEY `fk_cita_mascota` (`id_mascota`),
  CONSTRAINT `fk_cita_mascota` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
