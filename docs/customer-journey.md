# Kino: kasutaja teekond (Customer Journey)

Tallinna Polütehnikum, rühm K-TA-24B, 2026.
Koostajad: Jaromir Kuras ja Maksim Kalinski.

## 1. Sissejuhatus

Kasutaja teekond näitab, mida külastaja meie saidil samm-sammult teeb: alates sellest, kui tal tekib mõte kinno minna, kuni selleni, kui ta piletiga kinno jõuab.

Me kirjutasime teekonna lahti selleks, et näha, millised funktsioonid on igal sammul vaja. Iga etapi juures on kirjas ka see, mis võib valesti minna ja kuidas me selle ära lahendame. Funktsioonide numbrid (KIN-1 kuni KIN-16) on meie Jira tahvlilt.

## 2. Persoonad

Tegime kaks kujuteldavat kasutajat, kelle peale me teekonda tehes mõtleme.

| Persoona | Kirjeldus |
|---|---|
| Mari, 17 | Õpilane. Käib kinos sõpradega, ostab pileti telefonist. Tahab kiiresti näha, mis täna mängib, ja istuda sõbra kõrval. |
| Andres, 35 | Käib kinos perega paar korda kuus. Planeerib ette, ostab korraga 4 piletit ja tahab kindlust, et makse õnnestus. |

## 3. Teekonna ülevaade

Tabelis on kogu teekond lühidalt. Iga etappi on pikemalt kirjeldatud 4. peatükis.

| Nr | Etapp | Tunne | Funktsioonid |
|---|---|---|---|
| 1 | Soov kinno minna | Huvi | KIN-1 Filmide kava, KIN-6 Kino valik, KIN-10 Keele valik |
| 2 | Filmi otsimine ja valimine | Huvi ja natuke kahtlust | KIN-2 Otsing, KIN-3 Filmi leht, KIN-4 Filtrid, KIN-9 Treiler |
| 3 | Seansi valimine | Ootusärevus | KIN-5 Seansside kava, KIN-6 Kino valik |
| 4 | Kohtade valimine | Keskendumine, natuke kiirustamist | KIN-7 Kohtade valik |
| 5 | Maksmine | Pinge, pärast kergendus | KIN-8 Pileti ostmine, KIN-14 Piletihinnad |
| 6 | Pärast ostu ja kinno minek | Rahulolu | KIN-8 (kinnitus, QR-kood, e-kiri) |
| 7 | Probleem või muudatus | Ärevus | KIN-15 Broneeringute haldus |

Kõige tähtsamad on etapid 3, 4 ja 5, sest just seal ostab külastaja pileti. Kui seal midagi ei tööta, ei saa kino raha. Seepärast teeme need funktsioonid kõige esimesena.

## 4. Etappide kirjeldus

### 4.1 Etapp 1: Soov kinno minna

**Mida külastaja teeb:** Avab saidi ja vaatab, mis filmid praegu kinos on.  
**Mida ta mõtleb:** "Mis filmid praegu jooksevad?"  
**Tunne:** Huvi  
**Meie funktsioonid:** KIN-1 Filmide kava, KIN-6 Kino valik, KIN-10 Keele valik  
**Mis võib valesti minna:** Nimekiri on pikk ja segane, ei saa aru, mis on praegu ja mis tuleb alles hiljem.  
**Kuidas me lahendame:** Filmid on kaartidena koos plakatiga. Eraldi on osa "Varsti kinos".  

### 4.2 Etapp 2: Filmi otsimine ja valimine

**Mida külastaja teeb:** Otsib filmi nime järgi või filtreerib žanri järgi, avab filmi lehe ja vaatab treilerit.  
**Mida ta mõtleb:** "Kas see film on hea? Kas see sobib mulle?"  
**Tunne:** Huvi ja natuke kahtlust  
**Meie funktsioonid:** KIN-2 Otsing, KIN-3 Filmi leht, KIN-4 Filtrid, KIN-9 Treiler  
**Mis võib valesti minna:** Ei mäleta filmi täpset nime ja ei leia seda üles.  
**Kuidas me lahendame:** Otsing töötab ka poolelt sõnalt ja ei arvesta suuri tähti.  

### 4.3 Etapp 3: Seansi valimine

