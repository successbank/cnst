#!/usr/bin/env python3
import subprocess
import re
from collections import defaultdict

# Extract strings from Excel file
result = subprocess.run(['strings', '2/2.xls'], capture_output=True, text=True)
lines = result.stdout.split('\n')

# Find patterns that look like table data
# Pattern 1: H-beam specs (XXX*XXX*XX*XX)
h_beam_pattern = re.compile(r'^(\d{2,3})\*(\d{2,3})\*(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)$')

# Pattern 2: Numbers between 5 and 500 (likely weights)
weight_pattern = re.compile(r'^(\d+(?:\.\d+)?)$')

# Pattern 3: Korean/English headers
header_pattern = re.compile(r'(규격|단중|중량|단위중량|KG|kg|weight|Weight|WEIGHT)')

# Collect data
specs = []
numbers = []
headers = []
spec_index = {}

for i, line in enumerate(lines):
    line = line.strip()
    
    # Check for H-beam specs
    if h_beam_pattern.match(line):
        specs.append((i, line))
        spec_index[i] = line
    
    # Check for numbers
    if weight_pattern.match(line):
        try:
            num = float(line)
            if 5.0 <= num <= 500.0:  # Reasonable weight range
                numbers.append((i, num))
        except:
            pass
    
    # Check for headers
    if header_pattern.search(line):
        headers.append((i, line))

# Try to find patterns - weights usually follow specs
print("=== DATA STRUCTURE ANALYSIS ===")
print(f"Total H-beam specifications: {len(specs)}")
print(f"Total weight-like numbers: {len(numbers)}")
print(f"Total header-like strings: {len(headers)}")

# Look for weight values near specs
print("\n=== SPECIFICATION-WEIGHT PAIRS (within 5 lines) ===")
weight_map = {}
for spec_idx, spec in specs[:20]:  # First 20 specs
    print(f"\nSpec at line {spec_idx}: {spec}")
    nearby_numbers = [(idx, num) for idx, num in numbers if spec_idx < idx <= spec_idx + 5]
    if nearby_numbers:
        print(f"  Nearby numbers:")
        for num_idx, num in nearby_numbers[:3]:
            print(f"    Line {num_idx}: {num}")
            if spec not in weight_map:
                weight_map[spec] = num

# Print the likely spec-weight mapping
print("\n=== LIKELY SPEC-TO-WEIGHT MAPPING ===")
print("Specification → Unit Weight (kg/m)")
print("-" * 40)
for i, (spec, weight) in enumerate(sorted(weight_map.items())[:30]):
    print(f"{i+1:3d}. {spec:20s} → {weight:6.1f}")

# Look for sequential patterns
print("\n=== SEQUENTIAL DATA PATTERNS ===")
# Find sequences where specs and numbers alternate
for i in range(len(lines) - 10):
    if h_beam_pattern.match(lines[i].strip()):
        # Check if next few lines have numbers
        sequence = []
        for j in range(5):
            if i+j < len(lines):
                line = lines[i+j].strip()
                if h_beam_pattern.match(line):
                    sequence.append(f"SPEC: {line}")
                elif weight_pattern.match(line):
                    try:
                        num = float(line)
                        if 5.0 <= num <= 500.0:
                            sequence.append(f"WEIGHT: {num}")
                    except:
                        pass
        
        if len(sequence) >= 2 and "WEIGHT" in str(sequence):
            print(f"Pattern at line {i}:")
            for item in sequence[:3]:
                print(f"  {item}")
            break

# Extract data from the known examples
print("\n=== VERIFICATION WITH KNOWN EXAMPLES ===")
# From 2.txt: 100*100*6*8 = 17.2 kg/m
# From 2.txt: 125*125*6*8 = 23.8 kg/m
print("From 2.txt documentation:")
print("- 100*100*6*8 → 17.2 kg/m")
print("- 125*125*6*8 → 23.8 kg/m (Note: This spec might be 125*125*6.5*9 in the table)")

# Check if we found these
if "100*100*6*8" in weight_map:
    print(f"\nFound 100*100*6*8 with weight: {weight_map['100*100*6*8']}")
else:
    print("\n100*100*6*8 found in specs but weight mapping unclear")

print("\n=== SHEET STRUCTURE HYPOTHESIS ===")
print("Based on the analysis, the Excel file likely contains:")
print("1. Column A: H-beam specifications (Height*Width*Web*Flange)")
print("2. Column B or nearby: Unit weights in kg/m")
print("3. The data appears to be a lookup table for steel H-beam products")
print("4. Specifications range from small (100*100) to large (800+ mm) beams")