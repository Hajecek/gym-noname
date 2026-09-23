-- Základ je 150 Kč za vstup. Balíček je o 10 % levnější, měsíc odpovídá osmi vstupům.
UPDATE membership_plans
SET price = '150.00',
    description = 'Jeden vstup za 150 Kč, stejně jako jeden blok.'
WHERE slug = 'single';

UPDATE membership_plans
SET price = '1350.00',
    description = 'Deset vstupů na 180 dní. 135 Kč za vstup, o 150 Kč méně než deset jednorázových.'
WHERE slug = 'pack10';

UPDATE membership_plans
SET price = '1200.00',
    description = 'Neomezené vstupy na 30 dní. V ceně je osm vstupů, další už zdarma.'
WHERE slug = 'monthly';
