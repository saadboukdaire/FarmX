-- Add new columns to users table
ALTER TABLE users
ADD COLUMN gender VARCHAR(10) NULL AFTER user_tag,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER gender;

-- Update existing records to set created_at
UPDATE users SET created_at = NOW() WHERE created_at IS NULL; 