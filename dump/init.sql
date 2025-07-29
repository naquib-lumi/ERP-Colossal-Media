SET PASSWORD FOR 'root'@'localhost' = 'ctapps123';
SET PASSWORD FOR 'root'@'%' = 'ctapps123';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
CREATE USER IF NOT EXISTS 'erp_user'@'%' IDENTIFIED WITH mysql_native_password BY 'ctapps123';
GRANT ALL PRIVILEGES ON erp_colossal.* TO 'erp_user'@'%';
FLUSH PRIVILEGES;