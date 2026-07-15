import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("REPRODUCE_SYSTEM_METRICS.ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

for cell_idx in [8, 9]:
    cell = nb['cells'][cell_idx]
    print(f"=== Cell {cell_idx} ({cell['cell_type']}) ===")
    print("Source:")
    print("".join(cell['source']))
    print("====================")
