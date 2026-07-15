import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("d:\\KULIAH\\Semester 8\\MODEL_SVR\\ml-engine\\research\\scratch\\cell_29_code.txt", "r", encoding="utf-8") as f:
    code = f.read()

# Let's search for some keywords and print sections of code
import re

# Find sheet 6 writing logic
pos = code.find('ws6 =')
if pos != -1:
    print("=== Sheet 6 logic ===")
    print(code[pos:pos+1500])

# Find sheet 7 writing logic
pos = code.find('ws7 =')
if pos != -1:
    print("=== Sheet 7 logic ===")
    print(code[pos:pos+1500])
