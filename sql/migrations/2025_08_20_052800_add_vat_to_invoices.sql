-- Add VAT columns to the invoices table and adjust amount columns

-- First, add the new columns
ALTER TABLE `invoices`
ADD COLUMN `subtotal` DECIMAL(10,2) NOT NULL AFTER `status`,
ADD COLUMN `vat_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
ADD COLUMN `vat_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `vat_percentage`;

-- For existing records, we assume the old `amount` was the total and subtotal, with no VAT.
-- We will copy the value from `amount` to `subtotal` for old records.
UPDATE `invoices` SET `subtotal` = `amount` WHERE `subtotal` = 0.00;

-- Now, rename the old `amount` column to `total_amount`
ALTER TABLE `invoices`
CHANGE COLUMN `amount` `total_amount` DECIMAL(10,2) NOT NULL;

-- Also, let's add a `total_amount` column to the transactions table for consistency
ALTER TABLE `transactions`
ADD COLUMN `total_amount` DECIMAL(10,2) NULL AFTER `amount`;

-- For existing transactions, we can copy the amount to total_amount for deposits
UPDATE `transactions` SET `total_amount` = `amount` WHERE `type` = 'deposit' AND `total_amount` IS NULL;
