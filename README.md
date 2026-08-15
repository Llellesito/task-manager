# Task Manager

Aplicación web de gestión de tareas desarrollada con PHP, MySQL, HTML, CSS y JavaScript.

Este proyecto fue creado como parte de mi portafolio personal para practicar el desarrollo de una aplicación CRUD completa, trabajando con bases de datos, lógica del lado del servidor y una interfaz web responsive.

---

## Funcionalidades

- Crear nuevas tareas
- Añadir una descripción opcional
- Establecer una fecha límite opcional
- Marcar tareas como completadas
- Volver a marcar tareas como pendientes
- Eliminar tareas
- Separar tareas pendientes y completadas
- Ordenar las tareas pendientes según su fecha límite
- Ordenar las tareas completadas según la fecha en la que fueron completadas
- Indicadores visuales según el estado de la fecha límite
- Modo claro / modo oscuro
- Guardado de la preferencia de tema mediante `localStorage`
- Diseño responsive para ordenador y dispositivos móviles
- Conexión con MySQL mediante PDO

### Indicadores de fecha límite

Las tareas pendientes cambian visualmente dependiendo del tiempo restante:

- 🔵 **Normal** — Sin fecha límite o quedan más de 24 horas
- 🟡 **Próxima a vencer** — Quedan menos de 24 horas
- 🔴 **Vencida** — La fecha límite ya ha pasado
- 🟢 **Completada** — La tarea ha sido completada

---

## Tecnologías utilizadas

### Frontend

- HTML5
- CSS3
- JavaScript
- Diseño responsive

### Backend

- PHP
- PDO

### Base de datos

- MySQL

### Entorno de desarrollo

- Docker
- Docker Compose
- phpMyAdmin

---

## Estructura del proyecto

```text
task-manager/
│
├── assets/
│   ├── css/
│   │   └── style.css
│   │
│   └── js/
│       └── main.js
│
├── config/
│   └── db.php
│
├── index.php
│
├── docker-compose.yml
│
└── README.md