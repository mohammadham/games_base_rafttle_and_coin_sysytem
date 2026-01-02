-- SQL Script to add Persian language support
-- این اسکریپت را در دیتابیس خود اجرا کنید

-- 1. Add is_rtl column to languages table if not exists
ALTER TABLE `languages` 
ADD COLUMN IF NOT EXISTS `is_rtl` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_default`;

-- 2. Update existing RTL languages
UPDATE `languages` SET `is_rtl` = 1 WHERE `code` IN ('fa', 'ar', 'he', 'ur', 'ps', 'ku');

-- 3. Check if Persian language exists
SELECT COUNT(*) INTO @fa_exists FROM `languages` WHERE `code` = 'fa';

-- 4. Insert Persian language if it doesn't exist
INSERT INTO `languages` (`name`, `code`, `is_default`, `image`, `is_rtl`, `created_at`, `updated_at`)
SELECT * FROM (SELECT 'فارسی' as name, 'fa' as code, 1 as is_default, 'iran_flag.png' as image, 1 as is_rtl, NOW() as created_at, NOW() as updated_at) AS tmp
WHERE @fa_exists = 0;

-- 5. Set Persian as default language (optional - remove this if you want to keep English as default)
UPDATE `languages` SET `is_default` = 0 WHERE `code` != 'fa';
UPDATE `languages` SET `is_default` = 1 WHERE `code` = 'fa';

-- 6. Verify the changes
SELECT `id`, `name`, `code`, `is_default`, `is_rtl`, `image` FROM `languages` ORDER BY `is_default` DESC, `id` ASC;
