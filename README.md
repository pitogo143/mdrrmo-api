# MDRRMO Incident Reporting API (prototype)

This PHP REST API connects the existing Hatchable constituents website to a Render PostgreSQL database with PostGIS. It is a learning prototype only. Do not submit real emergency reports or real personal information; this is not an operational rescue or dispatch service.

## Repository structure (matches Step 1)

```text
mdrrmo-api/
├── public/
│   └── api/
│       └── incidents/
│           ├── create.php
│           ├── list.php
│           └── view.php
├── src/
│   ├── database.php
│   └── db_connection.php
├── .dockerignore
├── .gitignore
├── README.md
├── test-db.php
└── Dockerfile
```

HTTP/CORS handling, input validation, and idempotent database setup are kept in `src/database.php` so the tree matches the lab guide.

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api/incidents/create.php` | Validate and store a report. Requires JSON and an `Idempotency-Key` UUID header. |
| `GET` | `/api/incidents/list.php` | Return a paginated list of redacted report summaries. |
| `GET` | `/api/incidents/view.php?id={uuid}` | Return one redacted report summary. |

The public read endpoints omit reporter names, phone numbers, descriptions, addresses, and exact coordinates. Photos stay local in the website browser and are never uploaded.

## Render setup

1. The service uses the repository-root `Dockerfile` and the existing Render `DATABASE_URL`; keep its value private.
2. The container initializes PostGIS and the incident table before Apache starts.
3. The default CORS allow-list is `https://municipalitycity.hatchable.site`. If the site origin changes, set `CORS_ALLOWED_ORIGINS` to the exact comma-separated origins in Render.
4. If automatic deploys are disabled, use **Manual Deploy → Deploy latest commit**.
5. API service: `https://mdrrmo-api-eexs.onrender.com`.

## Local database check

Run `php test-db.php` from the repository root after setting `DATABASE_URL` and enabling PHP `pdo_pgsql`. It only runs `SELECT 1` and creates no report data.

## Prototype safety

The Hatchable form posts to `/api/incidents/create.php` with reporter name, contact number, incident type, description, persons affected, latitude, longitude, and GPS accuracy. `barangay` and `address` are optional. Retries use an idempotency key. Do not submit real personal or emergency information. This prototype is not monitored for emergency response.