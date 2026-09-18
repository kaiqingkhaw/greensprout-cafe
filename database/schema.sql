CREATE DATABASE IF NOT EXISTS greensprout_cafe
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greensprout_cafe;

CREATE TABLE request_limits (
  bucket CHAR(64) PRIMARY KEY,
  hits INT UNSIGNED NOT NULL,
  expires_at BIGINT UNSIGNED NOT NULL,
  INDEX (expires_at)
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  profile_image VARCHAR(255) DEFAULT NULL,
  member_since DATE NOT NULL DEFAULT (CURRENT_DATE),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) UNSIGNED NOT NULL,
  category VARCHAR(100) NOT NULL,
  image VARCHAR(500) NOT NULL DEFAULT '',
  featured BOOLEAN NOT NULL DEFAULT FALSE,
  discounted BOOLEAN NOT NULL DEFAULT FALSE,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  calories SMALLINT UNSIGNED DEFAULT 0,
  protein SMALLINT UNSIGNED DEFAULT 0,
  carbs SMALLINT UNSIGNED DEFAULT 0,
  fats SMALLINT UNSIGNED DEFAULT 0,
  fiber SMALLINT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_discount_percent CHECK (discount_percent <= 100),
  INDEX idx_products_category (category)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  order_number VARCHAR(32) NOT NULL UNIQUE,
  order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  items JSON NOT NULL,
  delivery_details JSON NULL,
  price_breakdown JSON NULL,
  request_key VARCHAR(64) NULL,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'cash',
  total DECIMAL(10,2) UNSIGNED NOT NULL,
  status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  estimated_delivery_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 45,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_orders_user_date (user_id, order_date),
  INDEX idx_orders_status (status)
  ,UNIQUE KEY uq_order_request (user_id, request_key)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  unit_price DECIMAL(10,2) UNSIGNED NOT NULL,
  special_request VARCHAR(500) NOT NULL DEFAULT '',
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE contact_submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO products (name, description, price, category, image, featured, calories, protein, carbs, fats, fiber) VALUES
('Avocado Garden Toast', 'Sourdough topped with avocado, tomatoes, sprouts, and seeds.', 18.90, 'Breakfast', 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=900&q=80', TRUE, 420, 12, 48, 20, 9),
('Harvest Buddha Bowl', 'Quinoa, roasted seasonal vegetables, chickpeas, and tahini dressing.', 24.90, 'Mains', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=80', TRUE, 560, 19, 72, 22, 14),
('Green Glow Smoothie', 'Spinach, mango, banana, oat milk, and chia seeds.', 14.90, 'Drinks', 'https://images.unsplash.com/photo-1610970881699-44a5587cabec?auto=format&fit=crop&w=900&q=80', FALSE, 240, 6, 44, 5, 8),
('Berry Chia Parfait', 'Coconut yoghurt, chia pudding, granola, and mixed berries.', 16.90, 'Desserts', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80', FALSE, 330, 8, 46, 13, 10);
