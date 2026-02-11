# test-vocacional
Test Vocacional - MVC con php, Formulario vocacional: intereses, habilidades y valores. Informes: individual (PDF) y grupal (PDF/Excel). Panel de administración: gestión de estudiantes y resultados. Seguridad: acceso por usuarios y roles (DECE, estudiante, Administrador)

## Prerrequisitos
- Node.js y npm instalados.
- PHP y Composer instalados (y en PATH).
- MySQL instalado y en ejecución (configuración predeterminada: localhost, root, sin contraseña).
## Instalación
Ejecute el siguiente comando para instalar las dependencias (tanto de npm como de Composer) e importar la base de datos:
```bash
npm run setup
Este comando ejecuta:

npm install: Instala las dependencias de Node.js (p. ej., mysql2).
npm run install:php: Ejecuta composer install para instalar las dependencias de PHP.
npm run db:import: Importa la base de datos desde bdd.sql a test_vocacional.
Iniciando el servidor
Desde la raíz del proyecto, ejecute:

bash
npm start
Esto iniciará el servidor PHP integrado en http://localhost:8000.

Detalles de la importación de la base de datos
El script de importación
scripts/db-import.js
:

Se conecta a MySQL.

Borra la base de datos test_vocacional si existe (lo que garantiza un nuevo inicio).

Crea la base de datos test_vocacional.

Ejecuta los comandos SQL desde
bdd.sql
.

NOTA

Si necesita configurar las credenciales de la base de datos de forma diferente, puede modificar
scripts/db-import.js
o establecer las variables de entorno DB_HOST, DB_USER y DB_PASS.