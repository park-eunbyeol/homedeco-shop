-- inquiries 테이블에 is_private 컬럼 추가
ALTER TABLE `inquiries` 
ADD COLUMN `is_private` tinyint(1) DEFAULT 0 AFTER `message`;
