-- #####################################################################
-- ##                                                                 ##
-- ##   !!!  DESTRUCTIVE  -  DO NOT RUN ON YOUR REAL DATABASE  !!!    ##
-- ##                                                                 ##
-- ##   This file WIPES the database before inserting demo data. It   ##
-- ##   DELETEs every user, product, order, cart, payment, discount   ##
-- ##   and notification (see the cleanup block below).               ##
-- ##                                                                 ##
-- ##   It is ONLY for setting up a throwaway demo database from      ##
-- ##   scratch. It is NOT a migration and NOT a syntax-check file.   ##
-- ##                                                                 ##
-- ##   Take a backup first:                                          ##
-- ##     mysqldump -u root --databases "cse311 lab project" \        ##
-- ##       --routines --triggers > database\backups\before.sql       ##
-- ##                                                                 ##
-- ##   NOTE: it also fails partway on current schemas - it inserts   ##
-- ##   a `phone` column into `farmer`, which no longer exists. So it ##
-- ##   deletes everything and only partly refills. Fix that line     ##
-- ##   before ever running it.                                       ##
-- ##                                                                 ##
-- #####################################################################
-- =====================================================================
--  AgriSphere - Demo / sample data
-- =====================================================================
--  Fills the database with realistic content so the whole site can be
--  clicked through: farmers, customers, land, products (approved,
--  pending and rejected), inventory, carts, orders across six months,
--  payments and AI logs.
--
--  HOW TO RUN
--    phpMyAdmin  ->  select `cse311 lab project`  ->  Import  ->  this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\sample_data.sql
--
--  WARNING
--    The cleanup block below DELETES all existing users (except the
--    admin, User_id = 1), products, orders and carts, so the script can
--    be run repeatedly and always give the same demo state.
--    Remove that block if you have real data you want to keep.
--
--  Prerequisite: run database/setup.sql first so every table exists.
-- =====================================================================

USE `cse311 lab project`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Cleanup (see the warning above)
-- ---------------------------------------------------------------------
-- Foreign key checks are off below, so cascades do NOT fire: every table
-- has to be cleared explicitly, including the Observer feature tables.
DELETE FROM `notification`;
DELETE FROM `product_discount`;
DELETE FROM `payment`;
DELETE FROM `order_items`;
DELETE FROM `customer_order`;
DELETE FROM `customer_cart`;
DELETE FROM `farm_inventory`;
DELETE FROM `farm_product`;
DELETE FROM `land`;
DELETE FROM `user_block`;
DELETE FROM `pending_registration`;
DELETE FROM `otp_verification`;
DELETE FROM `ai_log`;
DELETE FROM `farmer`;
DELETE FROM `customer`;
DELETE FROM `user` WHERE `User_id` <> 1;

