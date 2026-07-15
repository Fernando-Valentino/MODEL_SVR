import json

with open("DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb", "r", encoding="utf-8") as f:
    nb = json.load(f)

# Find cell that has 'import openpyxl' or write to Excel
target_cell = None
for idx, cell in enumerate(nb['cells']):
    source_text = "".join(cell['source'])
    if "import openpyxl" in source_text or "OUT_PATH =" in source_text:
        target_cell = cell
        print(f"Found Excel exporter cell at index {idx}")
        break

if target_cell:
    with open("scratch/cell_29_code.txt", "w", encoding="utf-8") as f_out:
        f_out.write("".join(target_cell['source']))
    print("Successfully saved cell 29 code to scratch/cell_29_code.txt")
else:
    print("Excel exporter cell not found")
