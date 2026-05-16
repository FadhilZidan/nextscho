-- NextScho School Information System
-- Database : school_sis
-- Charset  : utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `school_sis`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `school_sis`;

-- --------------------------------------------------------
-- users  (semua role: admin, guru, siswa)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','guru','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- classes  (VII-A, VIII-B, dst.)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id`                  INT(11)     NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(50) NOT NULL,
  `grade_level`         VARCHAR(10) NOT NULL,
  `academic_year`       VARCHAR(9)  NOT NULL DEFAULT '2025/2026',
  `homeroom_teacher_id` INT(11)     DEFAULT NULL,
  `created_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- teachers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teachers` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `nip`        VARCHAR(20)  DEFAULT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `subjects`   VARCHAR(100) DEFAULT NULL,
  `phone`      VARCHAR(15)  DEFAULT NULL,
  `photo`      VARCHAR(255) NOT NULL DEFAULT 'default.png',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- students
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `nis`        VARCHAR(20)  NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `class_id`   INT(11)      DEFAULT NULL,
  `gender`     ENUM('L','P') NOT NULL,
  `birth_date` DATE         DEFAULT NULL,
  `address`    TEXT,
  `phone`      VARCHAR(15)  DEFAULT NULL,
  `photo`      VARCHAR(255) NOT NULL DEFAULT 'default.png',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`),
  KEY `class_id` (`class_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`)  REFERENCES `users`   (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- subjects
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `code`       VARCHAR(20)  NOT NULL,
  `teacher_id` INT(11)      DEFAULT NULL,
  `class_id`   INT(11)      DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- grades
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grades` (
  `id`            INT(11)        NOT NULL AUTO_INCREMENT,
  `student_id`    INT(11)        NOT NULL,
  `subject_id`    INT(11)        NOT NULL,
  `score_daily`   DECIMAL(5,2)   DEFAULT NULL,
  `score_mid`     DECIMAL(5,2)   DEFAULT NULL,
  `score_final`   DECIMAL(5,2)   DEFAULT NULL,
  `score_average` DECIMAL(5,2)   DEFAULT NULL,
  `semester`      TINYINT(1)     NOT NULL DEFAULT 1,
  `academic_year` VARCHAR(9)     NOT NULL DEFAULT '2025/2026',
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade` (`student_id`,`subject_id`,`semester`,`academic_year`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- attendance
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         INT(11)    NOT NULL AUTO_INCREMENT,
  `student_id` INT(11)    NOT NULL,
  `class_id`   INT(11)    NOT NULL,
  `date`       DATE       NOT NULL,
  `status`     ENUM('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'hadir',
  `teacher_id` INT(11)    DEFAULT NULL,
  `notes`      VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`)  REFERENCES `classes`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- announcements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(200) NOT NULL,
  `content`    TEXT         NOT NULL,
  `created_by` INT(11)      DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
