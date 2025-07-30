-- Drop existing tables to ensure a clean setup
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;

-- Create admins table for admin authentication (admin/register.php, admin/login.php, admin_header.php)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table for user authentication and profiles (header.php, index.php, dashboard.php, write_article.php)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referrer_code VARCHAR(50),
    referrer_code_own VARCHAR(50) NOT NULL UNIQUE,
    tier ENUM('bronze', 'silver', 'gold') NOT NULL,
    payment_ref VARCHAR(50),
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    INDEX idx_email (email),
    INDEX idx_referrer_code_own (referrer_code_own)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create articles table for storing user-submitted articles (write_article.php, articles.php, article.php, admin/articles.php)
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create comments table for article comments (article.php)
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_article_id (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create transactions table for financial records (admin/transactions.php)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('deposit', 'withdrawal', 'earning') NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data for testing
-- Admins
INSERT INTO admins (full_name, email, password, created_at)
VALUES 
    ('Admin User', 'admin@example.com', '$2y$10$examplehashedpassword', NOW()),
    ('Jane Admin', 'jane@example.com', '$2y$10$examplehashedpassword2', NOW());

-- Users
INSERT INTO users (full_name, username, email, phone, password, referrer_code, referrer_code_own, tier, payment_ref, payment_status, balance, created_at)
VALUES 
    ('John Doe', 'johndoe', 'john@example.com', '+254123456789', '$2y$10$examplehashedpassword', 'REF123', 'UNIQUE123', 'silver', '4178866', 'completed', 450.00, NOW()),
    ('Jane Smith', 'janesmith', 'jane@example.com', '+254987654321', '$2y$10$examplehashedpassword2', 'REF456', 'UNIQUE456', 'gold', '4178867', 'completed', 1000.00, NOW()),
    ('Bob Brown', 'bobbrown', 'bob@example.com', '+254111222333', '$2y$10$examplehashedpassword3', 'REF789', 'UNIQUE789', 'bronze', NULL, 'pending', 0.00, NOW());

-- Articles
INSERT INTO articles (user_id, title, content, category, status, created_at, featured_image)
VALUES 
    (1, 'Balancing Studies and Earning', '<p><strong>Time management</strong> tips for students...</p>', 'student_life', 'approved', NOW(), 'images/articles/article_1631234567_abc123.jpg'),
    (2, 'Starting a Side Hustle', '<p>Entrepreneurship tips for <em>students</em>...</p>', 'entrepreneurship', 'pending', NOW(), NULL),
    (1, 'Promoting Campus Products', '<p>Learn to market products...</p>', 'product_promotion', 'rejected', NOW(), 'images/articles/article_1631234568_def456.jpg');

-- Comments
INSERT INTO comments (article_id, user_id, content, created_at)
VALUES 
    (1, 1, 'Great tips! Really helped me manage my time.', NOW()),
    (1, 2, 'Very informative article!', NOW());

-- Transactions
INSERT INTO transactions (user_id, amount, type, status, created_at)
VALUES 
    (1, 300.00, 'earning', 'completed', NOW()),
    (2, 500.00, 'earning', 'pending', NOW()),
    (1, 200.00, 'withdrawal', 'failed', NOW());
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tier ENUM('bronze', 'silver', 'gold') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_code VARCHAR(50) NOT NULL,
    payment_ref VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);