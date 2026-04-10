# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Full-stack monorepo:

| Directory | Stack | Port | Role |
|-----------|-------|------|------|
| `backend/` | Laravel 13 (PHP-FPM) | — | Main app: Filament admin, business logic, REST API |
| `fastify-backend/` | Fastify (Node.js) | 3200 | I Ching + AI image generation only |
| `frontend/` | Next.js (TypeScript) | 3111 | Primary frontend |

**Production URL**: `https://travel.xingke888.com/web` (Laravel served at `/web` path prefix via Nginx)

**PM2 manages**: `peking-fastify` and `peking-frontend` only. Laravel runs via PHP-FPM (pool: `/etc/php/8.4/fpm/pool.d/peking.conf`, socket: `/run/php/php8.4-fpm-peking.sock`).

## Commands

### Laravel (`backend/`)
```bash
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback last migration
php artisan make:migration name  # Create migration
php artisan make:controller name # Create controller
php artisan test                 # Run tests
composer install                 # Install dependencies
# No artisan serve — Laravel runs via PHP-FPM in both dev and production
```

### Fastify (`fastify-backend/`)
```bash
npm start        # Start production server
npm run dev      # Start with hot reload (node --watch)
```

### Next.js frontend (`frontend/`)
```bash
npm run dev    # Start dev server on 0.0.0.0:3111
npm run build  # Build for production
npm run lint   # ESLint
```

## Architecture

### Production Serving (Nginx)

Nginx at `/etc/nginx/sites-available/travel.xingke888.com`:
- `/web/` → PHP-FPM via fastcgi_pass (SCRIPT_FILENAME=backend/public/index.php, `fastcgi_param HTTPS "on"` forced because Cloudflare terminates SSL)
- `/api3200/` → proxy_pass to Fastify :3200
- `/` → proxy_pass to Next.js :3111

`APP_URL=https://travel.xingke888.com/web` is critical — Filament uses it to generate redirect URLs. Changing this breaks the admin panel.

`trustProxies(at: '*')` in `bootstrap/app.php` is required for Cloudflare + Nginx proxy chain to work (CSRF, HTTPS detection).

### Authentication Flow

All auth is delegated to an **external Auth Center** (SSO). Laravel does not handle passwords directly.

```
frontend → Laravel /api/auth/login → Auth Center (https://mo.xingke888.com/api)
```

- Auth Center issues `accessToken` + `refreshToken` (JWT, dual-token)
- Login field is `identifier` (username or email), not `email`
- Laravel config: `AUTH_CENTER_URL` in `.env`, `config/services.auth_center.url`

### JWT Verification

- **Laravel**: `JwtAuthMiddleware` using `firebase/php-jwt`, verifies with `config('jwt.secret')`
- **Fastify**: global `preHandler` hook in `src/plugins/jwt.js` — all routes protected by default; public routes listed in `PUBLIC_ROUTES` set as `'METHOD:/path'`

### User Sync (SSO Mapping)

Laravel maintains a local `users` table linked to the Auth Center via `sso_users`:
```
sso_users.sso_user_id   → Auth Center user ID (JWT payload.sub)
sso_users.local_user_id → local users.id
```
On first login, a local `User` record is created and mapped in `sso_users`.

### Filament Admin (`/web/admin`)

- Filament v5 resources live in `backend/app/Filament/Resources/`
- `User` model implements `FilamentUser` interface (required — without it, login returns 403)
- Navigation groups: 景区管理 (ScenicSpot, ScenicNode, ScenicFacility, CommentaryPoint)
- `AdminPanelProvider` at `backend/app/Providers/Filament/AdminPanelProvider.php`

### Scenic & Commentary System

**Models**: `ScenicSpot` → `ScenicNode` (tree, self-referential parent_id) → `CommentaryPoint`

**BaiduMapService** (`backend/app/Services/BaiduMapService.php`):
- `searchByRegion(region, maxPages)` — Baidu Maps v3 `/place/v3/region` API
- `collectSubSpots(name, region, radius)` — collect child attractions
- `getDetail(uid)` — get place detail by Baidu UID

