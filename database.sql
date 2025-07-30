CREATE DATABASE campusearn;

USE campusearn;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referrer_code VARCHAR(50),
    tier ENUM('bronze', 'silver', 'gold') NOT NULL,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at DATETIME NOT NULL
);

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('student_life', 'entrepreneurship', 'product_promotion') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('referral', 'article') NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sample data for testing
INSERT INTO users (full_name, username, email, phone, password, referrer_code, tier, created_at)
VALUES ('John Doe', 'johndoe', 'john@example.com', '+254123456789', '$2y$10$examplehashedpassword', 'REF123', 'gold', NOW());

INSERT INTO articles (user_id, title, content, category, status, created_at)
VALUES (1, 'Tips for Student Entrepreneurs', 'Content about entrepreneurship...', 'entrepreneurship', 'approved', NOW());

INSERT INTO earnings (user_id, amount, type, created_at)
VALUES (1, 500.00, 'article', NOW());

INSERT INTO withdrawals (user_id, amount, status, created_at)
VALUES (1, 200.00, 'pending', NOW());