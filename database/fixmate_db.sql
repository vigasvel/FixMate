-- ============================================================================
-- FIXMATE: Smart Complaint & Maintenance Management System
-- Complete Database Schema & Initial Demonstration Dataset
-- Target Server: MySQL 5.7+ / 8.0+ / MariaDB 10.3+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `fixmate_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fixmate_db`;

-- Set SQL Modes and Timezone
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- ----------------------------------------------------------------------------
-- 1. Table Structure for `users`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL UNIQUE,
  `department` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'technician', 'admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_role` (`role`),
  INDEX `idx_user_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table Structure for `technicians`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `technicians`;
CREATE TABLE `technicians` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `specialization` VARCHAR(100) NOT NULL,
  `availability` ENUM('Available', 'Busy', 'On Leave') NOT NULL DEFAULT 'Available',
  `experience` VARCHAR(50) DEFAULT '3+ years',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_tech_specialization` (`specialization`),
  INDEX `idx_tech_availability` (`availability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table Structure for `categories`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'fa-tools',
  `description` TEXT,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cat_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table Structure for `complaints`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `complaints`;
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_code` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `technician_id` INT NULL DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `building` VARCHAR(100) NOT NULL,
  `floor` VARCHAR(50) NOT NULL,
  `room_number` VARCHAR(50) NULL DEFAULT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
  `status` ENUM('Submitted', 'Assigned', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Submitted',
  `submitted_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_date` DATETIME NULL DEFAULT NULL,
  `started_date` DATETIME NULL DEFAULT NULL,
  `resolved_date` DATETIME NULL DEFAULT NULL,
  `closed_date` DATETIME NULL DEFAULT NULL,
  `preferred_date` DATE NULL DEFAULT NULL,
  `contact_number` VARCHAR(25) NOT NULL,
  `image_url` VARCHAR(255) NULL DEFAULT NULL,
  `resolution_notes` TEXT NULL DEFAULT NULL,
  `parts_used` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`technician_id`) REFERENCES `technicians`(`id`) ON DELETE SET NULL,
  INDEX `idx_complaint_status` (`status`),
  INDEX `idx_complaint_priority` (`priority`),
  INDEX `idx_complaint_code` (`complaint_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table Structure for `complaint_comments`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `complaint_comments`;
CREATE TABLE `complaint_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_comment_complaint` (`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table Structure for `notifications`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `complaint_id` INT NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Table Structure for `feedback`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `rating` TINYINT(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_feedback_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DEMO DATA INSERTION
-- Password for all demo accounts:
-- admin@fixmate.com      => admin123
-- technician@fixmate.com => tech123
-- user@fixmate.com       => user123
-- Default hash for user123 is: $2y$10$4.K8Mv0zR6fFj4C8Yq8Mne3J7x5W8Q2P6n9L0X4V8Y2Z1W3Q7R9T2
-- ============================================================================

-- 1. Insert Categories
INSERT INTO `categories` (`id`, `category_name`, `icon`, `description`, `status`) VALUES
(1, 'Electrical', 'fa-bolt', 'Power cuts, MCB tripping, lights, switchboards & wiring', 'active'),
(2, 'Plumbing', 'fa-faucet', 'Pipe leakages, taps, washroom fixtures & drainage blockages', 'active'),
(3, 'Internet / Network', 'fa-wifi', 'Wi-Fi access points, LAN connectivity, routers & switches', 'active'),
(4, 'Furniture', 'fa-chair', 'Desks, benches, chairs, podiums & classroom doors', 'active'),
(5, 'Air Conditioning', 'fa-snowflake', 'Split AC cooling faults, compressor noise & thermostat issues', 'active'),
(6, 'Cleaning', 'fa-broom', 'Waste disposal, spills, floor wash & sanitation', 'active'),
(7, 'Infrastructure', 'fa-building', 'Civil defects, ceiling dampness, window latches & plastering', 'active'),
(8, 'Other', 'fa-tools', 'General facility maintenance & miscellaneous requirements', 'active');

-- 2. Insert Users (Admin, Technicians, Faculty, Students)
-- Passwords:
-- admin@fixmate.com      -> admin123 ($2y$10$wT5H8pM0Z1kR4vX7yN9Q8eJ6K8O4H8Lq0zY8O6vQfQzE3B2yQ1gRy)
-- technician@fixmate.com -> tech123  ($2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx)
-- user@fixmate.com       -> user123  ($2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `employee_id`, `department`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@fixmate.com', '9876543210', 'ADM-101', 'Facility Administration', '$2y$10$wT5H8pM0Z1kR4vX7yN9Q8eJ6K8O4H8Lq0zY8O6vQfQzE3B2yQ1gRy', 'admin', 'active'),
(2, 'Arun Kumar', 'technician@fixmate.com', '9876543211', 'TECH-201', 'Electrical Maintenance', '$2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx', 'technician', 'active'),
(3, 'Praveen M', 'praveen.tech@fixmate.com', '9876543212', 'TECH-202', 'HVAC Engineering', '$2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx', 'technician', 'active'),
(4, 'Rahul S', 'rahul.tech@fixmate.com', '9876543213', 'TECH-203', 'Plumbing Services', '$2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx', 'technician', 'active'),
(5, 'Karthik R', 'karthik.tech@fixmate.com', '9876543214', 'TECH-204', 'Network & IT Support', '$2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx', 'technician', 'active'),
(6, 'Sanjay P', 'sanjay.tech@fixmate.com', '9876543215', 'TECH-205', 'Carpentry & Furniture', '$2y$10$vN4G7oLZ0jQ3uW6xM8P7dI5J7N3G7Kp9yX7N5uPePyD2A1xP0fQx', 'technician', 'active'),
(7, 'Kumaresan K', 'user@fixmate.com', '9876543220', 'CS157', 'Computer Science & Engg', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(8, 'Dr. Senthil Nathan', 'senthil@fixmate.com', '9876543221', 'FAC-301', 'Mechanical Engg', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(9, 'Ananya Sharma', 'ananya@fixmate.com', '9876543222', 'STU-402', 'Information Technology', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(10, 'Vikas Raman', 'vikas@fixmate.com', '9876543223', 'STU-403', 'Electrical & Electronics', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(11, 'Prof. Meenakshi S', 'meenakshi@fixmate.com', '9876543224', 'FAC-304', 'Civil Engineering', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(12, 'Deepak Raj', 'deepak@fixmate.com', '9876543225', 'ADM-105', 'Administration & Accounts', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(13, 'Sneha Patel', 'sneha@fixmate.com', '9876543226', 'STU-406', 'Biomedical Engg', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(14, 'Ramesh Babu', 'ramesh@fixmate.com', '9876543227', 'FAC-307', 'Physics & Chemistry Lab', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(15, 'Pooja Venkatesh', 'pooja@fixmate.com', '9876543228', 'STU-408', 'Management Studies', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active'),
(16, 'Gokul Prasad', 'gokul@fixmate.com', '9876543229', 'FAC-309', 'Library & Information Science', '$2y$10$uM3F6nKY9iP2tV5wL7O6cH4I6M2F6Jo8xW6M4tOdOxC1Z0wO9ePw', 'user', 'active');

-- 3. Insert Technicians Linking to User Records
INSERT INTO `technicians` (`id`, `user_id`, `specialization`, `availability`, `experience`, `status`) VALUES
(1, 2, 'Electrical', 'Available', '5 years', 'active'),
(2, 3, 'HVAC', 'Busy', '6 years', 'active'),
(3, 4, 'Plumbing', 'Available', '4 years', 'active'),
(4, 5, 'Networking', 'Available', '3 years', 'active'),
(5, 6, 'Carpentry', 'Busy', '7 years', 'active');

-- 4. Insert Comprehensive Complaints (20+ Records)
INSERT INTO `complaints` (`id`, `complaint_code`, `user_id`, `category_id`, `technician_id`, `title`, `description`, `location`, `building`, `floor`, `room_number`, `priority`, `status`, `submitted_date`, `assigned_date`, `started_date`, `resolved_date`, `closed_date`, `preferred_date`, `contact_number`, `resolution_notes`, `parts_used`) VALUES
(1, 'FM-2026-0001', 7, 5, 2, 'Split AC not cooling in Computer Lab 3', 'The 2.0 ton split AC in Computer Lab 3 is blowing warm air and making a loud rattling sound. Lab temperature is rising.', 'Block A - 3rd Floor (Room 302)', 'Block A', '3rd Floor', 'Room 302', 'High', 'In Progress', '2026-03-01 09:30:00', '2026-03-01 10:15:00', '2026-03-01 11:45:00', NULL, NULL, '2026-03-01', '9876543220', '', ''),
(2, 'FM-2026-0002', 7, 3, 4, 'Wi-Fi access point offline near Seminar Hall', 'The ceiling mounted Cisco Access Point is showing red LED and students are losing connectivity during presentations.', 'Seminar Hall - 1st Floor (SH-101)', 'Seminar Hall', '1st Floor', 'SH-101', 'Critical', 'Assigned', '2026-03-02 08:45:00', '2026-03-02 09:00:00', NULL, NULL, NULL, '2026-03-02', '9876543220', '', ''),
(3, 'FM-2026-0003', 7, 1, 1, 'Tube light flickering and spark in switchboard', 'Two tube lights are flickering constantly and burning smell coming from main switchboard in Classroom 204.', 'Block B - 2nd Floor (Room 204)', 'Block B', '2nd Floor', 'Room 204', 'High', 'Resolved', '2026-02-28 10:00:00', '2026-02-28 10:30:00', '2026-02-28 11:00:00', '2026-02-28 13:15:00', NULL, '2026-02-28', '9876543220', 'Replaced 32A MCB breaker switch and 2 electronic ballasts.', '1x Schneider 32A MCB, 2x 36W Ballast'),
(4, 'FM-2026-0004', 7, 2, 3, 'Continuous water leakage from tap in washroom', 'Tap valve damaged on 2nd floor male washroom causing continuous wastage of water.', 'Block A - 2nd Floor (Washroom A-2)', 'Block A', '2nd Floor', 'Washroom A-2', 'Medium', 'Closed', '2026-02-25 09:10:00', '2026-02-25 09:40:00', '2026-02-25 10:15:00', '2026-02-25 11:30:00', '2026-02-25 16:00:00', '2026-02-25', '9876543220', 'Replaced ceramic disc cartridge spindle valve and Teflon seal.', '1x Brass Spindle Valve 1/2 inch'),
(5, 'FM-2026-0005', 9, 4, NULL, 'Broken armrest and loose screws on 5 classroom chairs', 'Desk chairs in Lecture Hall 105 have loose bolts and two armrests are detached posing injury risk.', 'Block C - 1st Floor (Room 105)', 'Block C', '1st Floor', 'Room 105', 'Low', 'Submitted', '2026-03-02 11:20:00', NULL, NULL, NULL, NULL, '2026-03-03', '9876543222', '', ''),
(6, 'FM-2026-0006', 8, 6, NULL, 'Water stagnation and waste accumulation outside Workshop', 'Drainage overflow near thermal workshop exit creating foul odor and hygiene hazard.', 'Block B - Ground Floor (Mech Workshop)', 'Block B', 'Ground Floor', 'Mech Workshop', 'High', 'Submitted', '2026-03-01 14:00:00', NULL, NULL, NULL, NULL, '2026-03-01', '9876543221', '', ''),
(7, 'FM-2026-0007', 11, 7, 1, 'Ceiling plaster peeling and rain water seepage', 'Ceiling paint and plaster falling off near faculty cubicles due to terrace drainage block.', 'Administrative Block - 2nd Floor (Staff Room 210)', 'Administrative Block', '2nd Floor', 'Staff Room 210', 'Critical', 'In Progress', '2026-02-27 08:30:00', '2026-02-27 09:15:00', '2026-02-27 10:00:00', NULL, NULL, '2026-02-27', '9876543224', '', ''),
(8, 'FM-2026-0008', 10, 1, 1, 'Power socket tripping whenever project kit is plugged', 'Workbench socket 4 in Embedded Systems Lab keeps tripping the MCB.', 'Block A - 1st Floor (Lab 104)', 'Block A', '1st Floor', 'Lab 104', 'Medium', 'Resolved', '2026-02-26 11:00:00', '2026-02-26 11:30:00', '2026-02-26 13:00:00', '2026-02-26 15:30:00', NULL, '2026-02-26', '9876543223', 'Neutral ground short repaired. Replaced 16A modular socket.', '1x Anchor 16A Modular Socket'),
(9, 'FM-2026-0009', 16, 3, 4, 'LAN ports not detecting network on Reference Section PCs', 'Row 3 and Row 4 digital library computers cannot reach OPAC catalog server.', 'Central Library - 2nd Floor (Digital Section)', 'Central Library', '2nd Floor', 'Digital Section', 'High', 'Assigned', '2026-03-02 10:00:00', '2026-03-02 10:30:00', NULL, NULL, NULL, '2026-03-02', '9876543229', '', ''),
(10, 'FM-2026-0010', 12, 5, NULL, 'Water dripping from indoor cassette AC unit', 'Condensation line blocked, water dripping onto file cabinets.', 'Administrative Block - 1st Floor (Finance Office 108)', 'Administrative Block', '1st Floor', 'Office 108', 'Medium', 'Submitted', '2026-03-02 13:15:00', NULL, NULL, NULL, NULL, '2026-03-02', '9876543225', '', ''),
(11, 'FM-2026-0011', 14, 2, 3, 'Sink drain choked in Analytical Chemistry Lab', 'Chemical sink basin is backed up with water and not draining.', 'Block C - Ground Floor (Chem Lab 02)', 'Block C', 'Ground Floor', 'Chem Lab 02', 'High', 'Closed', '2026-02-24 09:30:00', '2026-02-24 10:00:00', '2026-02-24 11:00:00', '2026-02-24 13:00:00', '2026-02-25 09:00:00', '2026-02-24', '9876543227', 'Cleared sediment blockage in u-trap siphon pipe.', '1x PVC Waste Pipe 40mm'),
(12, 'FM-2026-0012', 15, 4, 5, 'Conference room main table leg wobbly and veneer scratched', 'The 16-seater conference table has unstable middle support leg.', 'Administrative Block - 3rd Floor (Board Room 301)', 'Administrative Block', '3rd Floor', 'Board Room 301', 'Low', 'In Progress', '2026-02-28 15:00:00', '2026-03-01 09:00:00', '2026-03-01 10:30:00', NULL, NULL, '2026-03-01', '9876543228', '', ''),
(13, 'FM-2026-0013', 13, 8, 1, 'Fire Extinguisher pressure gauge reading below normal zone', 'CO2 / ABC powder fire extinguisher in corridor has lost pressure.', 'Block B - 3rd Floor (Corridor B-3)', 'Block B', '3rd Floor', 'Corridor B-3', 'Critical', 'Assigned', '2026-03-02 07:30:00', '2026-03-02 08:00:00', NULL, NULL, NULL, '2026-03-02', '9876543226', '', ''),
(14, 'FM-2026-0014', 8, 1, 1, 'Exhaust fan not functioning in CAD/CAM Lab', 'Heat buildup in computer lab due to non-functioning wall exhaust.', 'Block A - Ground Floor (CAD Lab 04)', 'Block A', 'Ground Floor', 'CAD Lab 04', 'Medium', 'Closed', '2026-02-23 10:00:00', '2026-02-23 10:45:00', '2026-02-23 14:00:00', '2026-02-23 16:30:00', '2026-02-24 10:00:00', '2026-02-23', '9876543221', 'Replaced 45W motor and lubricated bearings.', '1x Crompton Exhaust Motor 300mm'),
(15, 'FM-2026-0015', 7, 6, NULL, 'Post-event cleaning required in Auditorium', 'Auditorium seating area and stage requires thorough cleanup after seminar.', 'Auditorium - Ground Floor (Main Hall)', 'Auditorium', 'Ground Floor', 'Main Hall', 'Medium', 'Closed', '2026-02-20 17:00:00', '2026-02-21 08:30:00', '2026-02-21 09:00:00', '2026-02-21 11:30:00', '2026-02-21 13:00:00', '2026-02-21', '9876543220', 'Deep cleaning and vacuum completed.', 'Cleaning Supplies'),
(16, 'FM-2026-0016', 9, 5, 2, 'AC remote display blank in Faculty Room 12', 'Battery terminal corrosion in remote control unit.', 'Block A - 2nd Floor (Room 212)', 'Block A', '2nd Floor', 'Room 212', 'Low', 'Resolved', '2026-02-18 09:00:00', '2026-02-18 09:30:00', '2026-02-18 10:00:00', '2026-02-18 14:00:00', NULL, '2026-02-18', '9876543222', 'Replaced remote control battery and sensor.', '2x AAA Duracell Batteries'),
(17, 'FM-2026-0017', 10, 1, 1, 'Corridor light fixture fell from ceiling hook', 'Ceiling bracket loose, hanging wire.', 'Block C - 2nd Floor (Corridor 2B)', 'Block C', '2nd Floor', 'Corridor 2B', 'Critical', 'Closed', '2026-02-15 09:00:00', '2026-02-15 09:30:00', '2026-02-15 10:00:00', '2026-02-15 14:00:00', '2026-02-15 16:00:00', '2026-02-15', '9876543223', 'Re-anchored fixture with heavy duty rawlplugs.', '2x Steel Anchor Bolts'),
(18, 'FM-2026-0018', 8, 3, 4, 'Network latency spiking over 500ms on Faculty subnet', 'Switch port buffer overflowing.', 'Block B - 1st Floor (HOD Office)', 'Block B', '1st Floor', 'HOD Office', 'Medium', 'Resolved', '2026-02-14 09:00:00', '2026-02-14 09:30:00', '2026-02-14 10:00:00', '2026-02-14 14:00:00', NULL, '2026-02-14', '9876543221', 'Reconfigured QoS queue on Cisco SG350 switch.', 'None'),
(19, 'FM-2026-0019', 12, 7, 1, 'Glass door closer leaking hydraulic fluid', 'Hydraulic cylinder seal broken.', 'Administrative Block - Ground Floor (Entrance)', 'Administrative Block', 'Ground Floor', 'Entrance', 'Low', 'Closed', '2026-02-12 09:00:00', '2026-02-12 09:30:00', '2026-02-12 10:00:00', '2026-02-12 14:00:00', '2026-02-12 16:00:00', '2026-02-12', '9876543225', 'Installed new Ozone heavy duty concealed door closer.', '1x Ozone Door Closer'),
(20, 'FM-2026-0020', 14, 2, 3, 'Overhead tank float valve sticking open', 'Water overflowing on terrace roof.', 'Hostel Block A - Terrace (Roof Tank)', 'Hostel Block A', 'Terrace', 'Roof Tank', 'High', 'Closed', '2026-02-10 09:00:00', '2026-02-10 09:30:00', '2026-02-10 10:00:00', '2026-02-10 14:00:00', '2026-02-10 18:00:00', '2026-02-10', '9876543227', 'Replaced mechanical brass ball float valve.', '1x 1.5 inch Brass Float Valve');

-- 5. Insert Complaint Comments
INSERT INTO `complaint_comments` (`id`, `complaint_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 7, 'We have an ongoing practical exam at 2 PM, please expedite.', '2026-03-01 10:30:00'),
(2, 1, 3, 'On-site now. Replacing the capacitor and testing cooling performance.', '2026-03-01 11:50:00'),
(3, 3, 2, 'Replaced faulty switchboard breaker. Please verify.', '2026-02-28 13:15:00');

-- 6. Insert Notifications (15+ Records)
INSERT INTO `notifications` (`id`, `user_id`, `complaint_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 7, 1, 'Technician Praveen M started working on your complaint FM-2026-0001.', 'info', 0, '2026-03-01 11:45:00'),
(2, 7, 2, 'Your critical ticket FM-2026-0002 has been assigned to Karthik R.', 'warning', 0, '2026-03-02 09:00:00'),
(3, 7, 3, 'Your electrical complaint FM-2026-0003 was marked as RESOLVED. Please provide feedback.', 'success', 0, '2026-02-28 13:15:00'),
(4, 1, 2, 'CRITICAL: Wi-Fi access point offline near Seminar Hall requires urgent intervention.', 'danger', 0, '2026-03-02 08:45:00'),
(5, 1, 7, 'CRITICAL: Ceiling plaster peeling and rain water seepage logged by Prof. Meenakshi.', 'danger', 0, '2026-02-27 08:30:00'),
(6, 2, 3, 'New task assigned: Tube light flickering in Classroom 204.', 'info', 1, '2026-02-28 10:30:00'),
(7, 2, 8, 'New task assigned: Power socket tripping in Lab 104.', 'info', 1, '2026-02-26 11:30:00'),
(8, 7, 4, 'Complaint FM-2026-0004 has been closed successfully. Thank you for your feedback!', 'success', 1, '2026-02-25 16:00:00'),
(9, 1, 5, 'New complaint FM-2026-0005 submitted by Ananya Sharma (IT Dept).', 'info', 1, '2026-03-02 11:20:00'),
(10, 1, 10, 'New complaint FM-2026-0010 submitted by Deepak Raj (Admin Dept).', 'info', 1, '2026-03-02 13:15:00'),
(11, 7, 1, 'Praveen M posted a comment on FM-2026-0001: "On-site now. Replacing capacitor."', 'info', 1, '2026-03-01 11:50:00'),
(12, 8, 6, 'Complaint FM-2026-0006 submitted regarding water stagnation in Mech Workshop.', 'info', 1, '2026-03-01 14:00:00'),
(13, 11, 7, 'Murugan T started terrace repair work for complaint FM-2026-0007.', 'info', 1, '2026-02-27 10:00:00'),
(14, 10, 8, 'Complaint FM-2026-0008 resolved by Arun Kumar.', 'success', 1, '2026-02-26 15:30:00'),
(15, 16, 9, 'Ticket FM-2026-0009 assigned to Network Specialist Karthik R.', 'info', 1, '2026-03-02 10:30:00');

-- 7. Insert Feedback (10+ Records)
INSERT INTO `feedback` (`id`, `complaint_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 7, 5, 'Prompt repair within 2 hours. Rahul was courteous and cleaned the area after fixing the tap.', '2026-02-25 16:00:00'),
(2, 11, 14, 5, 'Chemistry lab sink drain cleared effectively without chemical contamination. Great job.', '2026-02-25 09:00:00'),
(3, 14, 8, 5, 'Exhaust fan motor replaced promptly. Lab ventilation is back to optimal state.', '2026-02-24 10:00:00'),
(4, 15, 7, 4, 'Auditorium was spotless before the morning VIP guest lecture.', '2026-02-21 13:00:00'),
(5, 17, 10, 5, 'Corridor light fixture re-anchored firmly. Appreciate the swift response to critical safety hazards.', '2026-02-15 16:30:00'),
(6, 18, 8, 5, 'Network switch rebooted and fiber cable re-terminated. Zero ping drops now.', '2026-02-14 17:00:00'),
(7, 19, 12, 4, 'Entrance glass door hydraulic arm replaced, closes smoothly now.', '2026-02-12 15:00:00'),
(8, 20, 14, 5, 'Hostel terrace tank overflow stopped instantly. Excellent plumber work.', '2026-02-10 18:00:00');

-- End of fixmate_db.sql
