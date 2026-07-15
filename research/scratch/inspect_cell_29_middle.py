import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("d:\\KULIAH\\Semester 8\\MODEL_SVR\\ml-engine\\research\\scratch\\cell_29_code.txt", "r", encoding="utf-8") as f:
    lines = f.readlines()

for line_idx in range(435, min(600, len(lines))):
    print(f"{line_idx+1}: {lines[line_idx]}", end="")
