ALTER TABLE pension_claims
  ADD COLUMN reviewed_by INT NULL,
  ADD COLUMN admin_comments TEXT NULL,
  ADD COLUMN reviewed_at TIMESTAMP NULL,
  ADD CONSTRAINT fk_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL; 