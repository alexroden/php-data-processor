CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    qualification VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    predicted_grade VARCHAR(3),
    actual_grade VARCHAR(3),

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_student_id (student_id),

    UNIQUE KEY uk_student_subject (student_id, qualification, subject),

    CONSTRAINT fk_student_subjects_student
        FOREIGN KEY (student_id) REFERENCES students(id)
            ON DELETE CASCADE
);