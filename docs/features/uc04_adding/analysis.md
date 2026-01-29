# UC04 - Pubblicazione Annuncio

## 1. Panoramica
**Descrizione:** Consente a un Utente Autenticato di inserire un nuovo annuncio di vendita per un libro, specificando dettagli accademici e caricando una fotografia.

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Utente Autenticato (Venditore) |
| **Pre-condizioni** | L'utente è loggato e si trova nel form di creazione annuncio. |
| **Post-condizioni** | L'annuncio è salvato nel DB e l'immagine è memorizzata sul server. |

![Use Case Diagram](img/uc04.drawio.svg)

---

## 2. Flussi di Eventi

### Flusso Principale
1. Il **Venditore** clicca su "Pubblica Annuncio".
2. Il sistema mostra il modulo di inserimento: Titolo, Autore, ISBN, Prezzo, Corso, Docente e Caricamento Foto.
3. Il **Venditore** compila i campi e seleziona un'immagine dal dispositivo.
4. Il sistema (Frontend) valida la presenza dei campi obbligatori e il formato numerico del prezzo.
5. Il sistema (Backend) riceve i dati, salva l'immagine nel filesystem e crea il record nel database.
6. Il sistema reindirizza l'utente alla propria area personale o alla bacheca.

### Flussi Alternativi

* **A1: Dati mancanti o Formato errato**
    1. Il sistema evidenzia i campi non validi (es. ISBN non numerico o prezzo negativo).
    2. Il pulsante di invio viene disabilitato finché i dati non sono corretti.

* **A2: Errore Caricamento Immagine**
    1. Il file caricato non è un'immagine o supera la dimensione massima.
    2. Il sistema mostra l'errore: *"Formato file non supportato o file troppo grande"*.

---

## 3. Activity Diagram

![Activity Diagram Pubblicazione Annuncio](img/uc04_flowchart.drawio.svg)

---

## 4. Criteri di Accettazione
* Il campo ISBN deve accettare solo numeri (10 o 13 cifre).
* Il prezzo deve essere obbligatoriamente un numero positivo.
* L'utente deve poter caricare una foto.
* L'annuncio deve essere collegato all'ID dell'utente che lo ha creato.

---

## 5. Piano di Test Manuale
| ID | Azione | Risultato Atteso | Valida |
| :--- | :--- | :--- | :--- |
| **T01** | Tentativo di invio con campi vuoti | Messaggio di errore "Campi obbligatori mancanti". | ✅ |
| **T02** | Inserimento prezzo negativo (es. -5) | Errore di validazione sul campo prezzo. | ✅ |
| **T03** | Caricamento file non immagine (es. .pdf) | Blocco del caricamento e avviso all'utente. | ✅ |
| **T04** | Invio corretto dei dati | Messaggio di successo e comparsa dell'annuncio in bacheca. | ✅ |

---

## 6. Design Tecnico

### 6.1 Sequence Diagram
![Sequence Diagram](img/uc04_sequence.drawio.svg)

### 6.2 Backend Flowchart
![Backend Flowchart](img/uc04_backend_flowchart.drawio.svg)

### 6.3 Flusso Upload Immagine
![Image Upload Flow](img/image_upload_flow.drawio.svg)
