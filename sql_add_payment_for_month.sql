ALTER TABLE rental_payments ADD COLUMN payment_for_month DATE DEFAULT NULL;
UPDATE rental_payments SET payment_for_month = payment_date WHERE payment_for_month IS NULL;
