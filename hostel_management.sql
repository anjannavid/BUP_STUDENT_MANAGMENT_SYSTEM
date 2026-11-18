-- BUP Hall Management System
-- Import this file in phpMyAdmin (or: mysql -u root -p < hostel_management.sql)
-- Demo logins:  admin / admin123   and   s2025001 / student123
-- All names, rooms and amounts are sample data.

CREATE DATABASE IF NOT EXISTS hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS meal_skips, meal_prices, mess_feedback, mess_menu, complaints, guests,
  maintenance_requests, notices, fees, students, rooms, admins;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE rooms (
  room_no VARCHAR(10) PRIMARY KEY,
  block CHAR(1) NOT NULL,
  side ENUM('Men') NOT NULL,
  capacity TINYINT NOT NULL DEFAULT 4
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  department VARCHAR(60) NOT NULL,
  batch VARCHAR(30) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  room_no VARCHAR(10) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_room FOREIGN KEY (room_no) REFERENCES rooms(room_no) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  fee_type VARCHAR(30) NOT NULL,
  month_label VARCHAR(20) NOT NULL,
  amount INT NOT NULL,
  status ENUM('Due','Paid') NOT NULL DEFAULT 'Due',
  paid_on DATE NULL,
  reminded TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fee_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  body TEXT NOT NULL,
  category VARCHAR(30) NOT NULL DEFAULT 'General',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE maintenance_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  category VARCHAR(30) NOT NULL,
  description TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('Pending','In progress','Resolved') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_maint_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE guests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  guest_name VARCHAR(100) NOT NULL,
  relation VARCHAR(50) NOT NULL,
  visit_date DATE NOT NULL,
  id_proof_path VARCHAR(255) NULL,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_guest_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE complaints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  body TEXT NOT NULL,
  reply TEXT NULL,
  status ENUM('Open','Replied') NOT NULL DEFAULT 'Open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comp_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- day_index: 0 = Saturday ... 6 = Friday
CREATE TABLE mess_menu (
  day_index TINYINT PRIMARY KEY,
  day_name VARCHAR(12) NOT NULL,
  breakfast VARCHAR(200) NOT NULL,
  lunch VARCHAR(200) NOT NULL,
  dinner VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE mess_feedback (
  student_id VARCHAR(20) PRIMARY KEY,
  rating TINYINT NOT NULL,
  comment VARCHAR(500) NOT NULL DEFAULT '',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fb_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE meal_prices (
  meal ENUM('breakfast','lunch','dinner') PRIMARY KEY,
  price INT NOT NULL
) ENGINE=InnoDB;

-- Meals are ON by default. A row here means the student skipped that meal on that date.
CREATE TABLE meal_skips (
  student_id VARCHAR(20) NOT NULL,
  meal_date DATE NOT NULL,
  meal ENUM('breakfast','lunch','dinner') NOT NULL,
  PRIMARY KEY (student_id, meal_date, meal),
  KEY idx_skip_date (meal_date, meal),
  CONSTRAINT fk_skip_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

