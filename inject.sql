INSERT INTO admin (username, password, nama_admin) 
VALUES ('admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator 2')
ON DUPLICATE KEY UPDATE password = VALUES(password);