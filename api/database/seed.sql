-- =====================================================
-- Seed Data for Inventory & POS System
-- Snack Products Focus
-- =====================================================

-- -----------------------------------------------------
-- Admin User (password: admin123)
-- Password hash generated with PHP password_hash('admin123', PASSWORD_DEFAULT)
-- -----------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `phone`, `is_active`) VALUES
('usr-admin-001', 'admin@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', 'admin', '09171234567', 1),
('usr-manager-001', 'manager@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Dela Cruz', 'manager', '09181234567', 1),
('usr-cashier-001', 'cashier@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', 'cashier', '09191234567', 1),
('usr-stockman-001', 'stockman@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro', 'Garcia', 'stockman', '09201234567', 1);

-- -----------------------------------------------------
-- Categories (Snack-focused)
-- -----------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `display_order`, `is_active`) VALUES
('cat-001', 'Chips & Crisps', 'chips-crisps', 'Potato chips, corn chips, and savory crisps', 1, 1),
('cat-002', 'Biscuits & Crackers', 'biscuits-crackers', 'Sweet and savory biscuits, crackers', 2, 1),
('cat-003', 'Cookies', 'cookies', 'Chocolate chip, sandwich cookies, and more', 3, 1),
('cat-004', 'Chocolate & Candy', 'chocolate-candy', 'Chocolates, candies, and sweet treats', 4, 1),
('cat-005', 'Beverages', 'beverages', 'Soft drinks, juices, energy drinks, and teas', 5, 1),
('cat-006', 'Nuts & Dried Fruits', 'nuts-dried-fruits', 'Peanuts, cashews, dried mangoes', 6, 1);

-- -----------------------------------------------------
-- Suppliers
-- -----------------------------------------------------
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `city`, `is_active`) VALUES
('sup-001', 'Universal Robina Corporation', 'Ricardo Tan', 'orders@urc.com.ph', '028888888', '110 E. Rodriguez Jr. Ave, Libis', 'Quezon City', 1),
('sup-002', 'Monde Nissin Corporation', 'Emily Chua', 'sales@mondenissin.com', '027777777', 'Monde Nissin Building, EDSA', 'Makati City', 1),
('sup-003', 'Liwayway Marketing Corporation', 'James Lim', 'supply@oishi.com.ph', '026666666', 'Liwayway Building, Pasig', 'Pasig City', 1),
('sup-004', 'Coca-Cola Beverages Philippines', 'Anna Reyes', 'orders@cocacola.com.ph', '025555555', 'Coca-Cola Philippines Building', 'Taguig City', 1),
('sup-005', 'Pepsi-Cola Products Philippines', 'Mark Santos', 'supply@pepsi.com.ph', '024444444', 'Pepsi Building, Muntinlupa', 'Muntinlupa City', 1);

-- -----------------------------------------------------
-- Products (Snacks)
-- -----------------------------------------------------
INSERT INTO `products` (`id`, `sku`, `barcode`, `name`, `description`, `category_id`, `supplier_id`, `cost_price`, `wholesale_price`, `retail_price`, `is_active`) VALUES
-- Chips & Crisps
('prod-001', 'PIATTOS-CHZ-40', '4800016123456', 'Piattos Cheese 40g', 'Crispy potato chips with cheese flavor', 'cat-001', 'sup-001', 8.00, 10.00, 15.00, 1),
('prod-002', 'PIATTOS-SNC-40', '4800016123457', 'Piattos Sour Cream 40g', 'Crispy potato chips with sour cream & onion', 'cat-001', 'sup-001', 8.00, 10.00, 15.00, 1),
('prod-003', 'NOVA-MG-40', '4800016234567', 'Nova Multigrain Snacks 40g', 'Baked multigrain snacks, healthier choice', 'cat-001', 'sup-001', 9.00, 11.00, 16.00, 1),
('prod-004', 'OISHI-PC-60', '4800194123456', 'Oishi Prawn Crackers 60g', 'Classic prawn flavored crackers', 'cat-001', 'sup-003', 10.00, 13.00, 18.00, 1),
('prod-005', 'OISHI-PILLW-45', '4800194123457', 'Oishi Pillows Choco 45g', 'Pillow-shaped snacks with chocolate filling', 'cat-001', 'sup-003', 9.00, 12.00, 17.00, 1),
('prod-006', 'VCUT-CHZ-60', '4800016345678', 'V-Cut Cheese 60g', 'V-shaped potato chips, extra crunchy', 'cat-001', 'sup-001', 10.00, 13.00, 18.00, 1),

