# MDRRMO Incident Reporting API (prototype)

This PHP REST API connects the existing Hatchable constituents website to a Render PostgreSQL database with PostGIS. It is a learning prototype only. Do not submit real emergency reports or real personal information; it is not an operational rescue or dispatch service.

## File structure

```text
mdrrmo-api/
├── public/
│   ├── api/incidents/
│   │   ├── create.php
│   │   ├── list.php
│   │   └── view.php
│   └── index.php
├── src/
│   ├── database.php
│   ├── db_connection.php
│   ├── http.php
│   ├── incident_validation.php
│   └── migrate.php
├── migrations/001_create_incident_reports.sql
├── test-db.php
├── .dockerignore
├── .gitignore
├── Dockerfile
├── docker-entrypoint.sh
└── README.md
```

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api/incidents/create.php` | Validate and store an incident report. Requires JSON and an `Idempotency-Key` UUID header. |
| `GET` | `/api/incidents/list.php` | Return a paginated list of redacted report summaries. |
| `GET` | `/api/incidents/view.php?id={uuid}` | Return one redacted report summary. |

The public read endpoints omit reporter names, phone numbers, descriptions, barangay/address, and exact coordinates. The create endpoint returns only the new report ID, status, and timestamp. Photos stay local to the website browser and are never uploaded.

## Render setup

1. The `mdrrmo-api` service uses the repository root `Dockerfile` and the existing Render `DATABASE_URL`; keep its value private.
2. The container applies the idempotent SQL migration before Apache starts. It enables PostGIS and creates the incident reports table and indexes.
3. The default CORS allow-list is `https://municipalitycity.hatchable.site`. If the site origin changes, set `CORS_ALLOWED_ORIGINS` to the exact comma-separated origins in Render.
4. Render is connected to `pitogo143/mdrrmo-api`. If automatic deploys are disabled, use **Manual Deploy → Deploy latest commit**.
5. API service: `https://mdrrmo-api-eexs.onrender.com`.

## Hatchable form payload

The form posts to `https://mdrrmo-api-eexs.onrender.com/api/incidents/create.php`:

```json
{
  "reporter_name": "Demo Name",
  "contact_number": "+63 900 000 0000",
  "incident_type": "flood",
  "description": "Demo description",
  "persons_affected": 0,
  "latitude": 10.0,
  "longitude": 123.0,
  "accuracy_meters": 25.0
}
```

`barangay` and `address` are sent only when supplied. The UI uses an idempotency key on retries. Setup did not submit any sample report.

## Local connection check

Run `php test-db.php` from the repository root after setting `DATABASE_URL` and enabling the PHP `pdo_pgsql` extension. It only runs `SELECT 1`, creates no report data, and is CLI-only; the container serves files from `public/`.

## Database

The migration creates `incident_reports` with report details, a PostGIS `geometry(Point, 4326)`, an idempotency key, and timestamps. Do not expose the database URL, password, raw incident records, or full database errors in browser responses or logs.