ALTER TABLE `farm_product`   AUTO_INCREMENT = 1;
ALTER TABLE `customer_order` AUTO_INCREMENT = 1;
ALTER TABLE `customer_cart`  AUTO_INCREMENT = 1;
ALTER TABLE `order_items`    AUTO_INCREMENT = 1;
ALTER TABLE `payment`        AUTO_INCREMENT = 1;
ALTER TABLE `land`           AUTO_INCREMENT = 1;
ALTER TABLE `ai_log`         AUTO_INCREMENT = 1;
ALTER TABLE `notification`      AUTO_INCREMENT = 1;
ALTER TABLE `product_discount`  AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Users
--   Passwords are stored exactly the way the registration flow stores
--   them today (plain text) so these accounts can be used to log in.
--   See README_CPP_BACKEND.md section 9 about migrating to hashes.
-- ---------------------------------------------------------------------
INSERT INTO `user`
    (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`,
     `Phone`, `Email`, `NID`, `email_verified`, `last_login`) VALUES
-- Farmers
(2, 'Karim',  'Uddin',   'karim',  'karim123',  'Farmer',   '01711234567', 'karim@agrisphere.test',  '1990123456', 1, '2026-08-10 09:12:44'),
(3, 'Rina',   'Akter',   'rina',   'rina123',   'Farmer',   '01812345678', 'rina@agrisphere.test',   '1988654321', 1, '2026-08-09 18:40:05'),
(4, 'Jamal',  'Hossain', 'jamal',  'jamal123',  'Farmer',   '01913456789', 'jamal@agrisphere.test',  '1992777888', 1, '2026-08-08 07:55:19'),
-- Customers
(5, 'Sabbir', 'Rahman',  'sabbir', 'sabbir123', 'Customer', '01611122233', 'sabbir@agrisphere.test', '1995111222', 1, '2026-08-10 10:31:02'),
(6, 'Nusrat', 'Jahan',   'nusrat', 'nusrat123', 'Customer', '01722233344', 'nusrat@agrisphere.test', '1997333444', 1, '2026-08-09 21:14:38'),
(7, 'Tanvir', 'Ahmed',   'tanvir', 'tanvir123', 'Customer', '01833344455', 'tanvir@agrisphere.test', '1993555666', 1, '2026-08-07 16:02:57');

-- ---------------------------------------------------------------------
-- Farmer / customer profiles
-- (created here because this database has no after_user_insert trigger)
-- ---------------------------------------------------------------------
INSERT INTO `farmer` (`farmer_id`, `address`, `phone`, `bank_name`, `bank_account_number`) VALUES
(2, 'Village Char Kaliganj, Badarganj, Rangpur', '01711234567', 'Sonali Bank',   'AC-1029384756'),
(3, 'Ward 4, Mithapukur, Rangpur',               '01812345678', 'Janata Bank',   'AC-5647382910'),
(4, 'Shibganj, Bogura Sadar, Bogura',            '01913456789', 'Agrani Bank',   'AC-8172635490');

INSERT INTO `customer` (`customer_id`, `address`, `phone`) VALUES
(5, 'House 42, Road 7, Dhanmondi, Dhaka-1209',     '01611122233'),
(6, 'Flat 5B, Block C, Mirpur-10, Dhaka-1216',     '01722233344'),
(7, 'House 12, Ambarkhana, Sylhet-3100',           '01833344455');

-- ---------------------------------------------------------------------
-- Land holdings (farmer/land_info.php)
-- ---------------------------------------------------------------------
INSERT INTO `land`
    (`farmer_id`, `land_size`, `soil_type`, `district`, `upazila`, `latitude`, `longitude`) VALUES
(2, 3.50, 'Loamy',       'Rangpur', 'Badarganj',   25.743200, 89.244400),
(2, 1.25, 'Sandy loam',  'Rangpur', 'Badarganj',   25.751100, 89.259800),
(3, 2.75, 'Clay loam',   'Rangpur', 'Mithapukur',  25.565900, 89.152300),
(4, 5.00, 'Alluvial',    'Bogura',  'Bogura Sadar',24.851100, 89.371200);

-- ---------------------------------------------------------------------
-- Products
--   Images are files that already exist in uploads/products/.
--   Statuses cover all three cases so the admin review screen has work
--   to do: 15 approved, 2 pending, 1 rejected.
-- ---------------------------------------------------------------------
INSERT INTO `farm_product`
    (`product_id`, `farmer_id`, `category_id`, `product_name`, `description`, `product_image`,
     `unit`, `price_per_unit`, `quantity`, `status`, `rejection_reason`,
     `submitted_at`, `approved_at`, `approved_by`) VALUES
-- Vegetables (category 1)
(1,  2, 1, 'Fresh Potato',      'Grade A potatoes harvested this week, cleaned and sorted.',       'product_694dbd219fce8.png', 'kg',     42.50, 240, 'approved', NULL, '2026-03-02 08:15:00', '2026-03-02 11:40:00', 1),
(2,  3, 1, 'Carrot',            'Sweet red carrots, hand picked from Mithapukur fields.',          'product_694e637ca14a0.webp','kg',     72.00, 110, 'approved', NULL, '2026-03-11 09:05:00', '2026-03-11 13:22:00', 1),
(3,  2, 1, 'Cauliflower',       'Firm white heads, ideal for curry and roasting.',                 'product_694ea33ee8311.jpg', 'kg',     58.00,  75, 'approved', NULL, '2026-04-04 07:30:00', '2026-04-04 10:10:00', 1),
(4,  4, 1, 'Green Spinach',     'Fresh palong shak, tied in bundles the morning of delivery.',     'product_694e40f8d8ad6.jpg', 'bundle', 28.00, 140, 'approved', NULL, '2026-04-18 06:45:00', '2026-04-18 09:00:00', 1),
-- Fruits (category 2)
(5,  3, 2, 'Himsagar Mango',    'Season Himsagar from Rajshahi stock, naturally ripened.',          'product_694ed38435567.jpg', 'kg',    145.00,  85, 'approved', NULL, '2026-05-06 08:00:00', '2026-05-06 12:15:00', 1),
(6,  4, 2, 'Sagor Banana',      'Sagor kola sold by the dozen, ripened without chemicals.',         'product_694e41c190743.jpg', 'dozen',  95.00,  55, 'approved', NULL, '2026-05-21 07:20:00', '2026-05-21 11:05:00', 1),
-- Grains (category 3)
(7,  2, 3, 'Miniket Rice',      'Fine Miniket rice, milled and packed this season.',                'product_694e412d5bb4f.jpg', 'kg',     78.00, 480, 'approved', NULL, '2026-03-05 10:00:00', '2026-03-05 15:30:00', 1),
(8,  3, 3, 'Nazirshail Rice',   'Aromatic Nazirshail, aged six months for better texture.',         'product_694e417a7ab7c.jpg', 'kg',     86.00, 290, 'approved', NULL, '2026-06-02 09:40:00', '2026-06-02 14:00:00', 1),
-- Dairy (category 4)
(9,  4, 4, 'Fresh Cow Milk',    'Morning milk from grass fed cows, delivered chilled.',             'product_694e416be5aff.jpg', 'liter',  85.00,  90, 'approved', NULL, '2026-06-14 05:50:00', '2026-06-14 08:25:00', 1),
(10, 4, 4, 'Pure Ghee',         'Traditional hand churned ghee, no preservatives added.',           'product_694e50e226614.jpg', 'kg',    950.00,  22, 'approved', NULL, '2026-06-28 11:15:00', '2026-06-28 16:40:00', 1),
(11, 2, 4, 'Pasteurized Milk',  'Bottled pasteurized milk, awaiting quality review.',               'product_694e51a73d2aa.webp','liter',  92.00,  70, 'pending',  NULL, '2026-08-09 07:10:00', NULL, NULL),
-- Poultry (category 5)
(12, 3, 5, 'Farm Eggs',         'Free range brown eggs collected daily.',                           'product_694e4e23e1367.webp','piece',  13.50, 560, 'approved', NULL, '2026-07-01 06:30:00', '2026-07-01 09:45:00', 1),
(13, 2, 5, 'Live Duck',         'Healthy farm raised ducks, average weight 1.8 kg.',                'product_694dbf633f61a.jpg', 'piece', 480.00,  38, 'approved', NULL, '2026-07-09 08:20:00', '2026-07-09 12:35:00', 1),
-- Fish (category 6)
(14, 4, 6, 'Padma Hilsa',       'Genuine Padma ilish, iced immediately after catch.',               'product_694dbd751232a.webp','kg',   1250.00,  33, 'approved', NULL, '2026-07-16 05:15:00', '2026-07-16 10:05:00', 1),
(15, 3, 6, 'Rohu Fish',         'Pond raised rui, cleaned and cut on request.',                     'product_694eb6b8ed617.webp','kg',    340.00,  50, 'approved', NULL, '2026-07-24 06:00:00', '2026-07-24 09:30:00', 1),
-- Spices (category 7)
(16, 2, 7, 'Cumin Seed',        'Sun dried whole cumin, strong aroma, cleaned twice.',              'product_694ebf8dc5013.webp','kg',    620.00,  43, 'approved', NULL, '2026-08-01 10:45:00', '2026-08-01 14:20:00', 1),
-- Awaiting review / rejected
(17, 3, 1, 'Cherry Tomato',     'Small sweet tomatoes, first harvest of the season.',               NULL,                        'kg',    110.00,  40, 'pending',  NULL, '2026-08-10 07:05:00', NULL, NULL),
(18, 4, 2, 'Imported Apple',    'Red apples sourced from an importer in Chattogram.',               NULL,                        'kg',    260.00,  30, 'rejected',
 'AgriSphere lists locally grown produce only. Imported items cannot be approved.', '2026-08-05 13:30:00', NULL, NULL);

-- ---------------------------------------------------------------------
-- Inventory
-- (the after_inventory_insert trigger mirrors these into farm_product)
-- ---------------------------------------------------------------------
INSERT INTO `farm_inventory` (`product_id`, `quantity_available`) VALUES
(1, 240), (2, 110), (3, 75), (4, 140), (5, 85), (6, 55), (7, 480), (8, 290),
(9, 90), (10, 22), (11, 70), (12, 560), (13, 38), (14, 33), (15, 50), (16, 43),
(17, 40), (18, 30);

-- ---------------------------------------------------------------------
-- Open shopping carts
-- ---------------------------------------------------------------------
INSERT INTO `customer_cart` (`customer_id`, `product_id`, `quantity`) VALUES
(5,  2,  3),   -- Sabbir: 3 kg carrot
(5, 12, 12),   -- Sabbir: 12 eggs
(6,  9,  2);   -- Nusrat: 2 litres of milk

-- ---------------------------------------------------------------------
-- Orders spread over six months
-- (Delivered orders drive the revenue chart on the admin dashboard)
-- ---------------------------------------------------------------------
INSERT INTO `customer_order`
    (`order_id`, `customer_id`, `order_date`, `total_amount`, `status`,
     `processed_at`, `shipped_at`, `delivered_at`, `cancelled_at`, `updated_by`) VALUES
(1, 5, '2026-03-14 10:22:00',  815.00, 'Delivered', '2026-03-14 12:00:00', '2026-03-15 09:10:00', '2026-03-16 14:35:00', NULL, 1),
(2, 6, '2026-04-02 16:48:00',  564.00, 'Delivered', '2026-04-02 18:05:00', '2026-04-03 08:40:00', '2026-04-04 11:20:00', NULL, 1),
(3, 7, '2026-04-27 09:05:00', 2500.00, 'Cancelled', '2026-04-27 10:30:00', NULL, NULL, '2026-04-27 15:45:00', 1),
(4, 5, '2026-05-19 11:37:00', 1445.00, 'Delivered', '2026-05-19 13:15:00', '2026-05-20 07:55:00', '2026-05-21 12:05:00', NULL, 1),
(5, 6, '2026-06-08 14:10:00',  602.00, 'Delivered', '2026-06-08 15:40:00', '2026-06-09 09:25:00', '2026-06-10 10:50:00', NULL, 1),
(6, 7, '2026-07-11 08:52:00', 1970.00, 'Delivered', '2026-07-11 10:20:00', '2026-07-12 08:15:00', '2026-07-13 13:40:00', NULL, 1),
(7, 5, '2026-08-03 12:14:00', 1035.00, 'Shipped',   '2026-08-03 14:00:00', '2026-08-04 09:05:00', NULL, NULL, 1),
(8, 6, '2026-08-08 19:26:00',  421.00, 'Processing','2026-08-09 09:30:00', NULL, NULL, NULL, 1),
(9, 7, '2026-08-10 08:41:00', 1240.00, 'Pending',   NULL, NULL, NULL, NULL, NULL);

INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_per_unit`) VALUES
(1,  1, 10,   42.50),   -- 425.00
(1,  7,  5,   78.00),   -- 390.00
(2,  9,  6,   85.00),   -- 510.00
(2, 12,  4,   13.50),   --  54.00
(3, 14,  2, 1250.00),   -- 2500.00 (cancelled)
(4,  5,  8,  145.00),   -- 1160.00
(4,  6,  3,   95.00),   -- 285.00
(5,  2,  4,   72.00),   -- 288.00
(5,  3,  3,   58.00),   -- 174.00
(5,  4,  5,   28.00),   -- 140.00
(6, 15,  3,  340.00),   -- 1020.00
(6, 10,  1,  950.00),   -- 950.00
(7,  7, 10,   78.00),   -- 780.00
(7,  1,  6,   42.50),   -- 255.00
(8,  9,  4,   85.00),   -- 340.00
(8, 12,  6,   13.50),   --  81.00
(9, 16,  2,  620.00);   -- 1240.00

