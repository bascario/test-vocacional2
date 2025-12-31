import pandas as pd
import re

# Cargar el archivo Excel
df = pd.read_excel("Data OVP 1.1FILTRADI (1).xlsx", header=0)

# Renombrar columnas si es necesario
df.columns = ['zona', 'distrito', 'provincia', 'canton', 'parroquia', 'nombre', 'codigo']

# Limpiar distrito
df['distrito'] = df['distrito'].apply(lambda x: None if pd.isna(x) or "NO hay registro" in str(x) else x)

# Función para clasificar tipo
def clasificar_tipo(nombre):
    nombre = nombre.upper()
    if 'FISCOMISIONAL' in nombre or 'FISCOMICIONAL' in nombre or 'FISCOMISONAL' in nombre or 'PCEI' in nombre or 'MARISTA' in nombre or 'FE Y ALEGRIA' in nombre or 'DON BOSCO' in nombre or 'SAGRADO CORAZON' in nombre or 'SAN JOSE' in nombre or 'JUAN XXIII' in nombre:
        return 'Fiscomisional'
    elif 'PARTICULAR' in nombre:
        return 'Particular'
    else:
        return 'Fiscal'

df['tipo'] = df['nombre'].apply(clasificar_tipo)

# Generar INSERTs
inserts = []
for _, row in df.iterrows():
    nombre = str(row['nombre']).replace("'", "''")
    provincia = str(row['provincia']).replace("'", "''") if pd.notna(row['provincia']) else ''
    canton = str(row['canton']).replace("'", "''") if pd.notna(row['canton']) else ''
    zona = str(row['zona']).replace("'", "''") if pd.notna(row['zona']) else ''
    distrito = f"'{row['distrito']}'" if pd.notna(row['distrito']) else 'NULL'
    codigo = str(row['codigo']).replace("'", "''")
    tipo = row['tipo']
    
    provincia = f"'{provincia}'" if provincia != 'nan' else 'NULL'
    canton = f"'{canton}'" if canton != 'nan' else 'NULL'
    zona = f"'{zona}'" if zona != 'nan' else 'NULL'

    inserts.append(
        f"('{nombre}', {provincia}, {canton}, {zona}, {distrito}, '{codigo}', '{tipo}')"
    )

# Guardar en archivo
with open("instituciones_inserts.sql", "w", encoding="utf-8") as f:
    f.write("INSERT INTO `instituciones_educativas` (`nombre`, `provincia`, `canton`, `zona`, `distrito`, `codigo`, `tipo`) VALUES\n")
    f.write(",\n".join(inserts) + ";\n")