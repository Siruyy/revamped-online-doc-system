-- Create test database alongside the dev database
CREATE DATABASE IF NOT EXISTS `svci_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `svci_test`.* TO 'svci_dev'@'%';
FLUSH PRIVILEGES;
