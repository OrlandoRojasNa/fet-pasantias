# Implementación de Nexo Pasantías

## Decisión de alcance

El usuario pidió crear el proyecto desde cero y descartó el parecido con la universidad. Nexo tiene una identidad independiente. Se usa PHP 8.2, MariaDB/MySQL y HTML/CSS/JavaScript para ejecutarse en el XAMPP disponible. El commit inicial conserva el proyecto anterior.

Las reglas institucionales no aportadas se exponen como configuración. La semilla es opcional y contiene exclusivamente ejemplos ficticios, no reglas oficiales. Los Word siguen siendo referencias: el sistema admite su carga como documentos, sin convertir sus compromisos en requisitos nuevos.

## A Modelo de datos y migraciones

Esquema independiente `nexo_pasantias`, claves foráneas y migraciones registradas. Incluye usuarios, empresas, estudiantes, periodos, rúbricas, criterios, pasantías, hitos, evaluaciones, documentos versionados, conversaciones, notificaciones y auditoría. No modifica la base anterior. La semilla de demostración solo se ejecuta con `--demo` y no sobrescribe usuarios existentes.