-- Biscuits & Crackers
('prod-007', 'SKYFLKS-REG-25', '4800361234567', 'SkyFlakes Crackers 25g', 'Classic saltine crackers, perfect with spreads', 'cat-002', 'sup-002', 5.00, 7.00, 10.00, 1),
('prod-008', 'SKYFLKS-COND-25', '4800361234568', 'SkyFlakes Condensed Milk 25g', 'Sweet condensed milk flavored crackers', 'cat-002', 'sup-002', 6.00, 8.00, 12.00, 1),
('prod-009', 'FITA-REG-30', '4800361234569', 'Fita Crackers 30g', 'Buttery crackers, slightly sweet', 'cat-002', 'sup-002', 6.00, 8.00, 12.00, 1),
('prod-010', 'HANSEL-PB-32', '4800092123456', 'Hansel Peanut Butter 32g', 'Sandwich crackers with peanut butter filling', 'cat-002', 'sup-001', 6.00, 8.00, 12.00, 1),
('prod-011', 'REBISCO-BTR-32', '4800092234567', 'Rebisco Butter Crackers 32g', 'Crispy crackers with buttery taste', 'cat-002', 'sup-002', 5.00, 7.00, 10.00, 1),

-- Cookies
('prod-012', 'OREO-REG-66', '7622210123456', 'Oreo Original 66g', 'Chocolate sandwich cookies with vanilla cream', 'cat-003', 'sup-002', 18.00, 22.00, 30.00, 1),
('prod-013', 'OREO-DBL-66', '7622210123457', 'Oreo Double Stuf 66g', 'Oreo with double cream filling', 'cat-003', 'sup-002', 20.00, 25.00, 35.00, 1),
('prod-014', 'CHIPS-AHOY-66', '7622210234567', 'Chips Ahoy Original 66g', 'Chocolate chip cookies', 'cat-003', 'sup-002', 18.00, 22.00, 32.00, 1),
('prod-015', 'FIBISCO-CHC-30', '4800016456789', 'Fibisco Choco Chip 30g', 'Local chocolate chip cookies', 'cat-003', 'sup-001', 8.00, 10.00, 15.00, 1),

-- Chocolate & Candy
('prod-016', 'CHOCOMUCHO-DRK-32', '4800016567890', 'Choco Mucho Dark 32g', 'Wafer bar with dark chocolate', 'cat-004', 'sup-001', 10.00, 13.00, 18.00, 1),
('prod-017', 'CHOCOMUCHO-MLK-32', '4800016567891', 'Choco Mucho Milk 32g', 'Wafer bar with milk chocolate', 'cat-004', 'sup-001', 10.00, 13.00, 18.00, 1),
('prod-018', 'MNM-PNT-45', '5000159123456', 'M&Ms Peanut 45g', 'Chocolate candies with peanuts', 'cat-004', 'sup-002', 35.00, 42.00, 55.00, 1),
('prod-019', 'KITKAT-4FNG', '4800361345678', 'KitKat 4 Finger', 'Crispy wafer in milk chocolate', 'cat-004', 'sup-002', 28.00, 35.00, 45.00, 1),
('prod-020', 'MENTOS-MINT-37', '8935001123456', 'Mentos Mint 37g', 'Chewy mint candies', 'cat-004', 'sup-002', 12.00, 16.00, 22.00, 1),
('prod-021', 'NRDS-GUMR-46', '4800016678901', 'Nerds Gummy Clusters 46g', 'Gummy candies coated with Nerds', 'cat-004', 'sup-001', 25.00, 32.00, 42.00, 1),

-- Beverages
('prod-022', 'C2-GREEN-355', '4800016789012', 'C2 Green Tea 355ml', 'Ready-to-drink green tea', 'cat-005', 'sup-001', 12.00, 16.00, 22.00, 1),
('prod-023', 'C2-APPLE-355', '4800016789013', 'C2 Apple Green Tea 355ml', 'Green tea with apple flavor', 'cat-005', 'sup-001', 12.00, 16.00, 22.00, 1),
('prod-024', 'COBRA-ORG-350', '4800194234567', 'Cobra Energy Drink Original 350ml', 'Energy drink for active lifestyle', 'cat-005', 'sup-003', 18.00, 23.00, 30.00, 1),
('prod-025', 'COKE-REG-330', '5449000123456', 'Coca-Cola Regular 330ml', 'Classic cola soft drink', 'cat-005', 'sup-004', 18.00, 22.00, 28.00, 1),
('prod-026', 'SPRITE-REG-330', '5449000123457', 'Sprite 330ml', 'Lemon-lime soft drink', 'cat-005', 'sup-004', 18.00, 22.00, 28.00, 1),
('prod-027', 'ROYAL-ORG-330', '5449000234567', 'Royal Orange 330ml', 'Orange flavored soft drink', 'cat-005', 'sup-004', 18.00, 22.00, 28.00, 1),
('prod-028', 'MTN-DEW-330', '5449000345678', 'Mountain Dew 330ml', 'Citrus flavored soft drink', 'cat-005', 'sup-005', 18.00, 22.00, 28.00, 1),

