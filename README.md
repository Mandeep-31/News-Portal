<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## News backend

The admin control room is available at `/control-room/login`; after signing in, use the dashboard to create, schedule, edit, and remove news. Admin credentials are configured with `ADMIN_EMAIL` and `ADMIN_PASSWORD_HASH`. News accepts up to three JPG, PNG, or WebP images (5 MB each), uses no more than six distinct categories, and allows schedules up to 24 hours ahead. Priority is an integer from 0 to 10; higher values sort first. “Homepage feature” is exposed separately for frontend display decisions.

Public React API (JSON):

- `GET /api/news?q=search&category=World&per_page=12` — published news from the last seven days, sorted by priority then publication time. All query parameters are optional.
- `GET /api/news/categories` (also `GET /api/categories`) — category records with `id`, `name`, and `slug`.
- `GET /api/news/{slug}` — one published story, with image URLs.
- `GET /api/banners` — active banners in display order; no login is required.

Successful JSON responses use `success`, `message`, `data`, and `errors`. News list responses put stories in `data` and pagination details in `meta`; Laravel's previous top-level pagination fields and `links` are retained for compatibility. A story includes `id`, `title`, `slug`, `excerpt`, `description` (same content as `body`), `body`, `caption`, `category`, `author`, `added_by`, `priority`, `is_featured`, `published_at`, `image` (primary URL), and `images` (URL array). Errors use `success: false`, `message`, and `errors` with an appropriate HTTP error status.

Admins manage categories at `/control-room/categories` or through the protected `/api/admin/categories` resource routes. There can be at most six category records. News forms select an existing category; creating a news item requires `category_id`, and cannot create a category. The public category API returns the available records, and news responses include both the category name and ID.

Admins manage banners from the control-room dashboard or the protected `GET/POST /api/admin/banners` and `PUT/DELETE /api/admin/banners/{id}` endpoints. A banner has a title, image, optional caption and link URL, active flag, and display order. Only active banners appear at `GET /api/banners` and on the public home page.

Admin news API requests accept `title`, `description` (or `body`), `category_id`, optional `caption` and `author`, and `priority`. Use `publish_now=true` to publish immediately with the server's current timestamp, or provide `published_at` to schedule within the existing 24-hour limit. Send uploads as multipart `image` or `images[]` fields; the three-image maximum still applies. Banner create/update uses multipart form data with a required image on create and optional replacement image on update.

Run `php artisan storage:link` once so uploaded images can be served from the public disk. Run `php artisan schedule:work` locally, and configure the Laravel scheduler in production, to delete news older than seven days and its images. The scheduled cleanup command is `news:prune-expired`.

Set `CORS_ALLOWED_ORIGINS` to the React app's origin(s), comma separated, for example `http://localhost:5173` during local development.

### Admin authentication in Postman

For repeatable Postman access, use Sanctum bearer tokens instead of the browser session login:

1. `POST http://127.0.0.1:8000/api/admin/login` with **Body → raw → JSON**:

   ```json
   {"email":"your configured admin email","password":"your admin password"}
   ```

2. In the Postman collection, set **Authorization → Bearer Token** to `{{admin_token}}`. Add this **Post-response** script to the **login request only**:

   ```javascript
   const body = pm.response.json();
   if (pm.response.code === 200 && body.success && body.data?.token) {
     pm.collectionVariables.set('admin_token', body.data.token);
   }
   ```

Do not add token-clearing code to collection-level scripts or other requests. Protected requests do not revoke or clear the token; Sanctum tokens have no configured automatic expiration here and remain valid until logout. Keep `admin_token` in one variable scope; a blank environment variable with the same name can override the collection variable.

3. Send protected requests with the collection authorization, for example `GET /api/admin/dashboard` or `GET /api/admin/posts`. Admin post create, edit, and delete endpoints are `POST /api/admin/posts`, `PUT /api/admin/posts/{id}`, and `DELETE /api/admin/posts/{id}`.

4. Send `POST /api/admin/logout` with the same collection authorization. Add this **Post-response** script to the **logout request only**:

   ```javascript
   if (pm.response.code >= 200 && pm.response.code < 300) {
     pm.collectionVariables.unset('admin_token');
   }
   ```

The token is returned once by login and stored in the collection; logout revokes that token. The admin token is issued only after checking the configured `ADMIN_EMAIL` and `ADMIN_PASSWORD_HASH`, and carries an `admin` ability. Other Sanctum tokens cannot call admin endpoints. Sanctum and the `personal_access_tokens` migration are already present; run `php artisan migrate` if that migration has not yet been applied.

The browser flow remains session based and CSRF protected. For its optional Postman workflow, set `X-Use-Web-Session: true` on each `/control-room/*` request, keep Postman's cookie jar enabled, and use the same host consistently (`127.0.0.1` for every request). GET `/control-room/login` first and add this Post-response script to save the form's CSRF value without copying it manually:

```javascript
const match = pm.response.text().match(/name="_token"\s+value="([^"]+)"/);
if (match) pm.collectionVariables.set('session_csrf', match[1]);
```

POST `/control-room/login` as form data with `_token={{session_csrf}}`, `email`, and `password`; Postman's cookie jar sends the matching session cookie. After login, GET `/control-room/dashboard` with the same opt-in header and run the same script to refresh the token before POST `/control-room/logout`. Login and logout rotate session security state, so fetch the next form token before the next form POST. The API bearer token is separate from both the session cookie and CSRF token.

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
