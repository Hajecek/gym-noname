-- 017_drop_staff_role.sql
SET NAMES utf8mb4;

UPDATE users SET role = 'user' WHERE role = 'staff';

DELETE ur FROM user_roles ur
INNER JOIN roles r ON r.id = ur.role_id
WHERE r.slug = 'staff';

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.slug = 'staff';

DELETE FROM roles WHERE slug = 'staff';

ALTER TABLE users
    MODIFY role ENUM('user', 'admin') NOT NULL DEFAULT 'user';
