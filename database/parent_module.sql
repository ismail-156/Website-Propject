-- Adds the parent vaccination schedule table to the existing VMS database.
-- Existing users, parents, children, vaccines, hospitals, bookings, and notifications tables are reused.
CREATE TABLE IF NOT EXISTS child_vaccinations (
    cv_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'booked', 'vaccinated', 'missed') NOT NULL DEFAULT 'pending',
    vaccinated_on DATE DEFAULT NULL,
    PRIMARY KEY (cv_id),
    UNIQUE KEY uq_child_vaccine (child_id, vaccine_id),
    KEY idx_child_vaccinations_due (child_id, due_date),
    KEY idx_child_vaccinations_status (status),
    CONSTRAINT fk_child_vaccinations_child
        FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE,
    CONSTRAINT fk_child_vaccinations_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The existing children table in this project predates the blood-group profile field.
SET @add_blood_group = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'children' AND COLUMN_NAME = 'blood_group') = 0,
    'ALTER TABLE children ADD COLUMN blood_group VARCHAR(3) NULL AFTER gender',
    'SELECT 1'
);
PREPARE parent_module_stmt FROM @add_blood_group;
EXECUTE parent_module_stmt;
DEALLOCATE PREPARE parent_module_stmt;
