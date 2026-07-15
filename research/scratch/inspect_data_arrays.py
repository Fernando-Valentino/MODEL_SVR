import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

print(f"Total cells: {len(nb['cells'])}")
for idx, cell in enumerate(nb['cells']):
    cell_type = cell['cell_type']
    source_lines = cell['source']
    source_preview = "".join(source_lines).strip()
    if "param_grid" in source_preview or "wolves_score" in source_preview or "import openpyxl" in source_preview:
        print(f"=== Cell {idx} ({cell_type}) ===")
        print("Source:")
        print("".join(source_lines[:20])) # show first 20 lines
        print("====================")
