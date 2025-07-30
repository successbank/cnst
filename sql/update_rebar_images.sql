-- Update all rebar products to use 철근.jpg as main image
UPDATE products 
SET main_image = '/img/철근.jpg'
WHERE category_code = 'rebar' 
AND (main_image IS NULL OR main_image = '');

-- Check updated records
SELECT id, product_name, main_image 
FROM products 
WHERE category_code = 'rebar'
ORDER BY id;