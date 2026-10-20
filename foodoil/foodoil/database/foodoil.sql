-- ============================================================
-- FoodOil database  (MySQL / MariaDB, works with XAMPP + phpMyAdmin)
-- Import this file in phpMyAdmin: Import tab -> choose file -> Go
-- All products, prices, reviews and orders below are SAMPLE DATA.
-- ============================================================

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  phone         VARCHAR(15)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE products (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  category    VARCHAR(40)  NOT NULL,
  volume      VARCHAR(30)  NOT NULL,
  price       DECIMAL(10,2) NOT NULL,
  mrp         DECIMAL(10,2) NOT NULL,
  description TEXT NULL,
  image       VARCHAR(255) NOT NULL DEFAULT 'products/default.svg',
  stock       INT NOT NULL DEFAULT 0,
  popularity  INT NOT NULL DEFAULT 0,
  rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  reviews     INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_products_category (category),
  KEY idx_products_price (price),
  KEY idx_products_popularity (popularity),
  CONSTRAINT chk_products_price CHECK (price > 0),
  CONSTRAINT chk_products_mrp   CHECK (mrp >= price),
  CONSTRAINT chk_products_stock CHECK (stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_code     VARCHAR(20) NOT NULL,
  user_id        INT UNSIGNED NOT NULL,
  customer_name  VARCHAR(100) NOT NULL,
  phone          VARCHAR(15)  NOT NULL,
  address        VARCHAR(255) NOT NULL,
  city           VARCHAR(60)  NOT NULL,
  state          VARCHAR(60)  NOT NULL,
  pincode        CHAR(6)      NOT NULL,
  subtotal       DECIMAL(10,2) NOT NULL,
  delivery_fee   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total          DECIMAL(10,2) NOT NULL,
  payment_method ENUM('COD','UPI','CARD') NOT NULL DEFAULT 'COD',
  status         ENUM('Pending','Confirmed','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_code (order_code),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (status),
  KEY idx_orders_created (created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_orders_total CHECK (total >= 0)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(160) NOT NULL,
  image        VARCHAR(255) NOT NULL DEFAULT 'products/default.svg',
  quantity     INT NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  subtotal     DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_items_order (order_id),
  KEY idx_items_product (product_id),
  CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  phone      VARCHAR(15)  NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sample users
--   admin@foodoil.com     / Admin@123      (role: admin)
--   customer@foodoil.com  / Customer@123   (role: customer)
-- Change these passwords before showing the project publicly.
-- ------------------------------------------------------------
INSERT INTO users (id, name, email, phone, password_hash, role, created_at) VALUES
(1, 'FoodOil Admin',  'admin@foodoil.com',    NULL,         '$2y$12$QARpqDGK6KvKaaEND.ja4Ofd6hQc1EvCbK/6hNM8/.W46rX8OleD.', 'admin',    '2026-09-01 09:00:00'),
(2, 'Demo Customer',  'customer@foodoil.com', '9000000002', '$2y$12$.2KtFSYpodTdVG40bgz/DO814GS8UWUGup5Hzd9cuTYBqe0sUEPa2',  'customer', '2026-09-10 10:30:00');

-- ------------------------------------------------------------
-- Sample products (demo prices in Indian rupees)
-- ------------------------------------------------------------
INSERT INTO products (id, name, category, volume, price, mrp, description, image, stock, popularity, rating, reviews, is_active) VALUES
(1, 'Sunflower Oil', 'Sunflower Oil', '1 Litre', 150.00, 170.00, '100% pure sunflower oil, rich in vitamin E. Light in taste and ideal for healthy everyday cooking and frying.', 'products/sunflower-1l.svg', 120, 98, 4.5, 250, 1),
(2, 'Sunflower Oil', 'Sunflower Oil', '5 Litre', 690.00, 780.00, '100% pure sunflower oil, rich in vitamin E. Light in taste and ideal for healthy everyday cooking and frying.', 'products/sunflower-5l.svg', 40, 60, 4.4, 120, 1),
(3, 'Groundnut Oil', 'Groundnut Oil', '1 Litre', 165.00, 185.00, 'Cold-pressed style groundnut oil with a rich aroma and nutty flavour. Great for deep frying and traditional Indian dishes.', 'products/groundnut-1l.svg', 90, 85, 4.6, 180, 1),
(4, 'Groundnut Oil', 'Groundnut Oil', '5 Litre', 790.00, 880.00, 'Cold-pressed style groundnut oil with a rich aroma and nutty flavour. Great for deep frying and traditional Indian dishes.', 'products/groundnut-5l.svg', 25, 45, 4.5, 90, 1),
(5, 'Coconut Oil', 'Coconut Oil', '1 Litre', 180.00, 205.00, 'Pure coconut oil with a natural fragrance. Suitable for South Indian cooking, baking and everyday use.', 'products/coconut-1l.svg', 75, 80, 4.7, 210, 1),
(6, 'Coconut Oil', 'Coconut Oil', '500 ml', 100.00, 115.00, 'Pure coconut oil with a natural fragrance. Suitable for South Indian cooking, baking and everyday use.', 'products/coconut-500ml.svg', 8, 55, 4.5, 130, 1),
(7, 'Mustard Oil', 'Mustard Oil', '1 Litre', 160.00, 175.00, 'Strong, pungent mustard oil with a bold flavour. A favourite for pickles, curries and authentic regional recipes.', 'products/mustard-1l.svg', 110, 70, 4.3, 160, 1),
(8, 'Mustard Oil', 'Mustard Oil', '500 ml', 85.00, 95.00, 'Strong, pungent mustard oil with a bold flavour. A favourite for pickles, curries and authentic regional recipes.', 'products/mustard-500ml.svg', 0, 30, 4.2, 70, 1),
(9, 'Olive Oil', 'Olive Oil', '500 ml', 350.00, 400.00, 'Smooth olive oil with a fruity taste. Perfect for salads, sauteing and light cooking.', 'products/olive-500ml.svg', 35, 65, 4.6, 140, 1),
(10, 'Olive Oil', 'Olive Oil', '250 ml', 220.00, 250.00, 'Smooth olive oil with a fruity taste. Perfect for salads, sauteing and light cooking.', 'products/olive-250ml.svg', 30, 40, 4.5, 85, 1),
(11, 'Rice Bran Oil', 'Rice Bran Oil', '1 Litre', 170.00, 190.00, 'Light rice bran oil with a high smoke point and a neutral taste. A popular choice for healthy-heart cooking.', 'products/ricebran-1l.svg', 100, 75, 4.4, 150, 1),
(12, 'Rice Bran Oil', 'Rice Bran Oil', '5 Litre', 820.00, 900.00, 'Light rice bran oil with a high smoke point and a neutral taste. A popular choice for healthy-heart cooking.', 'products/ricebran-5l.svg', 30, 50, 4.5, 95, 1),
(13, 'Sesame Oil', 'Sesame Oil', '500 ml', 210.00, 240.00, 'Aromatic sesame (gingelly) oil with a rich, toasty flavour. Used for tempering, seasoning and traditional cooking.', 'products/sesame-500ml.svg', 60, 52, 4.6, 110, 1),
(14, 'Sesame Oil', 'Sesame Oil', '1 Litre', 395.00, 440.00, 'Aromatic sesame (gingelly) oil with a rich, toasty flavour. Used for tempering, seasoning and traditional cooking.', 'products/sesame-1l.svg', 20, 35, 4.5, 60, 1),
(15, 'Groundnut Oil', 'Groundnut Oil', '500 ml', 90.00, 100.00, 'Cold-pressed style groundnut oil with a rich aroma and nutty flavour. Great for deep frying and traditional Indian dishes.', 'products/groundnut-500ml.svg', 70, 48, 4.5, 95, 1),
(16, 'Sunflower Oil', 'Sunflower Oil', '500 ml', 80.00, 90.00, '100% pure sunflower oil, rich in vitamin E. Light in taste and ideal for healthy everyday cooking and frying.', 'products/sunflower-500ml.svg', 95, 62, 4.4, 105, 1);

-- ------------------------------------------------------------
-- Sample orders for the demo customer (so My Orders and the admin
-- dashboard are not empty). New orders continue from FO123457.
-- ------------------------------------------------------------
INSERT INTO orders (id, order_code, user_id, customer_name, phone, address, city, state, pincode, subtotal, delivery_fee, total, payment_method, status, created_at) VALUES
(123453, 'FO123453', 2, 'Demo Customer', '9000000002', 'Flat 101, Sample Residency, Main Road', 'Hyderabad', 'Telangana', '500001',  820.00, 40.00,  860.00, 'COD',  'Delivered',        '2026-09-25 11:20:00'),
(123454, 'FO123454', 2, 'Demo Customer', '9000000002', 'Flat 101, Sample Residency, Main Road', 'Hyderabad', 'Telangana', '500001',  150.00, 40.00,  190.00, 'UPI',  'Cancelled',        '2026-10-02 18:05:00'),
(123455, 'FO123455', 2, 'Demo Customer', '9000000002', 'Flat 101, Sample Residency, Main Road', 'Hyderabad', 'Telangana', '500001',  360.00, 40.00,  400.00, 'COD',  'Out for Delivery', '2026-10-10 09:45:00'),
(123456, 'FO123456', 2, 'Demo Customer', '9000000002', 'Flat 101, Sample Residency, Main Road', 'Hyderabad', 'Telangana', '500001',  675.00, 40.00,  715.00, 'COD',  'Delivered',        '2026-10-15 14:10:00');

INSERT INTO order_items (order_id, product_id, product_name, image, quantity, unit_price, subtotal) VALUES
(123453, 12, 'Rice Bran Oil (5 Litre)',  'products/ricebran-5l.svg',  1, 820.00, 820.00),
(123454,  1, 'Sunflower Oil (1 Litre)',  'products/sunflower-1l.svg', 1, 150.00, 150.00),
(123455,  5, 'Coconut Oil (1 Litre)',    'products/coconut-1l.svg',   2, 180.00, 360.00),
(123456,  1, 'Sunflower Oil (1 Litre)',  'products/sunflower-1l.svg', 1, 150.00, 150.00),
(123456,  5, 'Coconut Oil (1 Litre)',    'products/coconut-1l.svg',   2, 180.00, 360.00),
(123456,  3, 'Groundnut Oil (1 Litre)',  'products/groundnut-1l.svg', 1, 165.00, 165.00);
