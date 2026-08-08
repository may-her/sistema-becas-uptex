# Sistema de Becas UPTeX

Sistema web para la gestión integral del proceso de becas de la Universidad Politécnica de Texcoco (UPTex).

La plataforma centraliza la administración de convocatorias, periodos académicos, carreras, grupos, usuarios y solicitudes de beca, proporcionando diferentes niveles de acceso de acuerdo con el rol de cada usuario.

## Descripción

El Sistema de Becas UPTeX tiene como objetivo digitalizar y optimizar los procesos relacionados con la solicitud, revisión, seguimiento y administración de becas institucionales.

La aplicación utiliza una arquitectura cliente-servidor compuesta por un frontend desarrollado con Vue.js y un backend basado en Laravel que proporciona una API REST para la comunicación con la base de datos.

El sistema contempla cuatro perfiles principales:

- Alumno
- Profesor / Tutor
- Administrador / Jefe de carrera
- Superadministrador

Cada perfil dispone de funciones y permisos específicos de acuerdo con sus responsabilidades dentro del proceso de gestión de becas.

## Funcionalidades

### Módulo de alumnos

Permite a los estudiantes:

- Registrarse e iniciar sesión en el sistema.
- Consultar convocatorias disponibles.
- Seleccionar la modalidad de beca.
- Registrar su carrera y grupo.
- Crear solicitudes de beca.
- Consultar el estado de sus solicitudes.
- Revisar su historial de solicitudes.
- Gestionar la documentación requerida.

### Módulo de profesores y tutores

Permite:

- Consultar solicitudes correspondientes a los alumnos asignados.
- Revisar información de las solicitudes.
- Dar seguimiento al proceso.
- Actualizar el estado de las solicitudes de acuerdo con los permisos establecidos.

### Módulo de administración

Permite:

- Gestionar alumnos.
- Administrar personal.
- Consultar y revisar solicitudes.
- Emitir dictámenes.
- Gestionar periodos académicos.
- Crear y administrar convocatorias.
- Revisar documentación asociada a las solicitudes.

### Módulo de superadministración

Proporciona acceso a la administración general del sistema:

- Dashboard general.
- Estadísticas del sistema.
- Gestión de usuarios.
- Gestión de alumnos.
- Gestión de personal.
- Gestión de carreras.
- Gestión de grupos.
- Gestión de periodos académicos.
- Gestión de convocatorias.
- Consulta y seguimiento de solicitudes.

## Programas académicos

Actualmente el sistema contempla los siguientes programas:

- Ingeniería en Robótica
- Ingeniería en Sistemas Computacionales
- Ingeniería en Electrónica y Telecomunicaciones
- Ingeniería en Logística y Transporte
- Licenciatura en Administración y Gestión Empresarial
- Licenciatura en Comercio Internacional y Aduanas

## Arquitectura

El proyecto está dividido en dos aplicaciones principales:

### Frontend

Responsable de la interfaz de usuario y de la interacción con los diferentes módulos del sistema.

Tecnologías principales:

- Vue.js
- Vite
- JavaScript
- HTML5
- CSS3
- Axios

### Backend

Responsable de la lógica de negocio, autenticación, validaciones, administración de datos y exposición de servicios mediante una API REST.

Tecnologías principales:

- PHP
- Laravel
- Laravel Sanctum
- Eloquent ORM
- API REST

### Base de datos

- MySQL

