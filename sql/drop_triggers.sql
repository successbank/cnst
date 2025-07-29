-- member_addresses 테이블의 트리거 제거
DROP TRIGGER IF EXISTS before_insert_member_addresses;
DROP TRIGGER IF EXISTS before_update_member_addresses;