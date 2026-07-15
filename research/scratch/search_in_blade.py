import re
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

file_path = "d:\\KULIAH\\Semester 8\\MODEL_SVR\\web-app\\resources\\views\\operator\\optimasi\\index.blade.php"
with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
    content = f.read()

# Search for isGridRunning
print("=== isGridRunning occurrences ===")
for match in re.finditer(r'.{0,40}isGridRunning.{0,40}', content):
    print(match.group(0))

# Search for isGwoRunning
print("\n=== isGwoRunning occurrences ===")
for match in re.finditer(r'.{0,40}isGwoRunning.{0,40}', content):
    print(match.group(0))

# Search for the button trigger, like the AJAX request that starts the training
print("\n=== Form submission / AJAX start ===")
for match in re.finditer(r'.{0,40}btn-submit.{0,40}|.{0,40}id="btn-run-grid".{0,40}|.{0,40}Jalankan Grid Search.{0,40}', content):
    print(match.group(0))
