-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS homedeco_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE homedeco_shop;

-- 회원 테이블
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 카테고리 테이블
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 상품 테이블
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    main_image VARCHAR(255),
    style_tag VARCHAR(50), -- modern, vintage, minimal, scandinavian, etc
    color_tag VARCHAR(50), -- white, black, wood, metal, etc
    room_tag VARCHAR(50), -- living, bedroom, kitchen, etc
    views INT DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 0,
    review_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    INDEX idx_style (style_tag),
    INDEX idx_color (color_tag),
    INDEX idx_room (room_tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 상품 이미지 테이블
CREATE TABLE product_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 장바구니 테이블
CREATE TABLE cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 찜하기 테이블
CREATE TABLE wishlist (
    wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 주문 테이블
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_name VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 주문 상품 테이블
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 리뷰 테이블
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    content TEXT,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 리뷰 이미지 테이블
CREATE TABLE review_images (
    review_image_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 사용자 취향 테이블 (AI 추천용)
CREATE TABLE user_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    style_preference VARCHAR(50), -- 선호 스타일
    color_preference VARCHAR(50), -- 선호 색상
    room_preference VARCHAR(50), -- 주로 관심있는 공간
    price_range VARCHAR(20), -- 가격대
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 추천 기록 테이블
CREATE TABLE recommendation_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    recommendation_type VARCHAR(50), -- ai_based, similar, popular
    score DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 샘플 카테고리 데이터
INSERT INTO categories (name, description) VALUES
('거실', '소파, 테이블, 수납장 등 거실 가구'),
('침실', '침대, 협탁, 화장대 등 침실 가구'),
('주방/식당', '식탁, 의자, 수납장 등'),
('조명', '펜던트, 스탠드, 무드등 등'),
('소품', '쿠션, 러그, 액자 등 인테리어 소품');

-- 샘플 관리자 계정 (비밀번호: admin123)
INSERT INTO users (email, password, name, phone, is_admin) VALUES
('admin@homedeco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자', '010-0000-0000', 1);

-- 샘플 상품 데이터
INSERT INTO products (category_id, name, description, price, stock, main_image, style_tag, color_tag, room_tag) VALUES
(1, '모던 패브릭 3인 소파', '편안한 착석감과 심플한 디자인의 모던 소파', 890000, 15, '/images/sofa1.jpg', 'modern', 'gray', 'living'),
(1, '북유럽 원목 커피 테이블', '오크 원목으로 제작된 내츄럴 커피테이블', 320000, 20, '/images/table1.jpg', 'scandinavian', 'wood', 'living'),
(2, '미니멀 퀸사이즈 침대 프레임', '깔끔한 라인의 화이트 침대 프레임', 450000, 10, '/images/bed1.jpg', 'minimal', 'white', 'bedroom'),
(3, '4인용 원목 식탁 세트', '의자 4개 포함된 내츄럴 원목 식탁', 680000, 8, '/images/dining1.jpg', 'scandinavian', 'wood', 'dining'),
(4, '펜던트 조명 - 블랙', '인더스트리얼 스타일 펜던트 조명', 120000, 30, '/images/light1.jpg', 'industrial', 'black', 'living'),
(5, '북유럽 스타일 쿠션 세트', '4개 세트, 다양한 패턴', 45000, 50, '/images/cushion1.jpg', 'scandinavian', 'multi', 'living');