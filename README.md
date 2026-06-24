# Agenda de Citas – Proyecto DevOps Semana 7

Aplicación web en PHP y MySQL para gestión de citas médicas.

## Tecnologías

- PHP 8.2 + Apache
- MySQL 8.0
- Docker & Docker Compose
- GitHub Actions (CI/CD)

## Estructura del proyecto

```
Agenda_citas/
├── .github/
│   └── workflows/
│       ├── ci-basica.yml          # Actividad 1 y 2: primer workflow + trigger manual
│       ├── ci-validacion.yml      # Actividad 3: CI que valida archivos clave
│       ├── artifact-demo.yml      # Actividad 4: generar y descargar artifact
│       ├── pipeline-devops.yml    # Actividad 5: pipeline CI/CD con dos jobs
│       └── docker-build.yml       # Actividad 6: construir imagen Docker
├── app/
│   └── index.html
├── index.php
├── nueva.php
├── editar.php
├── eliminar.php
├── calendario.php
├── estadisticas.php
├── conexion.php
├── database.sql
├── Dockerfile
├── compose.yml
└── README.md
```


## Levantar el proyecto localmente con Docker

```bash
docker compose up -d
```

Al iniciar, el contenedor `db` ejecuta automáticamente `database.sql` (carpeta de inicialización de MySQL) y crea la tabla `citas` con datos de ejemplo. No es necesario importarlo manualmente.

Acceder en: [http://localhost:8080](http://localhost:8080)

### Entorno local sin Docker (XAMPP)

1. Importa `database.sql` en phpMyAdmin.
2. `conexion.php` usa por defecto `localhost` / `root` sin contraseña (configuración típica de XAMPP) si no detecta variables de entorno de Docker.

## Workflows de GitHub Actions

| Archivo | Actividad | Descripción |
|---|---|---|
| `ci-basica.yml` | 1 y 2 | Primer workflow; se activa por push y manualmente |
| `ci-validacion.yml` | 3 | Valida que existan los archivos clave del proyecto |
| `artifact-demo.yml` | 4 | Genera y sube un archivo de evidencia como artifact |
| `pipeline-devops.yml` | 5 | Pipeline con job de CI y job de CD (entrega simulada) |
| `docker-build.yml` | 6 | Construye la imagen Docker dentro del runner |

## Reflexión – Actividad 7

- **Workflow**: proceso automatizado definido en YAML que se ejecuta en GitHub cuando ocurre un evento (push, pull_request, ejecución manual).
- **Job vs Step**: un job es un conjunto de pasos que corre en un runner; un step es cada acción o comando individual dentro de ese job.
- **Runner**: máquina virtual (ubuntu-latest, windows-latest, etc.) que ejecuta los jobs del workflow.
- **Artifact**: archivo conservado después de una ejecución; útil para guardar reportes, binarios o evidencias.
- **Reducción de errores manuales**: GitHub Actions automatiza validaciones que normalmente haría un humano (verificar archivos, compilar, construir imagen), asegurando que cada push cumpla los requisitos sin depender de pasos manuales olvidados.

## Correcciones aplicadas al proyecto

Durante la revisión se detectaron y corrigieron los siguientes problemas:


| Problema | Archivo(s) | Corrección |
|---|---|---|
| `conexion.php` estaba codificado solo para XAMPP (`localhost`, sin contraseña) y nunca leía las variables de entorno (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`) que define `compose.yml`. El contenedor de la app nunca podía conectarse a MySQL. | `conexion.php` | Ahora usa `getenv()` con esas variables y conserva los valores de XAMPP como respaldo si no existen. |
| No existía el archivo `database.sql` que el propio mensaje de error de `conexion.php` pedía ejecutar, ni había forma de crear la tabla `citas` automáticamente en Docker. | `database.sql` (nuevo) | Se creó el script con la tabla `citas` y datos de ejemplo, y se montó como script de inicialización de MySQL en `compose.yml`. |
| `eliminar.php` borraba una cita con una simple petición GET, sin protección: cualquier enlace, imagen o script externo podía disparar el borrado (vulnerabilidad CSRF). | `eliminar.php`, `index.php` | Se agregó un token CSRF por sesión que debe coincidir para procesar la eliminación. |
| En `calendario.php`, la fecha de inicio del mes se construía sin ceros de relleno (`"$anio-$mes-01"` generaba `2026-1-01` en vez de `2026-01-01`), lo que puede causar comparaciones de fecha inconsistentes en MySQL. | `calendario.php` | Se usa `sprintf('%04d-%02d-01', ...)` para garantizar el formato correcto `YYYY-MM-DD`. |
| `docker-build.yml` solo construía la imagen pero nunca la ejecutaba junto a una base de datos real, por lo que el pipeline no detectaba el problema de conexión. | `docker-build.yml` | Se agregaron pasos que levantan `docker compose up`, esperan a que la base de datos esté `healthy` y verifican con `curl` que `index.php` responde sin el mensaje de error de conexión, antes de apagar los contenedores. |

> Nota: las consultas SQL del proyecto (`nueva.php`, `editar.php`, `index.php`) ya usaban `real_escape_string()` y `(int)` casting correctamente, por lo que no se encontraron inyecciones SQL explotables.
