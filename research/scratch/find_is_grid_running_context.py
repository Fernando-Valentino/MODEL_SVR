import re
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

file_path = "d:\\KULIAH\\Semester 8\\MODEL_SVR\\web-app\\resources\\views\\operator\\optimasi\\index.blade.php"
with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if "isGridRunning = true;" in line:
        print(f"=== Found isGridRunning = true; at line {i+1} ===")
        for j in range(max(0, i-5), min(len(lines), i+80)):
            print(f"{j+1}: {lines[j]}", end="")
        print("\n============================================\n")