**Mida külastaja teeb:** Vaatab kava ja valib päeva, kellaaja ja saali.  
**Mida ta mõtleb:** "Mis kell ma jõuan ja kus see kino on?"  
**Tunne:** Ootusärevus  
**Meie funktsioonid:** KIN-5 Seansside kava, KIN-6 Kino valik  
**Mis võib valesti minna:** Valib kogemata seansi, mis on juba alanud.  
**Kuidas me lahendame:** Möödunud seansse ei näidata. 15 minutit enne algust ei saa enam piletit osta.  

### 4.4 Etapp 4: Kohtade valimine

**Mida külastaja teeb:** Valib saali plaanilt vaba koha või mitu kohta.  
**Mida ta mõtleb:** "Tahan istuda keskel ja sõbra kõrval."  
**Tunne:** Keskendumine, natuke kiirustamist  
**Meie funktsioonid:** KIN-7 Kohtade valik  
**Mis võib valesti minna:** Keegi teine ostab sama koha samal ajal ära.  
**Kuidas me lahendame:** Valitud kohad pannakse 10 minutiks kinni, kuni ost käib.  

### 4.5 Etapp 5: Maksmine

**Mida külastaja teeb:** Valib pileti tüübi, sisestab nime ja e-posti ning maksab.  
**Mida ta mõtleb:** "Kas see kindlasti õnnestub?"  
**Tunne:** Pinge, pärast kergendus  
**Meie funktsioonid:** KIN-8 Pileti ostmine, KIN-14 Piletihinnad  
**Mis võib valesti minna:** Makse ebaõnnestub ja külastaja ei tea, kas pilet on olemas.  
**Kuidas me lahendame:** Kui makse ei õnnestu, tellimust ei looda ja kohad vabanevad kohe.  

### 4.6 Etapp 6: Pärast ostu ja kinno minek

**Mida külastaja teeb:** Saab e-postiga pileti QR-koodiga ja näitab seda kinos.  
**Mida ta mõtleb:** "Kus mu pilet on?"  
**Tunne:** Rahulolu  
**Meie funktsioonid:** KIN-8 (kinnitus, QR-kood, e-kiri)  
**Mis võib valesti minna:** E-kiri ei jõua kohale või kustub kogemata ära.  
**Kuidas me lahendame:** Kinnituse leht jääb alles ja administraator saab pileti uuesti saata.  

### 4.7 Etapp 7: Probleem või muudatus

**Mida külastaja teeb:** Kirjutab kinole, kui midagi läks valesti või plaanid muutusid.  
**Mida ta mõtleb:** "Kas ma saan selle tühistada?"  
**Tunne:** Ärevus  
**Meie funktsioonid:** KIN-15 Broneeringute haldus  
**Mis võib valesti minna:** Administraator ei leia tellimust üles.  
**Kuidas me lahendame:** Tellimusi saab otsida broneeringu numbri või e-posti järgi.  

## 5. Administraatori töö taustal

Külastaja administraatorit ei näe, aga ilma tema tööta teekond ei toimi. Tabelis on kirjas, milline halduse funktsioon millist etappi toetab.

| Halduse funktsioon | Toetab etappi | Miks see vajalik on |
|---|---|---|
| KIN-11 Filmide haldus | 1 ja 2 | Et kavas oleksid õiged filmid koos plakati ja kirjeldusega |
| KIN-12 Seansside haldus | 3 | Et kavas oleksid õiged kellaajad ja saalid |
| KIN-13 Kinode ja saalide haldus | 4 | Et saali plaan oleks päriselt samasugune nagu kinos |
| KIN-14 Piletihindade haldus | 5 | Et hind oleks õige ja arvutataks automaatselt |
| KIN-15 Broneeringute haldus | 6 ja 7 | Et saaks külastajat aidata ja tellimust tühistada |
| KIN-16 Statistika | pärast teekonda | Et näha, millised filmid ja seansid on populaarsed |

## 6. Kokkuvõte

Teekond koosneb seitsmest etapist. Kõige rohkem probleeme võib tekkida kohtade valimisel ja maksmisel, sest seal tegutsevad korraga mitu inimest ja seal liigub raha.

Sellest teekonnast tegime endale järgmised otsused:

- Kõigepealt teeme etapid 1, 3, 4 ja 5 — see on kõige lühem tee pileti ostmiseni.
- Kohad pannakse ostmise ajaks kinni, et kaks inimest ei saaks sama kohta.
- Iga sammu järel näeb külastaja selgelt, mis edasi juhtub (kinnitus, hind, kohtade arv).
- Otsing, filtrid ja treiler teeme hiljem, sest ilma nendeta saab piletit ikka osta.
