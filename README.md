# O Porto do Sabor — ERP

Aplicación web de gestión empresarial (ERP) con tienda online para una empresa de productos del mar. Desarrollada con Symfony 7.4, PHP 8.2 y Docker.

---

## Requisitos previos

- [Docker Engine 24+](https://docs.docker.com/get-docker/)
- [Docker Compose v2](https://docs.docker.com/compose/)
- Git

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/alexdieguez14/O_porto_do_sabor_Proxecto_Docker.git
cd Proxecto-O-porto-do-sabor/
```

### 2. Construir y levantar los contenedores

```bash
docker compose up --build -d
```

Esto levanta cuatro servicios:

| Contenedor | Puerto | Función |
|---|---|---|
| `symfony-app` | 8000 | Aplicación web (PHP 8.2 + Apache) |
| `symfony-mysql` | 3306 | Base de datos MySQL 8 |
| `symfony-mercure` | 3000 | Notificaciones en tiempo real |
| `symfony-phpmyadmin` | 8080 | Administración visual de la BD |

### 3. Instalar dependencias PHP

```bash
docker exec symfony-app composer install
```

### 4. Corregir permisos del directorio de caché

Docker levanta el servidor Apache con el usuario `www-data`, que necesita escribir en `var/`. Sin este paso la aplicación lanza un error 500 al arrancar:

```bash
docker exec symfony-app chmod -R 777 var/
```

### 5. Descargar e importar la base de datos

Descargar el dump desde Google Drive:

[Descargar dump.sql](https://drive.google.com/file/d/11J28MssptgUaJKxBfjO5KbraMawfDKEV/view?usp=sharing)

Una vez descargado, importarlo por cualquiera de estos dos métodos:

**Opción A — phpMyAdmin (recomendado):**

1. Abrir [http://localhost:8080](http://localhost:8080)
2. Iniciar sesión con usuario `root` y contraseña `root`
3. Ir a la pestaña **Importar**
4. Seleccionar el archivo `dump.sql` descargado y pulsar **Importar**

**Opción B — línea de comandos:**

```bash
docker exec -i symfony-mysql mysql -u root -proot < dump.sql
```

El dump crea la base de datos `symfony_db` con todas las tablas y datos de prueba incluidos.

### 6. Conceder permisos al usuario de la aplicación

Docker crea el usuario `symfony` con acceso solo a la base auxiliar `prueba`. Tras importar el dump hay que darle permisos sobre `symfony_db` o la aplicación dará error "Access denied":

```bash
docker exec symfony-mysql mysql -u root -proot -e "GRANT ALL PRIVILEGES ON symfony_db.* TO 'symfony'@'%'; FLUSH PRIVILEGES;"
```

> Credenciales de la aplicación: usuario `symfony`, contraseña `symfony` (definidas en `docker-compose.yml` y en `app/.env`).
> Se pueden cambbiar a otro usuario y otra contraseña en los archivos anteriores.

### 7. Acceder a la aplicación

Abrir el navegador en: [http://localhost:8000](http://localhost:8000)

---

## Pantallas principales

| Pantalla | URL |
|---|---|
| Inicio | [http://localhost:8000/](http://localhost:8000/) |
| Registro | [http://localhost:8000/registro](http://localhost:8000/registro) |
| Tienda | [http://localhost:8000/tienda](http://localhost:8000/tienda) |
| Administración | [http://localhost:8000/admin](http://localhost:8000/admin) |
| Contabilidad | [http://localhost:8000/contabilidad](http://localhost:8000/contabilidad) |
| Logística | [http://localhost:8000/logistica](http://localhost:8000/logistica) |

---

## Usuarios de prueba

| Email | Rol |
|---|---|
| admin@test.com | Administrador |
| logistica@test.com | Logística |
| contabilidad@test.com | Contabilidad |
| cliente@test.com | Cliente |
| alberto@cliente.es | Cliente |
| alex@logistica.com | Logística |

> La contraseña de todos los usuarios de prueba es `12345678`.

---

## Estructura del proyecto

```
.
├── Dockerfile
├── docker-compose.yml
└── app/                  
    ├── src/
    │   ├── Controller/
    │   ├── Entity/
    │   ├── Form/
    │   ├── Repository/
    │   └── Service/
    ├── templates/
    ├── translations/     
    ├── config/
    └── public/
```

---

## Comandos útiles

```bash
# Ver logs de la aplicación
docker logs symfony-app --tail 50

# Acceder al contenedor
docker exec -it symfony-app bash

# Limpiar caché
docker exec symfony-app php bin/console cache:clear

# Crear una entidad
docker exec -it symfony-app php bin/console make:entity

# Copia de seguridad de la BD
docker exec symfony-mysql mysqldump -u symfony -psymfony symfony_db > backup.sql

# Parar los contenedores
docker compose down
```

---

## Tecnologías

- **Backend:** PHP 8.2 · Symfony 7.4 · Doctrine ORM 3.6
- **Frontend:** Twig · CSS personalizado
- **Base de datos:** MySQL 8.0
- **Tiempo real:** Mercure Hub
- **Infraestructura:** Docker · Apache
