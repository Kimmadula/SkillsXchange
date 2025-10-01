-- Clear all skills from the database
-- Run this SQL script in your database management tool (phpMyAdmin, MySQL Workbench, etc.)

DELETE FROM skills;

-- Optional: Reset the auto-increment counter
-- ALTER TABLE skills AUTO_INCREMENT = 1;

-- Verify the skills table is empty
SELECT COUNT(*) as remaining_skills FROM skills;
