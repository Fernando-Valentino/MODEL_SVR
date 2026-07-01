import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open('research/DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb', 'r', encoding='utf-8') as f:
    nb = json.load(f)

for i, cell in enumerate(nb.get('cells', [])):
    source = "".join(cell.get('source', []))
    if 'split' in source.lower():
        print(f"Cell {i} contains split:")
        for line in source.split('\n'):
            if 'split' in line.lower() or 'train =' in line.lower() or 'test =' in line.lower():
                print("  ", line.strip())
        print("-" * 50)
