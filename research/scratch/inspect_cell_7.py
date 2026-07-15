import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("REPRODUCE_SYSTEM_METRICS.ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

cell = nb['cells'][7]
print("=== Cell 7 (code) ===")
print("".join(cell['source']))
print("====================")
