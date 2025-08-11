-- Failed jobs table
CREATE TABLE failed_jobs (
                             id         SERIAL PRIMARY KEY,
                             uuid       TEXT NOT NULL UNIQUE,
                             connection TEXT NOT NULL,
                             queue      TEXT NOT NULL,
                             payload    TEXT NOT NULL,
                             exception  TEXT NOT NULL,
                             failed_at  TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
                      id           SERIAL PRIMARY KEY,
                      queue        TEXT NOT NULL,
                      payload      TEXT NOT NULL,
                      attempts     INTEGER NOT NULL,
                      reserved_at  TIMESTAMP WITH TIME ZONE,
                      available_at TIMESTAMP WITH TIME ZONE NOT NULL,
                      created_at   TIMESTAMP WITH TIME ZONE NOT NULL
);

-- Index on queue for faster lookups
CREATE INDEX jobs_queue_index ON jobs (queue);