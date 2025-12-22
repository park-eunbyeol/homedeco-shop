-- 문의 게시판 테이블 생성
CREATE TABLE IF NOT EXISTS `inquiries` (
  `inquiry_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','answered','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inquiry_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 문의 답변 테이블 생성
CREATE TABLE IF NOT EXISTS `inquiry_replies` (
  `reply_id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `reply_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `inquiry_id` (`inquiry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 외래 키 제약 조건 추가
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `inquiry_replies`
  ADD CONSTRAINT `replies_inquiry_fk` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`inquiry_id`) ON DELETE CASCADE;
