-- Místo prostoru (název a adresa, kterou admin zadává).
ALTER TABLE rooms
    ADD COLUMN location VARCHAR(190) DEFAULT NULL AFTER name;
