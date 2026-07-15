import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

# Search cells for "Avg RMSE CV" or GWO convergence table data
for idx, cell in enumerate(nb['cells']):
    source_text = "".join(cell['source'])
    if "Avg RMSE CV" in source_text or "Log Konvergensi" in source_text or "gwo_log" in source_text or "grid_results" in source_text:
        print(f"=== Cell {idx} ({cell['cell_type']}) ===")
        print("Source:")
        print("".join(cell['source'][:40])) # first 40 lines
        print("====================")
