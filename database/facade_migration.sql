--  AgriSphere - Facade Pattern feature (product packages)
--  Adds TWO new tables and seeds demo data. It does not modify, drop or
--  rename any existing table, column or row.
--
--    product_package  - a named bundle, e.g. "Weekly Essentials"
--    package_item     - which EXISTING product, and how much of it
--
--  package_item.product_id is a FOREIGN KEY into farm_product, so a
--  package is only a list of existing products. There is no second
--  product system and no second cart.
--
--  It also seeds three farmers and their products so the packages can be
--  demonstrated with guaranteed stock. Seeding uses fixed ids and
--  INSERT IGNORE, so running this file twice creates nothing new.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\facade_migration.sql
--
--  Safe to run more than once.

USE `cse311 lab project`;

-- product_package
--   The package itself. `package_name` is UNIQUE, which is what makes
--   re-running this seed a no-op.
CREATE TABLE IF NOT EXISTS `product_package` (
    `package_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `package_name` VARCHAR(120) NOT NULL,
    `description`  VARCHAR(300) NULL DEFAULT NULL,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_package_name` (`package_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- package_item
--   One row per product in a package. The UNIQUE key stops the same
--   product being listed twice in one package.
CREATE TABLE IF NOT EXISTS `package_item` (
    `package_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `package_id`      INT NOT NULL,
    `product_id`      INT NOT NULL,
    `quantity`        INT NOT NULL,
    UNIQUE KEY `uq_package_product` (`package_id`, `product_id`),
    KEY `idx_package_item_product` (`product_id`),
    CONSTRAINT `fk_pkg_item_package` FOREIGN KEY (`package_id`)
        REFERENCES `product_package`(`package_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pkg_item_product` FOREIGN KEY (`product_id`)
        REFERENCES `farm_product`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--  SEED: three farmers
--  Only `user` is written. The existing `after_user_insert` trigger
--  creates the matching `farmer` row (address / bank details) by itself,
--  so inserting into `farmer` here would fail on a duplicate key.
--
--  Passwords are bcrypt hashes of  Farmer123!  - never plaintext.
--  Login works because AuthService detects the $2y$ hash and
--  auth/login_action.php runs password_verify() for it.
--
--  Fixed ids (7700001-7700003) keep this seed idempotent.
INSERT IGNORE INTO `user`
    (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`, `Phone`, `Email`, `NID`, `email_verified`)
VALUES
    (7700001, 'Abdul', 'Karim',  'akarim',   '$2y$10$mJIXXBZzgfxk0if1fGomjOtyaZ0BVkOGziyGW2.PPyqnjCrK8/K5m', 'Farmer', '01711000001', 'farmer1@agrisphere.test', '1990770000101', 1),
    (7700002, 'Rahim', 'Uddin',  'ruddin',   '$2y$10$mJIXXBZzgfxk0if1fGomjOtyaZ0BVkOGziyGW2.PPyqnjCrK8/K5m', 'Farmer', '01711000002', 'farmer2@agrisphere.test', '1990770000202', 1),
    (7700003, 'Hasan', 'Ali',    'hasanali', '$2y$10$mJIXXBZzgfxk0if1fGomjOtyaZ0BVkOGziyGW2.PPyqnjCrK8/K5m', 'Farmer', '01711000003', 'farmer3@agrisphere.test', '1990770000303', 1);

--  SEED: their products
--  Fixed ids 7001-7011 sit well above the current AUTO_INCREMENT, so they
--  never collide with products added through the farmer pages.
--  Categories are the EXISTING ones: 1 Vegetables, 3 Grains, 5 Dairy,
--  6 Fish, 8 Poultry. Masoor Dal is filed under Grains because the
--  database has no Pulses category and this seed does not invent one.
--  Everything is seeded 'approved' with generous stock so the packages
--  work immediately.
--  product_image holds a file name that lives in uploads/products/.
--  getProductImageUrl() (includes/functions.php) checks the file exists and
--  falls back to the shared placeholder if it does not, so a missing image
--  degrades gracefully instead of breaking the page.
INSERT IGNORE INTO `farm_product`
    (`product_id`, `farmer_id`, `category_id`, `product_name`, `description`, `product_image`, `unit`, `price_per_unit`, `quantity`, `status`, `approved_at`)
VALUES
    -- Farmer 1: Abdul Karim
    (7001, 7700001, 3, 'Miniket Rice',            'Premium miniket rice, freshly milled.',       'product_pkg_miniket_rice.jpg',   'kg',    70.00,  500, 'approved', NOW()),
    (7002, 7700001, 1, 'Fresh Potato',            'Field-fresh potatoes, sorted and cleaned.',   'product_pkg_fresh_potato.jpg',   'kg',    45.00,  400, 'approved', NOW()),
    (7003, 7700001, 3, 'Masoor Dal',              'Red lentils, cleaned and ready to cook.',     'product_pkg_masoor_dal.jpg',     'kg',   140.00,  300, 'approved', NOW()),
    (7004, 7700001, 1, 'Bottle Gourd',            'Seasonal bottle gourd (lau), picked daily.',  'product_pkg_bottle_gourd.jpg',   'piece', 60.00,  200, 'approved', NOW()),
    -- Farmer 2: Rahim Uddin
    (7005, 7700002, 8, 'Chicken (Whole Broiler)', 'Whole broiler chicken, farm dressed.',        'product_pkg_chicken_whole.jpg',  'kg',   190.00,  250, 'approved', NOW()),
    (7006, 7700002, 8, 'Chicken Breast',          'Boneless skinless chicken breast.',           'product_pkg_chicken_breast.jpg', 'kg',   320.00,  200, 'approved', NOW()),
    (7007, 7700002, 8, 'Eggs (Brown)',            'Free-range brown eggs.',                      'product_pkg_eggs_brown.jpg',     'piece',  9.00, 1200, 'approved', NOW()),
    (7008, 7700002, 5, 'Milk (Cow)',              'Fresh cow milk, delivered daily.',            'product_pkg_milk_cow.jpg',       'liter',  85.00,  300, 'approved', NOW()),
    -- Farmer 3: Hasan Ali
    (7009, 7700003, 6, 'Tilapia Fish',            'Pond-raised tilapia, cleaned on request.',    'product_pkg_tilapia.jpg',        'kg',   200.00,  200, 'approved', NOW()),
    (7010, 7700003, 6, 'Rui Fish',                'Fresh rui (rohu) from managed ponds.',        'product_pkg_rui_fish.jpg',       'kg',   230.00,  180, 'approved', NOW()),
    (7011, 7700003, 5, 'Yogurt (Doi)',            'Traditional sweet yogurt, set in clay pots.', 'product_pkg_yogurt.jpg',         'kg',   160.00,  150, 'approved', NOW());

-- The INSERT above is skipped on a re-run (the rows already exist), so the
-- image names are (re)applied here. This is also what fills them in for a
-- database that was seeded before images were added.
UPDATE `farm_product` SET `product_image` = CASE `product_id`
        WHEN 7001 THEN 'product_pkg_miniket_rice.jpg'
        WHEN 7002 THEN 'product_pkg_fresh_potato.jpg'
        WHEN 7003 THEN 'product_pkg_masoor_dal.jpg'
        WHEN 7004 THEN 'product_pkg_bottle_gourd.jpg'
        WHEN 7005 THEN 'product_pkg_chicken_whole.jpg'
        WHEN 7006 THEN 'product_pkg_chicken_breast.jpg'
        WHEN 7007 THEN 'product_pkg_eggs_brown.jpg'
        WHEN 7008 THEN 'product_pkg_milk_cow.jpg'
        WHEN 7009 THEN 'product_pkg_tilapia.jpg'
        WHEN 7010 THEN 'product_pkg_rui_fish.jpg'
        WHEN 7011 THEN 'product_pkg_yogurt.jpg'
    END
WHERE `product_id` BETWEEN 7001 AND 7011;

-- farm_inventory drives farm_product.quantity through the existing
-- after_inventory_insert / after_inventory_update triggers.
--
-- NOTE: in the live database farm_inventory's primary key is `inventory_id`
-- and `product_id` is only an index (it is NOT unique, unlike the definition
-- in setup.sql). INSERT IGNORE would therefore happily insert a second row for
-- the same product on a re-run, so the guard below is an explicit
-- WHERE NOT EXISTS instead.
INSERT INTO `farm_inventory` (`product_id`, `quantity_available`)
SELECT * FROM (
    SELECT 7001 AS p, 500 AS q UNION ALL SELECT 7002, 400 UNION ALL
    SELECT 7003, 300 UNION ALL SELECT 7004, 200 UNION ALL
    SELECT 7005, 250 UNION ALL SELECT 7006, 200 UNION ALL
    SELECT 7007, 1200 UNION ALL SELECT 7008, 300 UNION ALL
    SELECT 7009, 200 UNION ALL SELECT 7010, 180 UNION ALL
    SELECT 7011, 150
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `farm_inventory` fi WHERE fi.product_id = seed.p
);

--  SEED: three packages
INSERT IGNORE INTO `product_package` (`package_id`, `package_name`, `description`) VALUES
    (1, 'Weekly Essentials',  'A full week of staples for a family - rice, chicken, fish, eggs, potato and dal.'),
    (2, 'Farm Fresh Basics',  'Everyday basics straight from the farm - rice, eggs, milk, potato and a seasonal vegetable.'),
    (3, 'High Protein',       'A protein-focused selection - chicken breast, eggs, fish and yogurt.');

INSERT IGNORE INTO `package_item` (`package_id`, `product_id`, `quantity`) VALUES
    -- 1. Weekly Essentials
    (1, 7001, 5),   -- Miniket Rice           5 kg
    (1, 7005, 1),   -- Chicken (Whole Broiler) 1 kg
    (1, 7009, 1),   -- Tilapia Fish            1 kg
    (1, 7007, 12),  -- Eggs (Brown)           12 pieces
    (1, 7002, 2),   -- Fresh Potato            2 kg
    (1, 7003, 1),   -- Masoor Dal              1 kg
    -- 2. Farm Fresh Basics
    (2, 7001, 5),   -- Miniket Rice            5 kg
    (2, 7007, 12),  -- Eggs (Brown)           12 pieces
    (2, 7008, 2),   -- Milk (Cow)              2 liters
    (2, 7002, 2),   -- Fresh Potato            2 kg
    (2, 7004, 1),   -- Bottle Gourd            1 piece
    -- 3. High Protein
    (3, 7006, 2),   -- Chicken Breast          2 kg
    (3, 7007, 30),  -- Eggs (Brown)           30 pieces
    (3, 7010, 1),   -- Rui Fish                1 kg
    (3, 7011, 1);   -- Yogurt (Doi)            1 kg

SELECT 'Facade package tables and seed data ready.' AS Status;