INSERT INTO `payment`
    (`order_id`, `payment_method`, `bkash_number`, `payment_status`, `payment_time`) VALUES
(1, 'cod',   NULL,          'Paid',    '2026-03-16 14:35:00'),
(2, 'bkash', '01722233344', 'Paid',    '2026-04-02 16:49:00'),
(3, 'bkash', '01833344455', 'Failed',  '2026-04-27 09:06:00'),
(4, 'cod',   NULL,          'Paid',    '2026-05-21 12:05:00'),
(5, 'bkash', '01722233344', 'Paid',    '2026-06-08 14:11:00'),
(6, 'cod',   NULL,          'Paid',    '2026-07-13 13:40:00'),
(7, 'bkash', '01611122233', 'Paid',    '2026-08-03 12:15:00'),
(8, 'cod',   NULL,          'Pending', '2026-08-08 19:26:00'),
(9, 'cod',   NULL,          'Pending', '2026-08-10 08:41:00');

-- ---------------------------------------------------------------------
-- A blocked account, so the admin user list shows both states
-- ---------------------------------------------------------------------
INSERT INTO `user_block` (`user_id`, `is_blocked`, `blocked_at`, `reason`) VALUES
(7, 0, '2026-06-02 11:00:00', 'Temporary hold during a payment dispute - since cleared.');

