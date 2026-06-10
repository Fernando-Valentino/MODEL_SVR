import json

filename = "DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb"
with open(filename, 'r', encoding='utf-8') as f:
    nb = json.load(f)

code_cells = [c['source'] for c in nb['cells'] if c['cell_type'] == 'code']

with open('notebook_code.py', 'w', encoding='utf-8') as f:
    for cell in code_cells:
        f.write(''.join(cell))
        f.write('\n# ==========================================\n')
