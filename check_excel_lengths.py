import pandas as pd
import warnings
warnings.filterwarnings('ignore')

# Read Excel file
df = pd.read_excel('114/철근.xlsx')

# Get all lengths from the first column
all_lengths = []
for idx in range(1, len(df)):
    length = df.iloc[idx]['길이']
    if pd.notna(length) and 'RED FONT' not in str(length):
        all_lengths.append(float(length))

# Remove duplicates and sort
unique_lengths = sorted(list(set(all_lengths)))
print(f"Total unique lengths in Excel: {len(unique_lengths)}")
print(f"Length range: {min(unique_lengths)}m to {max(unique_lengths)}m")
print(f"\nFirst 10 lengths: {unique_lengths[:10]}")
print(f"Last 10 lengths: {unique_lengths[-10:]}")

# Check D38 data specifically
d38_col = 'D38 / 8.95'
d38_pieces_col = 'Unnamed: 29'
d38_total_col = 'Unnamed: 30'

print("\n\nD38 Data Analysis:")
d38_with_data = 0
d38_without_data = 0

for idx in range(1, len(df)):
    length = df.iloc[idx]['길이']
    if pd.notna(length) and 'RED FONT' not in str(length):
        pieces = df.iloc[idx][d38_pieces_col]
        total = df.iloc[idx][d38_total_col]
        
        if pd.notna(pieces) and pd.notna(total):
            d38_with_data += 1
            if d38_with_data <= 5:  # Show first 5 with data
                print(f"  {float(length)}m: {pieces} pieces, {total}kg")
        else:
            d38_without_data += 1

print(f"\nD38 Summary:")
print(f"  Lengths with data: {d38_with_data}")
print(f"  Lengths without data: {d38_without_data}")
print(f"  Total lengths: {d38_with_data + d38_without_data}")