import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

cell = nb['cells'][16]
print("=== Cell 16 Code lines 80-250 ===")
print("".join(cell['source'][80:250]))
print("====================")
