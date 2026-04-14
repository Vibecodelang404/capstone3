-- =====================================================
-- Inventory & POS System Database Schema
-- MySQL 8.0+
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` CHAR(36) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'manager', 'stockman', 'cashier', 'customer') NOT NULL DEFAULT 'customer',
  `phone` VARCHAR(20) NULL,
  `avatar_url` VARCHAR(500) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: refresh_tokens
-- -----------------------------------------------------
DROP TABLE IF EXISTS `refresh_tokens`;
CREATE TABLE `refresh_tokens` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_refresh_tokens_user` (`user_id`),
  INDEX `idx_refresh_tokens_hash` (`token_hash`),
  CONSTRAINT `fk_refresh_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: categories
-- -----------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_categories_slug` (`slug`),
  INDEX `idx_categories_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: suppliers
-- -----------------------------------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `contact_person` VARCHAR(200) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_suppliers_name` (`name`),
  INDEX `idx_suppliers_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: products
-- -----------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` CHAR(36) NOT NULL,
  `sku` VARCHAR(50) NOT NULL,
  `barcode` VARCHAR(50) NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category_id` CHAR(36) NOT NULL,
  `supplier_id` CHAR(36) NULL,
  `cost_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `wholesale_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `retail_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `image_url` VARCHAR(500) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_products_sku` (`sku`),
  INDEX `idx_products_barcode` (`barcode`),
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_supplier` (`supplier_id`),
  INDEX `idx_products_is_active` (`is_active`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_products_supplier`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: product_variants
-- -----------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` CHAR(36) NOT NULL,
  `product_id` CHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `sku_suffix` VARCHAR(20) NULL,
  `price_adjustment` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_variants_product` (`product_id`),
  CONSTRAINT `fk_product_variants_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: inventory_levels
-- -----------------------------------------------------
DROP TABLE IF EXISTS `inventory_levels`;
CREATE TABLE `inventory_levels` (
  `id` CHAR(36) NOT NULL,
  `product_id` CHAR(36) NOT NULL,
  `wholesale_qty` INT NOT NULL DEFAULT 0 COMMENT 'Quantity in wholesale units (boxes)',
  `retail_qty` INT NOT NULL DEFAULT 0 COMMENT 'Quantity in retail units (packs)',
  `shelf_qty` INT NOT NULL DEFAULT 0 COMMENT 'Quantity in shelf units (pieces)',
  `wholesale_unit` VARCHAR(50) NOT NULL DEFAULT 'box',
  `retail_unit` VARCHAR(50) NOT NULL DEFAULT 'pack',
  `shelf_unit` VARCHAR(50) NOT NULL DEFAULT 'piece',
  `pcs_per_pack` INT NOT NULL DEFAULT 1,
  `packs_per_box` INT NOT NULL DEFAULT 1,
  `reorder_point` INT NOT NULL DEFAULT 10,
  `max_stock_level` INT NOT NULL DEFAULT 100,
  `last_restock_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_inventory_product` (`product_id`),
  CONSTRAINT `fk_inventory_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: batches
-- -----------------------------------------------------
DROP TABLE IF EXISTS `batches`;
CREATE TABLE `batches` (
  `id` CHAR(36) NOT NULL,
  `product_id` CHAR(36) NOT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `supplier_id` CHAR(36) NULL,
  `quantity_received` INT NOT NULL,
  `quantity_remaining` INT NOT NULL,
  `cost_per_unit` DECIMAL(10, 2) NOT NULL,
  `manufacture_date` DATE NULL,
  `expiry_date` DATE NOT NULL,
  `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_batches_product` (`product_id`),
  INDEX `idx_batches_expiry` (`expiry_date`),
  INDEX `idx_batches_batch_number` (`batch_number`),
  CONSTRAINT `fk_batches_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_batches_supplier`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: transactions
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` CHAR(36) NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `cashier_id` CHAR(36) NOT NULL,
  `customer_id` CHAR(36) NULL,
  `subtotal` DECIMAL(12, 2) NOT NULL,
  `discount_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('percentage', 'fixed') NULL,
  `discount_value` DECIMAL(10, 2) NULL,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12, 2) NOT NULL,
  `payment_type` ENUM('cash', 'gcash', 'maya', 'card', 'mixed') NOT NULL,
  `amount_received` DECIMAL(12, 2) NOT NULL,
  `change_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('completed', 'voided', 'refunded') NOT NULL DEFAULT 'completed',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_transactions_invoice` (`invoice_number`),
  INDEX `idx_transactions_cashier` (`cashier_id`),
  INDEX `idx_transactions_customer` (`customer_id`),
  INDEX `idx_transactions_created_at` (`created_at`),
  INDEX `idx_transactions_status` (`status`),
  CONSTRAINT `fk_transactions_cashier`
    FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: transaction_items
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transaction_items`;
CREATE TABLE `transaction_items` (
  `id` CHAR(36) NOT NULL,
  `transaction_id` CHAR(36) NOT NULL,
  `product_id` CHAR(36) NOT NULL,
  `variant_id` CHAR(36) NULL,
  `batch_id` CHAR(36) NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_name` VARCHAR(100) NULL,
  `quantity` INT NOT NULL,
  `unit_type` ENUM('piece', 'pack', 'box') NOT NULL DEFAULT 'piece',
  `unit_label` VARCHAR(50) NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(12, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_transaction_items_transaction` (`transaction_id`),
  INDEX `idx_transaction_items_product` (`product_id`),
  CONSTRAINT `fk_transaction_items_transaction`
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_items_variant`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_transaction_items_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: orders (Online/Customer Orders)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` CHAR(36) NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `customer_id` CHAR(36) NULL,
  `customer_name` VARCHAR(255) NULL,
  `customer_phone` VARCHAR(20) NULL,
  `shipping_address` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'processing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` DECIMAL(12, 2) NOT NULL,
  `discount_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12, 2) NOT NULL,
  `payment_method` ENUM('cod', 'gcash', 'maya', 'card') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `delivery_address` TEXT NULL,
  `delivery_notes` TEXT NULL,
  `contact_phone` VARCHAR(20) NULL,
  `processed_by` CHAR(36) NULL,
  `processed_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `cancellation_reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_orders_number` (`order_number`),
  INDEX `idx_orders_customer` (`customer_id`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_created_at` (`created_at`),
  CONSTRAINT `fk_orders_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_processor`
    FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: order_items
-- -----------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` CHAR(36) NOT NULL,
  `order_id` CHAR(36) NOT NULL,
  `product_id` CHAR(36) NOT NULL,
  `variant_id` CHAR(36) NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_name` VARCHAR(100) NULL,
  `quantity` INT NOT NULL,
  `unit_type` ENUM('piece', 'pack', 'box') NOT NULL DEFAULT 'piece',
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(12, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order_items_order` (`order_id`),
  INDEX `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_variant`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: settings
-- -----------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` CHAR(36) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` JSON NOT NULL,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: audit_logs
-- -----------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` CHAR(36) NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_logs_user` (`user_id`),
  INDEX `idx_audit_logs_entity` (`entity_type`, `entity_id`),
  INDEX `idx_audit_logs_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: alerts
-- -----------------------------------------------------
DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `id` CHAR(36) NOT NULL,
  `type` ENUM('low_stock', 'expiring_soon', 'expired', 'reorder', 'system') NOT NULL,
  `severity` ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `product_id` CHAR(36) NULL,
  `batch_id` CHAR(36) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_dismissed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` TIMESTAMP NULL,
  `dismissed_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_alerts_type` (`type`),
  INDEX `idx_alerts_severity` (`severity`),
  INDEX `idx_alerts_is_read` (`is_read`),
  INDEX `idx_alerts_created_at` (`created_at`),
  CONSTRAINT `fk_alerts_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_alerts_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
