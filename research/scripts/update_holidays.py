import json
import re
import subprocess
import os

def fetch_holidays_from_db():
    cmd = [
        "docker", "exec", "mysql_db", 
        "mysql", "-u", "svr_user", "-puserpassword", "-N", "-B", 
        "-e", "SELECT tanggal, keterangan FROM svr_parkir.hari_liburs WHERE tipe='Libur Nasional' ORDER BY tanggal;"
    ]
    result = subprocess.run(cmd, capture_output=True)
    if result.returncode != 0:
        print("Error fetching from DB:", result.stderr.decode('utf-8', errors='ignore') if result.stderr else "")
        return None
    
    stdout_str = result.stdout.decode('utf-8', errors='ignore')
    holidays = []
    for line in stdout_str.strip().split('\n'):
        if not line:
            continue
        parts = line.split('\t')
        if len(parts) >= 2:
            date_str = parts[0].strip()
            desc = parts[1].strip()
            # Clean special characters like mock/unwanted characters
            desc = desc.replace("\x92", "'").replace("\ufffd", "'").replace("Miraj", "Mi'raj").replace("Mi\x92raj", "Mi'raj")
            desc = re.sub(r'Mi[^a-zA-Z0-9]{1,3}raj', "Mi'raj", desc)
            holidays.append((date_str, desc))
    return holidays

def main():
    holidays = fetch_holidays_from_db()
    if not holidays:
        print("Failed to fetch holidays.")
        return
    
    print(f"Fetched {len(holidays)} national holidays from database.")
    
    # 1. Format dates list representation
    dates_only = [h[0] for h in holidays]
    
    # Format nicely with indentation: 4 dates per line
    dates_lines = []
    for i in range(0, len(dates_only), 5):
        chunk = dates_only[i:i+5]
        chunk_str = ", ".join(f"'{d}'" for d in chunk)
        dates_lines.append(f"    {chunk_str}")
    dates_block = ",\n".join(dates_lines)
    
    new_libur_nasional_id_str = f"libur_nasional_id = pd.to_datetime([\n{dates_block}\n])"
    new_libur_nasional_id_const = f"LIBUR_NASIONAL_ID = [\n{dates_block}\n]"
    
    # 2. Format names dict representation
    dict_lines = []
    for d, desc in holidays:
        desc_escaped = desc.replace('"', '\\"')
        dict_lines.append(f'    "{d}": "{desc_escaped}"')
    dict_block = ",\n".join(dict_lines)
    new_libur_names_str = f"LIBUR_NAMES = {{\n{dict_block}\n}}"

    # --- Target Files ---
    base_dir = os.path.dirname(os.path.abspath(__file__))
    research_dir = base_dir
    ml_engine_dir = os.path.dirname(research_dir)
    
    file_py = os.path.join(research_dir, "demo_svr_grid_search_&_grey_wolf_optimizer (4).py")
    file_nb_code = os.path.join(research_dir, "notebook_code.py")
    file_ipynb = os.path.join(research_dir, "DEMO_SVR_GRID_SEARCH_&_GREY_WOLF_OPTIMIZER (20).ipynb")
    file_const = os.path.join(ml_engine_dir, "app", "core", "constants.py")
    
    # Update python files
    for filepath in [file_py, file_nb_code]:
        if os.path.exists(filepath):
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Replace libur_nasional_id = pd.to_datetime(...)
            content = re.sub(
                r'libur_nasional_id\s*=\s*pd\.to_datetime\(\[[\s\S]*?\]\)',
                new_libur_nasional_id_str,
                content
            )
            
            # Replace LIBUR_NAMES = {...}
            content = re.sub(
                r'LIBUR_NAMES\s*=\s*\{[\s\S]*?\}',
                new_libur_names_str,
                content
            )
            
            # Remove google colab import and mount
            content = re.sub(
                r'from google\.colab import drive\n+drive\.mount\(\s*[\'"]/content/drive[\'"]\s*,\s*force_remount\s*=\s*True\s*\)\n*',
                '',
                content
            )
            
            # Clean up paths
            content = content.replace(
                "'/content/drive/MyDrive/SKRIPSI/DATA_PARKIR/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'",
                "'DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'"
            ).replace(
                '"/content/drive/MyDrive/SKRIPSI/MODEL_CHECKPOINT"',
                '"model_checkpoint"'
            ).replace(
                '"/content/drive/MyDrive/SKRIPSI/EXCEL_MODEL"',
                '"excel_model"'
            ).replace(
                '"/content/drive/MyDrive/SKRIPSI/EXCEL_MODEL/SVR_Dishub_Cirebon_Final_v2.xlsx"',
                '"excel_model/SVR_Dishub_Cirebon_Final_v2.xlsx"'
            )
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated: {os.path.basename(filepath)}")
            
    # Update constants.py
    if os.path.exists(file_const):
        with open(file_const, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace LIBUR_NASIONAL_ID = [...]
        content = re.sub(
            r'LIBUR_NASIONAL_ID\s*=\s*\[[\s\S]*?\]',
            new_libur_nasional_id_const,
            content
        )
        
        with open(file_const, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated: constants.py")
        
    # Update Jupyter Notebook (.ipynb)
    if os.path.exists(file_ipynb):
        with open(file_ipynb, 'r', encoding='utf-8') as f:
            nb = json.load(f)
            
        for cell in nb.get('cells', []):
            if cell.get('cell_type') == 'code':
                source_str = "".join(cell.get('source', []))
                
                # Replace libur_nasional_id = pd.to_datetime(...)
                source_str = re.sub(
                    r'libur_nasional_id\s*=\s*pd\.to_datetime\(\[[\s\S]*?\]\)',
                    new_libur_nasional_id_str,
                    source_str
                )
                
                # Replace LIBUR_NAMES = {...}
                source_str = re.sub(
                    r'LIBUR_NAMES\s*=\s*\{[\s\S]*?\}',
                    new_libur_names_str,
                    source_str
                )
                
                # Remove google colab import and mount
                source_str = re.sub(
                    r'from google\.colab import drive\n+drive\.mount\(\s*[\'"]/content/drive[\'"]\s*,\s*force_remount\s*=\s*True\s*\)\n*',
                    '',
                    source_str
                )
                
                # Clean up paths
                source_str = source_str.replace(
                    "'/content/drive/MyDrive/SKRIPSI/DATA_PARKIR/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'",
                    "'DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'"
                ).replace(
                    '"/content/drive/MyDrive/SKRIPSI/MODEL_CHECKPOINT"',
                    '"model_checkpoint"'
                ).replace(
                    '"/content/drive/MyDrive/SKRIPSI/EXCEL_MODEL"',
                    '"excel_model"'
                ).replace(
                    '"/content/drive/MyDrive/SKRIPSI/EXCEL_MODEL/SVR_Dishub_Cirebon_Final_v2.xlsx"',
                    '"excel_model/SVR_Dishub_Cirebon_Final_v2.xlsx"'
                )
                
                # Reconvert to lines list with newlines preserved
                lines = []
                for line in source_str.split('\n'):
                    lines.append(line + '\n')
                if lines and lines[-1] == '\n':
                    lines.pop()
                cell['source'] = lines
                
        with open(file_ipynb, 'w', encoding='utf-8') as f:
            json.dump(nb, f, indent=2, ensure_ascii=False)
        print(f"Updated: {os.path.basename(file_ipynb)}")

if __name__ == "__main__":
    main()
