-- Add display_order column to board_quote table
ALTER TABLE board_quote ADD COLUMN display_order INTEGER;

-- Set initial display_order values based on current ID order
UPDATE board_quote 
SET display_order = (
    SELECT COUNT(*) + 1
    FROM (SELECT * FROM board_quote) AS t2
    WHERE t2.id < board_quote.id
);

-- Create index for better performance
CREATE INDEX idx_board_quote_display_order ON board_quote(display_order);