**Filament Actions**:
- `CollectSubSpotsAction` — fetches child nodes from Baidu Maps API
- `CollectFacilitiesAction` — fetches nearby facilities (10 types)
- `OptimalRouteAction` — nearest-neighbor TSP to sort child nodes by travel order
- `GenerateCommentaryAction` — generates commentary skeleton from Baidu place detail (on EditScenicNode page header)
- Commentary form has a "生成讲解词骨架" hintAction button on the content field

**Laravel API routes** (all public, no auth):
```
GET /api/scenic/spots          → ScenicApiController::spots
GET /api/scenic/sub-spots      → ScenicApiController::subSpots
GET /api/commentary/spot/{id}  → CommentaryPointController::bySpot
GET /api/commentary/node/{id}  → CommentaryPointController::byNode
GET /api/commentary/nearby     → CommentaryPointController::nearby (Haversine GPS, ?lat=&lng=&radius=)
```

**Fastify routes** (only remaining):
```
GET|POST /iching/predict
GET /iching/trigrams
GET /iching/hexagrams/*
POST /image/generate
```

### Database

Schema managed by **Laravel migrations only**. DB: `travel` (MySQL).

Key tables: `users`, `sso_users`, `scenic_spots`, `scenic_nodes`, `scenic_facilities`, `commentary_points`, `cache`, `jobs`, `sessions`

### Frontend API Clients (`frontend/`)

Two clients in `lib/api/client.ts`:
- `laravelClient` → `NEXT_PUBLIC_LARAVEL_API_URL`
- `fastifyClient` → `NEXT_PUBLIC_FASTIFY_API_URL`

Both auto-inject `accessToken` from localStorage. On 401, both refresh via Laravel's `/token/refresh`.

### OAuth / WeChat Login

WeChat OAuth redirects to `/oauth/callback` with `accessToken` and `refreshToken` as query params. Callback page (`app/oauth/callback/page.tsx`) stores tokens and fetches user info.

## Reference Documents (`docs/`)

| 文件 | 说明 |
|------|------|
| `docs/baidu-place-api-v3.pdf` | 百度地图地点检索 API 3.0 完整文档（region / around / detail 接口参数、返回字段说明） |
| `docs/baidu-poi-categories.tsv` | 百度地图 POI 一级/二级行业分类表 |

### 行业分类要点

`classified_poi_tag` 字段值格式为 `一级分类;二级分类`，一级分类完整列表（30 类）：
美食、酒店、购物、生活服务、丽人、**旅游景点**、休闲娱乐、运动健身、教育培训、文化传媒、医疗、汽车服务、交通设施、金融、房地产、公司企业、政府机构、出入口、自然地物、行政地标、门址、道路、铁路、行政界线、其他线要素、行政区划、水系、绿地、标注、公交线路、电子眼

**旅游景点** 二级分类：公园、动物园、植物园、游乐园、博物馆、水族馆、海滨浴场、文物古迹、教堂、风景区、景点、寺庙、其他

子景点/孙景点采集时用 `str_starts_with($tag, '旅游景点')` 过滤 around 周边搜索结果（detail API 的 children 不过滤，其已属于官方子点）。

## Key Environment Variables

**`backend/.env`**
```
APP_URL=https://travel.xingke888.com/web   # Must include /web prefix
AUTH_CENTER_URL=https://mo.xingke888.com/api
JWT_SECRET=...
JWT_ALGO=HS256
DB_CONNECTION=mysql
DB_DATABASE=travel
BAIDU_MAP_AK=...
SESSION_DOMAIN=.travel.xingke888.com
```

**`frontend/.env.local`**
```
NEXT_PUBLIC_LARAVEL_API_URL=https://travel.xingke888.com/web/api
NEXT_PUBLIC_FASTIFY_API_URL=https://travel.xingke888.com/api3200
```

**`fastify-backend/.env`**
```
PORT=3200
HOST=0.0.0.0
JWT_SECRET=...    # Must match Laravel's JWT_SECRET
```
