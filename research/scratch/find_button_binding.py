import re
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

file_path = "d:\\KULIAH\\Semester 8\\MODEL_SVR\\web-app\\resources\\views\\operator\\optimasi\\index.blade.php"
with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
    content = f.read()

# Search for the button Jalankan Grid Search
for match in re.finditer(r'.{0,100}Jalankan Grid Search.{0,200}', content):
    print(match.group(0))
