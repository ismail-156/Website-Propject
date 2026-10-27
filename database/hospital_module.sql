-- Stores vaccine availability per hospital; absent rows retain the default available state.
CREATE TABLE IF NOT EXISTS hospital_vaccines (
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    in_stock ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (hospital_id, vaccine_id),
    KEY idx_hospital_vaccines_vaccine (vaccine_id),
    CONSTRAINT fk_hospital_vaccines_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE,
    CONSTRAINT fk_hospital_vaccines_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
