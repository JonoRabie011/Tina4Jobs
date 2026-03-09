CREATE TABLE tina4_failed_jobs
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL,
    connection VARCHAR(255) NOT NULL,
    queue      VARCHAR(255) NOT NULL,
    payload    TEXT NOT NULL,
    exception  TEXT NOT NULL,
    failed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (uuid)
);

CREATE TABLE tina4_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  queue VARCHAR(255) NOT NULL,
  payload TEXT NOT NULL,
  attempts INT NOT NULL,
  reserved_at INT,
  available_at INT NOT NULL,
  created_at INT NOT NULL
);

CREATE INDEX jobs_queue_index ON tina4_jobs (queue);