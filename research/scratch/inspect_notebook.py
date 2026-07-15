import json
import sys

# Reconfigure stdout to use UTF-8
if sys.platform.startswith('win'):
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("REPRODUCE_SYSTEM_METRICS.ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

print(f"Total cells: {len(nb['cells'])}")
for idx, cell in enumerate(nb['cells']):
    cell_type = cell['cell_type']
    source_lines = cell['source']
    source_preview = "".join(source_lines[:2]).strip().replace("\n", " ")
    print(f"Cell {idx} ({cell_type}): {source_preview[:80]}")
