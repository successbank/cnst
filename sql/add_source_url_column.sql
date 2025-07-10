-- Add source_url column to board_news table
ALTER TABLE board_news ADD COLUMN IF NOT EXISTS source_url VARCHAR(500);

-- Update the admin_news.php to handle the new column
-- The column will be NULL for existing records