CREATE TABLE failed_jobs
(
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid       TEXT NOT NULL,
    connection TEXT NOT NULL,
    queue      TEXT NOT NULL,
    payload    TEXT NOT NULL,
    exception  TEXT NOT NULL,
    failed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (uuid)
);

CREATE TABLE jobs
(
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    queue        TEXT    NOT NULL,
    payload      TEXT    NOT NULL,
    attempts     INTEGER NOT NULL,
    reserved_at  INTEGER,
    available_at INTEGER NOT NULL,
    created_at   INTEGER NOT NULL
);

CREATE INDEX jobs_queue_index ON jobs (queue);