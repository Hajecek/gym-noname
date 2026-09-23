UPDATE rooms
SET max_persons = 2
WHERE max_persons > 2;

ALTER TABLE rooms
  MODIFY max_persons TINYINT UNSIGNED NOT NULL DEFAULT 2;
