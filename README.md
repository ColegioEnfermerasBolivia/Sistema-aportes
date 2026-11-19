<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## API de afiliados (v1)

La API solo permite consultar afiliados activos y devuelve `ci`, `nombres`,
`apellidos`, `especialidad` (lista de especialidades activas) y `departamento`.
No devuelve registros académicos que no sean de grado `ESPECIALIDAD`. Las sedes
regionales usan el departamento de su colegio padre; si no tienen padre, el
departamento se devuelve como `null`.

Configura una clave secreta en `.env` (no la compartas ni la incluyas en el
repositorio):

```env
API_PUBLIC_KEY=pega_aqui_una_clave_aleatoria
```

En PowerShell puedes generar una clave aleatoria de 256 bits con:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Luego reinicia el servidor o limpia la configuración cacheada con
`php artisan config:clear`. La API falla de forma cerrada si no se configura
`API_PUBLIC_KEY`.

Con el servidor local iniciado con `php artisan serve`, la URL es
`http://127.0.0.1:8000/api/v1/afiliados`. En Postman o REST Client selecciona
`GET`, agrega `Accept: application/json` y envía la clave en el encabezado
`X-API-Key` (no en la URL). Opcionalmente puedes filtrar por CI exacto con
`?ci=1234567` o cambiar el tamaño de página con `?per_page=10`; el máximo es
30 resultados por página. Se permiten 30 solicitudes por minuto desde cada IP;
el exceso devuelve HTTP 429. Las respuestas no se almacenan en caché.

Esta ruta expone números de CI y otros datos personales a cualquier persona que
obtenga la clave. Para producción, entrega la clave solo a integraciones
autorizadas, usa HTTPS, restringe el acceso de red cuando sea posible, rota la
clave si se filtra y configura correctamente las IPs de proxies confiables antes
de depender de la limitación por IP. Una API key por sí sola no convierte en
segura la publicación irrestricta de datos personales.
