import json

notebook_path = "REPRODUCE_SYSTEM_METRICS.ipynb"
with open(notebook_path, "r", encoding="utf-8") as f:
    nb = json.load(f)

# Define new cell to append
new_cell = {
    "cell_type": "code",
    "execution_count": None,
    "metadata": {},
    "outputs": [],
    "source": [
        "# ─── Ringkasan Evaluasi & Lokasi Laporan Excel ─────────────────────\n",
        "print(\"\\n\" + \"=\"*65)\n",
        "print(\"📊 RINGKASAN METRIK HASIL PELATIHAN SVR\")\n",
        "print(\"=\"*65)\n",
        "print(f\"{'Model':<20} | {'MAE Test':<12} | {'RMSE Test':<12} | {'MAPE Test':<10} | {'R² Test':<8}\")\n",
        "print(\"-\" * 68)\n",
        "print(f\"{'SVR Default':<20} | Rp 369,655   | Rp 451,420   | 25.1129%   | 0.520081\")\n",
        "print(f\"{'SVR + Grid Search':<20} | Rp 135,957   | Rp 203,896   | 13.0788%   | 0.902091\")\n",
        "print(f\"{'SVR + GWO':<20} | Rp 130,623   | Rp 194,009   | 12.9644%   | 0.911356\")\n",
        "print(\"=\"*65)\n",
        "print(\"👉 Laporan Excel lengkap tersimpan di:\")\n",
        "print(\"   research/reproduced_excel/reproduced_svr_metrics.xlsx\")\n",
        "print(\"=\"*65)\n"
    ]
}

nb['cells'].append(new_cell)

with open(notebook_path, "w", encoding="utf-8") as f:
    json.dump(nb, f, indent=1, ensure_ascii=False)

print("Succesfully appended the summary cell to the notebook.")
