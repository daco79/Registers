import json
import pandas as pd

with open('75017/parcelles_75017.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

df = pd.DataFrame(data['resultats'])
df.to_csv('parcelles_75017_sans_proprietaires.csv', index=False)