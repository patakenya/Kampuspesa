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
    referrer_code_own VARCHAR(50) NOT NULL UNIQUE,
    tier ENUM('bronze', 'silver', 'gold') NOT NULL,
    payment_ref VARCHAR(50),
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    balance DECIMAL(10, 2) DEFAULT 0.00,
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
    referred_user_id INT, -- Added to track referred user for earnings breakdown
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (referred_user_id) REFERENCES users(id)
);

CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL
);

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

-- Sample data for testing
INSERT INTO users (full_name, username, email, phone, password, referrer_code, referrer_code_own, tier, payment_ref, payment_status, balance, created_at)
VALUES ('John Doe', 'johndoe', 'john@example.com', '+254123456789', '$2y$10$examplehashedpassword', 'REF123', 'UNIQUE123', 'gold', '4178866', 'completed', 450.00, NOW()),
       ('Jane Smith', 'janesmith', 'jane@example.com', '+254987654321', '$2y$10$examplehashedpassword2', 'UNIQUE123', 'UNIQUE456', 'silver', '4178866', 'completed', 0.00, NOW());

INSERT INTO articles (user_id, title, content, category, status, created_at)
VALUES (1, 'Tips for Student Entrepreneurs', 'Content about entrepreneurship...', 'entrepreneurship', 'approved', NOW());

INSERT INTO earnings (user_id, amount, type, referred_user_id, created_at)
VALUES (1, 337.50, 'referral', 2, NOW()),
       (1, 500.00, 'article', NULL, NOW());

INSERT INTO withdrawals (user_id, amount, status, created_at)
VALUES (1, 200.00, 'pending', NOW());

INSERT INTO contact_submissions (name, email, message, created_at)
VALUES ('Jane Doe', 'jane@example.com', 'I have a question about article submissions.', NOW());

INSERT INTO payments (user_id, tier, amount, transaction_code, payment_ref, status, created_at)
VALUES (1, 'gold', 1000.00, 'WS12345678', '4178866', 'completed', NOW()),
       (2, 'silver', 750.00, 'WS98765432', '4178866', 'completed', NOW());