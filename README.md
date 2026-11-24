# MDRRMO Incident Reporting API (prototype)

This PHP REST API accepts incident reports from the existing Hatchable constituents website and stores them in the Render PostgreSQL database with a PostGIS point. It is a learning prototype only. **Do not submit real emergency reports or real personal information. This is not an operational rescue or dispatch service.**

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

The public read endpoints deliberately omit reporter names, phone numbers, descriptions, barangay/address, and exact coordinates. The create endpoint returns only the new report ID, status, and timestamp. Photos are never uploaded; the website may show a local preview only.

## Render setup

1. The `mdrrmo-api` web service should use the repository's root `Dockerfile`. It already has a `DATABASE_URL` variable in Render; keep its value private. For services in the same Render region, use the database's internal connection URL.
2. Keep `DATABASE_URL` configured on the service. The container runs the idempotent SQL migration before Apache starts. It enables PostGIS and creates the `incident_reports` table and indexes.
3. The default CORS allow-list is `https://municipalitycity.hatchable.site`. If the site uses a different origin, set `CORS_ALLOWED_ORIGINS` to the exact comma-separated website origins in Render. This setting is not a secret.
4. Push the files to the `main` branch of `pitogo143/mdrrmo-api`. Render is already connected to that repository. If automatic deploys are disabled, use **Manual Deploy → Deploy latest commit**.
5. The service URL is `https://mdrrmo-api-eexs.onrender.com`.

## Hatchable form payload

The form sends JSON to `https://mdrrmo-api-eexs.onrender.com/api/incidents/create.php` with these fields:

```json
{
  "reporter_name": "Sample Person",
  "contact_number": "+63 900 000 0000",
  "incident_type": "flood",
  "description": "Sample description",
  "persons_affected": 0,
  "latitude": 10.0,
  "longitude": 123.0,
  "accuracy_meters": 25.0
}
```

`barangay` and `address` are sent only when supplied. The UI uses a stable idempotency key for retries, so retrying a timed-out request does not create another report. The code does not submit any sample record during setup.

## Database

The migration creates `incident_reports` with contact and report details, a PostGIS `geometry(Point, 4326)`, a retry key, and timestamps. Do not expose the database URL, database password, raw incident records, or full database errors in browser responses or logs.
