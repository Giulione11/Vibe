
import pandas as pd
import json

# === Configurazione ===
csv1_path = 'music.csv'
csv2_path = 'spotifytracksgenre.csv'
merge_keys = ['artists']  # Chiavi accettate per il merge

# === Carica i CSV
df1 = pd.read_csv(csv1_path)
df2 = pd.read_csv(csv2_path)

# === Trova chiave comune tra i due file
def find_common_key(df1, df2, keys):
    for key in keys:
        if key in df1.columns and key in df2.columns:
            return key
    raise ValueError("Nessuna chiave comune trovata tra: " + ", ".join(keys))

merge_key = find_common_key(df1, df2, merge_keys)
print(f"🔑 Chiave usata per il merge: {merge_key}")

# === Simula dati MongoDB: trasformazione in dizionari
mongo_data1 = df1.to_dict('records')
mongo_data2 = df2.to_dict('records')

# === Riconverti in DataFrame per il merge
df_mongo1 = pd.DataFrame(mongo_data1)
df_mongo2 = pd.DataFrame(mongo_data2)

# === Merge dei dati sul campo trovato
merged_df = pd.merge(df_mongo1, df_mongo2, on=merge_key, how='inner')

# === Rimuove duplicati perfettamente identici (su tutte le colonne)
merged_df = merged_df.drop_duplicates()

# === Trasforma il risultato finale in lista di "documenti Mongo"
merged_documents = merged_df.to_dict('records')

# === Salva tutto in JSON
output_path = "merged_output.json"
with open(output_path, "w", encoding='utf-8') as f:
    json.dump(merged_documents, f, indent=2, ensure_ascii=False)

print(f"\n✅ Merge completato! Salvato in '{output_path}' con {len(merged_documents)} documenti (senza duplicati totali).")
