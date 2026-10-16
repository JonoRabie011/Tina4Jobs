-- Failed jobs table
CREATE TABLE tina4_failed_jobs (
                             id         SERIAL PRIMARY KEY,
                             uuid       TEXT NOT NULL UNIQUE,
                             connection TEXT NOT NULL,
                             queue      TEXT NOT NULL,
                             payload    TEXT NOT NULL,
                             exception  TEXT NOT NULL,
                             failed_at  TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table. reserved_at / available_at / created_at are Unix timestamps (integers),
-- which is what the queue writes and compares against.
CREATE TABLE tina4_jobs (
                      id           SERIAL PRIMARY KEY,
                      queue        TEXT NOT NULL,
                      payload      TEXT NOT NULL,
                      attempts     INTEGER NOT NULL,
                      reserved_at  BIGINT,
                      available_at BIGINT NOT NULL,
                      created_at   BIGINT NOT NULL
);

-- Index on queue for faster lookups
CREATE INDEX jobs_queue_index ON tina4_jobs (queue);