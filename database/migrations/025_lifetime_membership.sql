ALTER TABLE membership_plans
  MODIFY type ENUM('single', 'pack', 'monthly', 'credit', 'voucher', 'lifetime') NOT NULL;

INSERT INTO membership_plans (
  public_id, slug, name, description, type, price, currency, entries, duration_days, max_guests, is_active, sort_order
)
SELECT
  UUID(), 'lifetime', 'Doživotní členství', 'Rare. Neomezené vstupy bez konce. Jen ruční přiřazení.',
  'lifetime', '0.00', 'CZK', NULL, NULL, 1, 1, 0
WHERE NOT EXISTS (
  SELECT 1 FROM membership_plans WHERE slug = 'lifetime'
);
