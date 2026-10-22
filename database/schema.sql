CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','student') NOT NULL DEFAULT 'student',
 roll_no VARCHAR(30) NULL UNIQUE,
 department VARCHAR(100) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(150) NOT NULL,
 description TEXT NOT NULL,
 category ENUM('Workshop','Seminar','Cultural','Competition','Sports') NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 registration_deadline DATETIME NOT NULL,
 venue VARCHAR(150) NOT NULL,
 organizer VARCHAR(100) NOT NULL,
 capacity INT UNSIGNED NOT NULL,
 status ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
 created_by INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(id),
 INDEX event_schedule (status,starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS registrations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id INT UNSIGNED NOT NULL,
 student_id INT UNSIGNED NOT NULL,
 attendance ENUM('unmarked','present','absent') NOT NULL DEFAULT 'unmarked',
 marked_by INT UNSIGNED NULL,
 marked_at DATETIME NULL,
 registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY one_registration (event_id,student_id),
 FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
 FOREIGN KEY (student_id) REFERENCES users(id),
 FOREIGN KEY (marked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
 email VARCHAR(190) PRIMARY KEY,
 failures INT NOT NULL DEFAULT 0,
 blocked_until DATETIME NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
