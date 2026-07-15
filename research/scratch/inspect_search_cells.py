import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

for idx in [14, 16]:
    cell = nb['cells'][idx]
    print(f"=== Cell {idx} ({cell['cell_type']}) ===")
    print("".join(cell['source'][:100]))
    print("====================")