-- Nuts & Dried Fruits
('prod-029', 'PLANTERS-MXD-150', '0029000123456', 'Planters Mixed Nuts 150g', 'Premium mixed nuts assortment', 'cat-006', 'sup-001', 85.00, 100.00, 130.00, 1),
('prod-030', 'NAGARAYA-BBQ-80', '4800016890123', 'Nagaraya Cracker Nuts BBQ 80g', 'Crunchy coated peanuts, BBQ flavor', 'cat-006', 'sup-001', 20.00, 25.00, 35.00, 1),
('prod-031', 'NAGARAYA-BF-80', '4800016890124', 'Nagaraya Cracker Nuts Butter 80g', 'Crunchy coated peanuts, butter flavor', 'cat-006', 'sup-001', 20.00, 25.00, 35.00, 1),
('prod-032', 'DRIED-MNG-100', '4800016901234', '7D Dried Mangoes 100g', 'Sweet dried Philippine mangoes', 'cat-006', 'sup-003', 55.00, 68.00, 85.00, 1);

-- -----------------------------------------------------
-- Product Variants (for products with size/flavor options)
-- -----------------------------------------------------
INSERT INTO `product_variants` (`id`, `product_id`, `name`, `sku_suffix`, `price_adjustment`, `is_active`) VALUES
-- Piattos variants
('var-001', 'prod-001', 'Party Size 160g', '-160', 35.00, 1),
('var-002', 'prod-002', 'Party Size 160g', '-160', 35.00, 1),
-- Nova variants
('var-003', 'prod-003', 'Party Size 160g', '-160', 38.00, 1),
-- Oreo variants
('var-004', 'prod-012', 'Family Pack 133g', '-133', 28.00, 1),
('var-005', 'prod-012', 'Party Pack 266g', '-266', 62.00, 1),
-- Coca-Cola variants
('var-006', 'prod-025', '500ml Bottle', '-500', 8.00, 1),
('var-007', 'prod-025', '1.5L Bottle', '-1500', 32.00, 1),
-- Sprite variants
('var-008', 'prod-026', '500ml Bottle', '-500', 8.00, 1),
('var-009', 'prod-026', '1.5L Bottle', '-1500', 32.00, 1);

