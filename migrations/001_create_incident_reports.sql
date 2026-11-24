CREATE EXTENSION IF NOT EXISTS postgis;

CREATE TABLE IF NOT EXISTS incident_reports (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    idempotency_key UUID NOT NULL UNIQUE,
    reporter_name VARCHAR(120) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    incident_type VARCHAR(40) NOT NULL CHECK (
        incident_type IN ('flood', 'fire', 'landslide', 'earthquake', 'medical', 'storm', 'other')
    ),
    description TEXT NOT NULL,
    barangay VARCHAR(120),
    address VARCHAR(250),
    persons_affected INTEGER NOT NULL DEFAULT 0 CHECK (persons_affected >= 0),
    latitude DOUBLE PRECISION NOT NULL CHECK (latitude BETWEEN -90 AND 90),
    longitude DOUBLE PRECISION NOT NULL CHECK (longitude BETWEEN -180 AND 180),
    accuracy_meters NUMERIC(10, 2) NOT NULL CHECK (accuracy_meters > 0),
    location GEOMETRY(Point, 4326) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'received' CHECK (
        status IN ('received', 'in_review', 'resolved')
    ),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS incident_reports_created_at_idx
    ON incident_reports (created_at DESC);

CREATE INDEX IF NOT EXISTS incident_reports_location_gix
    ON incident_reports USING GIST (location);
