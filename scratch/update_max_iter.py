import json
import os

def update_ipynb(filename):
    print(f"Updating {filename}...")
    with open(filename, 'r', encoding='utf-8') as f:
        nb = json.load(f)
    
    modified = False
    for cell in nb.get('cells', []):
        if cell.get('cell_type') != 'code':
            continue
        
        # Merge source lines to do replacement
        source_str = "".join(cell.get('source', []))
        
        orig = source_str
        # Replace Grid Search SVR calls
        source_str = source_str.replace("SVR(kernel='rbf', cache_size=500, **params)", "SVR(kernel='rbf', cache_size=500, max_iter=10000, **params)")
        source_str = source_str.replace("SVR(kernel='rbf', cache_size=500, **best_params)", "SVR(kernel='rbf', cache_size=500, max_iter=10000, **best_params)")
        
        # Replace GWO SVR calls
        source_str = source_str.replace("cache_size= 2000\n        )", "cache_size= 2000,\n            max_iter  = 10000\n        )")
        source_str = source_str.replace("cache_size= 2000\n)", "cache_size= 2000,\n    max_iter  = 10000\n)")
        
        if source_str != orig:
            # Split back into lines keeping newlines
            cell['source'] = [line + '\n' for line in source_str.split('\n')]
            # Fix last line not having trailing newline if original didn't
            if cell['source'] and cell['source'][-1].endswith('\n\n'):
                cell['source'][-1] = cell['source'][-1][:-1]
            elif cell['source'] and not cell['source'][-1].endswith('\n') and orig.endswith('\n'):
                cell['source'][-1] += '\n'
            modified = True
            
    if modified:
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(nb, f, indent=1, ensure_ascii=False)
        print(" -> IPYNB updated successfully!")
    else:
        print(" -> No changes needed for IPYNB.")

def update_py(filename):
    print(f"Updating {filename}...")
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()
    
    orig = content
    content = content.replace("SVR(kernel='rbf', cache_size=500, **params)", "SVR(kernel='rbf', cache_size=500, max_iter=10000, **params)")
    content = content.replace("SVR(kernel='rbf', cache_size=500, **best_params)", "SVR(kernel='rbf', cache_size=500, max_iter=10000, **best_params)")
    content = content.replace("cache_size= 2000\n        )", "cache_size= 2000,\n            max_iter  = 10000\n        )")
    content = content.replace("cache_size= 2000\n)", "cache_size= 2000,\n    max_iter  = 10000\n)")
    
    if content != orig:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(content)
        print(" -> Python script updated successfully!")
    else:
        print(" -> No changes needed for Python script.")

if __name__ == '__main__':
    update_ipynb('research/DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb')
    update_py('research/demo_svr_grid_search_&_grey_wolf_optimizer (4).py')
    update_py('research/notebook_code.py')
