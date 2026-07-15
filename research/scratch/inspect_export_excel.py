import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

for cell_idx in [27, 28, 29]:
    if cell_idx < len(nb['cells']):
        cell = nb['cells'][cell_idx]
        print(f"=== Cell {cell_idx} ({cell['cell_type']}) ===")
        print("Source:")
        print("".join(cell['source']))
        print("====================")