-- ---------------------------------------------------------------------
-- AI assistant history
-- ---------------------------------------------------------------------
INSERT INTO `ai_log` (`user_id`, `user_type`, `query`, `response`, `timestamp`) VALUES
(2, 'farmer',   'Best time to plant tomatoes?',
 'In Bangladesh the Rabi season is best: transplant seedlings between mid-October and mid-November for the highest yield.',
 '2026-07-28 10:14:00'),
(3, 'farmer',   'What is the current market price of rice?',
 'Approximate retail prices: coarse rice BDT 50-55/kg, medium BDT 55-65/kg, fine (Miniket, Nazirshail) BDT 65-80/kg.',
 '2026-08-01 15:42:00'),
(5, 'customer', 'Which fruits are good for diabetics?',
 'Guava, jamun, papaya and green apple are lower on the glycemic index and high in fibre - good choices in moderation.',
 '2026-08-04 20:03:00'),
(6, 'customer', 'How should I store leafy greens?',
 'Wash, pat completely dry, wrap loosely in a dry paper towel and keep in an airtight box in the crisper - stays fresh about a week.',
 '2026-08-09 21:20:00'),
(1, 'admin',    'Best practices for order management',
 'Automate status notifications, track order processing time, and reconcile payment status against delivery events daily.',
 '2026-08-10 09:05:00');

SELECT 'Sample data loaded.' AS Status,
       (SELECT COUNT(*) FROM `user`)           AS users,
       (SELECT COUNT(*) FROM `farm_product`)   AS products,
       (SELECT COUNT(*) FROM `customer_order`) AS orders;
