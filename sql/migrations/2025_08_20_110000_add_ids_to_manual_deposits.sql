-- Add transaction_id and invoice_id to the manual_deposits table to link them correctly

ALTER TABLE `manual_deposits`
ADD COLUMN `transaction_id` INT(11) NULL AFTER `user_id`,
ADD COLUMN `invoice_id` INT(11) NULL AFTER `transaction_id`;
