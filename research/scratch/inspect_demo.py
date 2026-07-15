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
    source_preview = "".join(source_lines[:2]).strip().replace("\n", " ")
    if cell_type == "markdown":
        print(f"Cell {idx} ({cell_type}): {source_preview[:80]}")
    elif "GridSearch" in source_preview or "Grid Search" in source_preview or "GWO" in source_preview or "gwo" in source_preview:
        print(f"Cell {idx} ({cell_type}): {source_preview[:80]}")
