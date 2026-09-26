-- Čistá částka zůstává v payments.amount. Poplatek a částka od zákazníka jsou vedle.
ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount;

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS charged_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER fee_amount;