-- -----------------------------------------------------
-- Inventory Levels
-- -----------------------------------------------------
INSERT INTO `inventory_levels` (`id`, `product_id`, `wholesale_qty`, `retail_qty`, `shelf_qty`, `wholesale_unit`, `retail_unit`, `shelf_unit`, `pcs_per_pack`, `packs_per_box`, `reorder_point`, `max_stock_level`) VALUES
-- Chips
('inv-001', 'prod-001', 10, 25, 15, 'box', 'pack', 'piece', 12, 24, 5, 50),
('inv-002', 'prod-002', 8, 20, 12, 'box', 'pack', 'piece', 12, 24, 5, 50),
('inv-003', 'prod-003', 12, 30, 18, 'box', 'pack', 'piece', 12, 24, 5, 50),
('inv-004', 'prod-004', 6, 18, 10, 'box', 'pack', 'piece', 12, 20, 5, 40),
('inv-005', 'prod-005', 8, 22, 14, 'box', 'pack', 'piece', 12, 20, 5, 40),
('inv-006', 'prod-006', 10, 28, 16, 'box', 'pack', 'piece', 12, 24, 5, 50),
-- Biscuits
('inv-007', 'prod-007', 15, 40, 30, 'box', 'pack', 'piece', 10, 30, 8, 60),
('inv-008', 'prod-008', 12, 35, 25, 'box', 'pack', 'piece', 10, 30, 8, 60),
('inv-009', 'prod-009', 10, 30, 20, 'box', 'pack', 'piece', 10, 30, 8, 60),
('inv-010', 'prod-010', 8, 24, 18, 'box', 'pack', 'piece', 10, 24, 6, 48),
('inv-011', 'prod-011', 10, 28, 20, 'box', 'pack', 'piece', 10, 24, 6, 48),
-- Cookies
('inv-012', 'prod-012', 12, 30, 20, 'box', 'pack', 'piece', 6, 24, 5, 50),
('inv-013', 'prod-013', 8, 20, 15, 'box', 'pack', 'piece', 6, 24, 4, 40),
('inv-014', 'prod-014', 10, 25, 18, 'box', 'pack', 'piece', 6, 24, 5, 50),
('inv-015', 'prod-015', 6, 18, 12, 'box', 'pack', 'piece', 10, 20, 4, 40),
-- Chocolate & Candy
('inv-016', 'prod-016', 15, 40, 25, 'box', 'pack', 'piece', 12, 24, 6, 60),
('inv-017', 'prod-017', 15, 40, 25, 'box', 'pack', 'piece', 12, 24, 6, 60),
('inv-018', 'prod-018', 6, 15, 10, 'box', 'pack', 'piece', 6, 12, 3, 30),
('inv-019', 'prod-019', 8, 20, 15, 'box', 'pack', 'piece', 8, 16, 4, 40),
('inv-020', 'prod-020', 10, 25, 18, 'box', 'roll', 'piece', 14, 24, 5, 50),
('inv-021', 'prod-021', 4, 12, 8, 'box', 'pack', 'piece', 6, 12, 3, 24),
-- Beverages
('inv-022', 'prod-022', 20, 50, 30, 'case', 'bundle', 'bottle', 6, 24, 10, 80),
('inv-023', 'prod-023', 18, 45, 28, 'case', 'bundle', 'bottle', 6, 24, 10, 80),
('inv-024', 'prod-024', 15, 40, 25, 'case', 'bundle', 'bottle', 6, 24, 8, 60),
('inv-025', 'prod-025', 25, 60, 40, 'case', 'bundle', 'bottle', 6, 24, 12, 100),
('inv-026', 'prod-026', 22, 55, 35, 'case', 'bundle', 'bottle', 6, 24, 12, 100),
('inv-027', 'prod-027', 18, 45, 30, 'case', 'bundle', 'bottle', 6, 24, 10, 80),
('inv-028', 'prod-028', 16, 42, 28, 'case', 'bundle', 'bottle', 6, 24, 10, 80),
-- Nuts
('inv-029', 'prod-029', 5, 15, 10, 'box', 'pack', 'piece', 6, 12, 3, 24),
('inv-030', 'prod-030', 10, 28, 18, 'box', 'pack', 'piece', 12, 24, 5, 50),
('inv-031', 'prod-031', 10, 28, 18, 'box', 'pack', 'piece', 12, 24, 5, 50),
('inv-032', 'prod-032', 6, 18, 12, 'box', 'pack', 'piece', 6, 12, 4, 30);

-- -----------------------------------------------------
-- Sample Batches (FEFO tracking)
-- -----------------------------------------------------
INSERT INTO `batches` (`id`, `product_id`, `batch_number`, `supplier_id`, `quantity_received`, `quantity_remaining`, `cost_per_unit`, `manufacture_date`, `expiry_date`, `received_at`) VALUES
-- Recent batches for popular items
('bat-001', 'prod-001', 'URC-2024-001', 'sup-001', 288, 250, 8.00, '2024-01-15', '2024-07-15', '2024-01-20'),
('bat-002', 'prod-001', 'URC-2024-015', 'sup-001', 288, 288, 8.00, '2024-02-01', '2024-08-01', '2024-02-05'),
('bat-003', 'prod-012', 'MN-2024-042', 'sup-002', 144, 120, 18.00, '2024-01-10', '2024-10-10', '2024-01-15'),
('bat-004', 'prod-022', 'URC-2024-088', 'sup-001', 576, 500, 12.00, '2024-02-01', '2025-02-01', '2024-02-03'),
('bat-005', 'prod-025', 'CCBP-2024-102', 'sup-004', 576, 450, 18.00, '2024-01-20', '2024-07-20', '2024-01-25');

-- -----------------------------------------------------
-- Default Settings
-- -----------------------------------------------------
INSERT INTO `settings` (`id`, `key`, `value`, `description`) VALUES
('set-001', 'store', '{"name": "Sari-Sari Store POS", "address": "123 Main Street, Barangay Centro", "city": "Manila", "phone": "09171234567", "email": "store@example.com", "tin": "123-456-789-000", "permitNumber": "BP-2024-001"}', 'Store information'),
('set-002', 'pos', '{"defaultPaymentMethod": "cash", "enableGCashPayment": true, "enableMayaPayment": true, "enableCashPayment": true, "enableCardPayment": false, "requireCustomerForSale": false, "autoPrintReceipt": false, "showProductImages": true, "quickAddMode": false, "lowStockAlert": 10}', 'POS settings'),
('set-003', 'inventory', '{"lowStockThreshold": 10, "criticalStockThreshold": 5, "expiryWarningDays": 30, "enableAutoReorder": false, "defaultReorderQuantity": 24}', 'Inventory settings'),
('set-004', 'notifications', '{"emailNotifications": false, "lowStockAlerts": true, "expiryAlerts": true, "dailyReports": false}', 'Notification settings');
