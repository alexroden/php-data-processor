CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(20) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    gender CHAR(1) NOT NULL,
    admission_date DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_external_id (external_id),

    CHECK (gender IN ('M', 'F'))
);