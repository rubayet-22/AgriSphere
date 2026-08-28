--  AgriSphere - Decorator Pattern feature (notification delivery)
--  Adds THREE columns to the existing `user` table: the customer's
--  notification delivery preferences. Does not modify or drop any
--  existing table, column or row, and does NOT touch the `notification`
--  table - notification rows keep exactly the shape they already have.
--
--  Why there is no `notify_in_app` column
--    In-App delivery is always on: it IS the BaseNotification, the
--    notification row the Observer already writes. A column that is
--    always 1 would be dead data, so the checkbox is shown ticked and
--    disabled instead.
--
--  Why these live on `user` and not in a new table
--    UserRepository::listActiveMembers() already SELECTs from `user`
--    once per discount event, so the Decorator layer gets the
--    preferences for free - no extra query, no join.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\decorator_migration.sql
--
--  Safe to run more than once (ADD COLUMN IF NOT EXISTS).

USE `cse311 lab project`;

-- user.notify_email
--   1 = wrap the notification with EmailDecorator. On by default.
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `notify_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `last_login`;

-- user.notify_sms
--   1 = wrap the notification with SmsDecorator. Off by default.
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `notify_sms` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notify_email`;

-- user.notify_push
--   1 = wrap the notification with PushNotificationDecorator. Off by default.
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `notify_push` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notify_sms`;

SELECT 'Notification preference columns ready.' AS Status;
