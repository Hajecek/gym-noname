-- 015_drop_owner_role.sql
SET NAMES utf8mb4;

UPDATE users SET role = 'admin' WHERE role = 'owner';

INSERT INTO user_roles (user_id, role_id, assigned_at)
SELECT ur.user_id, admin_role.id, ur.assigned_at
FROM user_roles ur
INNER JOIN roles owner_role ON owner_role.id = ur.role_id AND owner_role.slug = 'owner'
INNER JOIN roles admin_role ON admin_role.slug = 'admin'
LEFT JOIN user_roles already ON already.user_id = ur.user_id AND already.role_id = admin_role.id
WHERE already.user_id IS NULL;

DELETE ur FROM user_roles ur
INNER JOIN roles r ON r.id = ur.role_id
WHERE r.slug = 'owner';

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.slug = 'owner';

DELETE FROM roles WHERE slug = 'owner';

ALTER TABLE users
    MODIFY role ENUM('user', 'admin') NOT NULL DEFAULT 'user';
