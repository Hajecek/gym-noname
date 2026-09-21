-- Souřadnice studia pro výběr nejbližšího fitka v aplikaci.
ALTER TABLE rooms
    ADD COLUMN latitude DECIMAL(9, 6) DEFAULT NULL AFTER location,
    ADD COLUMN longitude DECIMAL(9, 6) DEFAULT NULL AFTER latitude;
