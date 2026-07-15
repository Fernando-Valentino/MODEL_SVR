import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open("REPRODUCE_SYSTEM_METRICS.ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

# Find cells and check if they have outputs
for idx, cell in enumerate(nb['cells']):
    cell_type = cell['cell_type']
    if cell_type == "code" and 'outputs' in cell and len(cell['outputs']) > 0:
        # Check if output contains text
        for out in cell['outputs']:
            if out.get('output_type') == 'stream' and 'text' in out:
                text_content = "".join(out['text'])
                if "Grid Search" in text_content or "GWO" in text_content or "Rank" in text_content or "Iterasi" in text_content:
                    print(f"=== Found output in Cell {idx} ===")
                    print(text_content[:2000]) # print first 2000 chars of output
                    print("==================================")
