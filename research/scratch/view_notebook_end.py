import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("REPRODUCE_SYSTEM_METRICS.ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

print(f"Total cells: {len(nb['cells'])}")
for idx in range(max(0, len(nb['cells']) - 3), len(nb['cells'])):
    cell = nb['cells'][idx]
    print(f"=== Cell {idx} ({cell['cell_type']}) ===")
    print("".join(cell['source'][:40]))
    print("====================")
