# Instrucciones del Proyecto

Este documento define el flujo base para trabajar en `e:\sapp`.
Usalo como referencia principal.

## 1. Objetivo

Mantener y evolucionar el sistema de solicitudes con estas reglas:
- Cambios pequenos, probados y reversibles.
- Seguridad y validaciones primero.
- Documentacion corta y accionable.

## 2. Preparacion local

Requisitos:
- PHP 8.2 o superior
- Composer
- Node.js 20 o superior
- Motor SQL compatible (MySQL/MariaDB)

Pasos:

```bash
composer run setup
```

Si el `.env` no existe, se crea desde `.env.example`.

## 3. Comandos diarios

Desarrollo completo:

```bash
composer run dev
```

Incluye:
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0`
- `npm run dev`

Pruebas:

```bash
composer run test
```

Formato de codigo:

```bash
./vendor/bin/pint
```

## 4. Flujo de trabajo recomendado

1. Crear rama para el cambio.
2. Implementar cambio minimo funcional.
3. Ejecutar pruebas y revisar logs.
4. Documentar solo decisiones relevantes.
5. Preparar PR con alcance acotado.

## 5. Criterios de calidad minimos

- Sin errores de sintaxis.
- Validaciones en Request/FormRequest para entradas de usuario.
- Consultas optimizadas (evitar N+1 cuando aplique).
- Pruebas o evidencia manual clara para el comportamiento nuevo.
- Sin credenciales en codigo o repositorio.

## 6. Seguridad y entorno

- No subir secretos a Git.
- Mantener variables sensibles en `.env`.
- En produccion: usar `APP_DEBUG=false`.
- Confirmar configuracion de CAPTCHA y autenticacion segun entorno.

## 7. Despliegue (resumen)

Secuencia base:
1. Backup de base de datos.
2. Modo mantenimiento.
3. Actualizar codigo.
4. Ejecutar migraciones requeridas.
5. Limpiar y reconstruir caches.
6. Reiniciar workers/servicios necesarios.
7. Salir de mantenimiento y validar flujo critico.

Comandos utiles:

```bash
php artisan down
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

## 8. Mapa de documentacion

Mantener estos documentos especializados:
- `REQUEST.md`: flujo operativo y reglas para solicitudes.
- `DEPLOY_CHECKLIST.md`: checklist operativo de despliegue.
- `PRODUCCION_COMANDOS.md`: comandos de produccion.
- `CONFIGURACION_RECAPTCHA.md`: configuracion CAPTCHA v2.
- `RECAPTCHA_ENTERPRISE.md`: configuracion enterprise.
- `TECHNICIAN_MODULE_README.md`: modulo de tecnicos.
- `SISTEMA_CONSULTA_PUBLICA_V2.md`: consulta publica.

Regla:
- Este archivo define el proceso general.
- Los demas archivos guardan detalle tecnico por modulo.

Alias operativo:
- Cuando se use la palabra `solicitud`, se debe aplicar `REQUEST.md`.

## 9. Regla de actualizacion documental

Cuando se cambie comportamiento funcional:
1. Actualizar este archivo si cambia el proceso general.
2. Actualizar el documento del modulo afectado.
3. Evitar duplicar la misma instruccion en varios archivos.

## 10. Convenciones de escritura

- Titulos cortos y directos.
- Pasos numerados para procesos.
- Comandos en bloques de codigo.
- Evitar texto promocional o redundante.
- Mantener lenguaje tecnico simple.
