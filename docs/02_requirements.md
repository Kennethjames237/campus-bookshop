# 2 Analisi dei Requisiti

**Scopo del Progetto:**
Applicazione Web per la compravendita di libri universitari.
Non verrà implementato un sistema di pagamento.
Non verrà implementato un meccanismo di passowrd recovery.
La gestione per accordarsi sul prezzo, su un diverso pagamento e sulla consegna del libro, sono attualmente lasciate alla gestione tra utenti.

---

## 2.1 Requisiti Non Funzionali

* **RNF01:** Implementazione dell'architettura attraverso il pattern Model-View-Presenter (MVP).
* **RNF02:** Backend implementato in PHP puro.
* **RNF03:** Frontend implementato in HTML, CSS e Javascript.
* **RNF04:** Il frontend comunica con il backend solo tramite API RESTful CRUD.
* **RNF05:** Il Backend non deve generare codice HTML.
* **RNF06:** Le password non devono essere salvate in chiaro.

---

## 2.2 Requisiti Funzionali
* **RF01:** Login e Registrazione degli utenti.
* **RF02:** Visualizzazione lista libri.
* **RF03:** Caricamento annuncio.
* **RF04:** Acquisto diretto.
* **RF05:** Ricerca avanzata per ISBN, corso, docente.
* **RF06:** Messaggistica tra utenti.

---

## 2.3 Attori di Sistema Principali

* **Visitatore:** utente non autenticato, può:
    1. visualizzare tutti gli annunci.
    2. effettuare il login.
    3. effettuare la registrazione.

* **Utente:** utente autenticato, può:
    1. visualizzare annunci pubblicati da altri.
    2. aggiungere, modificare e rimuovere i propri annunci.
    3. visualizzare lo storico dei libri venduti.
    4. visualizzare lo storico degli acquisti.
    5. effettuare il logout.

## 2.4 User Stories

### [US01] Registrazione Utente
> **Come** Visitatore,
> **Voglio** registrarmi,
> **Affinché** il sistema mi permetta di accedere.
>
* **Analisi:** [UC01](features/uc01_registration.md)

### [US02] Login Utente
> **Come** Visitatore,
> **Voglio** effettuare il login,
> **Affinché** il sistema mi permetta di compiere operazioni.
>
* **Analisi:** [UC02](features/uc02_login/analysis.md)

### [US03] Visualizzazione
> **Come** Utente o Visitatore,
> **Voglio** vedere la lista dei libri (esclusi i miei),
> **Affinché** possa trovare testi da acquistare.

* **Analisi:** [UC03](features/uc03_listing/analysis.md)

### [US04] Pubblicazione Annuncio
> **Come** Utente,
> **Voglio** caricare un libro (Titolo, Prezzo, Foto, ISBN, Docente, Corso),
> **Affinché** sia visibile e acquistabile dagli altri utenti.

* **Analisi:** [UC04](features/uc04_adding/analysis.md)

### [US05] Acquisto Diretto
> **Come** Utente,
> **Voglio** poter acquistare un libro,
> **Affinché** il libro vada nello storico dei miei acquisiti, nello storico annunci del venditore e sparisca dalla bacheca.

* **Analisi:** [UC05](features/uc05_buy/analysis.md)

### [US06] Ricerca Avanzata
> **Come** Utente o Visitatore,
> **Voglio** poter cercare libri per ISBN, corso o docente,
> **Affinché** possa trovare rapidamente i testi di mio interesse.

* **Analisi:** [UC06](features/uc06_search/analysis.md)

### [US07] Messaggistica tra Utenti
> **Come** Utente,
> **Voglio** poter inviare e ricevere messaggi da altri utenti,
> **Affinché** possa accordarmi su prezzo e consegna dei libri.

* **Analisi:** [UC07](features/uc07_messaging/analysis.md)

---

## 2.5 Matrice di Tracciabilità

| | RF01 | RF02 | RF03 | RF04 | RF05 | RF06 |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| [UC01](features/uc01_registration/analysis.md) | X |   |   |   |   |   |
| [UC02](features/uc02_login/analysis.md)        | X |   |   |   |   |   |
| [UC03](features/uc03_listing/analysis.md)      |   | X |   |   |   |   |
| [UC04](features/uc04_adding/analysis.md)       |   |   | X |   |   |   |
| [UC05](features/uc05_buy/analysis.md)          |   |   |   | X |   |   |
| [UC06](features/uc06_search/analysis.md)       |   |   |   |   | X |   |
| [UC07](features/uc07_messaging/analysis.md)    |   |   |   |   |   | X |

---
