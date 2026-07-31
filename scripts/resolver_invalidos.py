import sys
import unicodedata
from openpyxl import load_workbook


def norm(v):
    v = " ".join(str(v or "").strip().upper().split())
    return "".join(c for c in unicodedata.normalize("NFD", v) if unicodedata.category(c) != "Mn")


alvos = {
    (norm("LUCIANA CARVALHO SOARES DA SILVA"), norm("FLOQUINHO")),
    (norm("MARA DENIZE RIBEIRO LEVI SILVA"), norm("THEO")),
    (norm("MARIA TEREZA BASTOS LOPES"), norm("BONITINHA")),
    (norm("NAGILA DE LIMA GOELDI"), norm("TORRADA")),
}
wb = load_workbook(sys.argv[1], data_only=True, read_only=True)
for nome_aba in ["JUNHO", "MAIO", "ABRIL", "MARÇO", "FEVEREIRO", "JANEIRO V2"]:
    ws = wb[nome_aba]
    print(f"[{nome_aba}]")
    for row in ws.iter_rows(min_row=2, values_only=True):
        cliente = norm(row[2] if len(row) > 2 else None)
        # Jan V2 pet=G; demais consolidados variam, tente G/H/I.
        for pet_idx in [6, 7, 8, 5]:
            pet = norm(row[pet_idx] if len(row) > pet_idx else None)
            if (cliente, pet) in alvos:
                print([row[i] if len(row) > i else None for i in range(min(10, len(row)))])
                break
