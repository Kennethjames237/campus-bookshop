# UC03 - Visualizzazione Lista Libri

## 1. Panoramica
**Descrizione:** Consente a un Visitatore o a un Utente Autenticato di consultare la bacheca degli annunci disponibili. Se l'utente è loggato, il sistema nasconde automaticamente i suoi annunci.

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Visitatore, Utente Autenticato, Database Annunci |
| **Pre-condizioni** | L'utente accede alla pagina "Bacheca" o "Home". |
| **Post-condizioni** | Il sistema mostra i libri filtrati disponibili per l'acquisto. |

![Use Case Diagram](img/uc03.drawio.svg)

---

## 2. Flussi di Eventi

### Flusso Principale
1. L'**Utente/Visitatore** accede alla bacheca dei libri.
2. Il sistema (Presenter) richiede la lista degli annunci al Model (API).
3. Il sistema (Backend) recupera i libri dal database.
4. **Filtro Identità:** Se l'utente è autenticato, il sistema esclude dalla lista gli annunci creati dall'utente stesso.
5. Il sistema mostra la lista ordinata (es. dal più recente) con: Titolo, Prezzo, Foto e Autore.
6. L'**Utente/Visitatore** visualizza i risultati.

### Flussi Alternativi

* **A1: Nessun libro disponibile**
    1. Il database non restituisce risultati (vuoto o tutti appartengono all'utente loggato).
    2. Il sistema mostra un messaggio: *"Al momento non ci sono libri disponibili. Torna più tardi!"*.

* **A2: Errore caricamento immagini**
    1. Se la foto di un annuncio non è reperibile, il sistema mostra un'immagine segnaposto (placeholder) di default.

---

## 3. Activity Diagram

![Activity Diagram Visualizzazione](img/uc03_flowchart.drawio.svg)

---

## 4. Criteri di Accettazione
* La lista deve essere caricata in modo asincrono senza ricaricare la pagina.
* L'utente non deve visualizzare i propri annunci nella lista 
* Ogni annuncio deve mostrare chiaramente il prezzo.

---

## 5. Piano di Test Manuale
| ID | Azione | Risultato Atteso | Valida |
| :--- | :--- | :--- | :--- |
| **T01** | Accesso da Visitatore | Visualizzazione di tutti i libri nel DB. | ✅ |
| **T02** | Accesso da Utente Loggato | Visualizzazione dei libri degli altri, i propri sono nascosti. | ✅ |
| **T03** | Database vuoto | Visualizzazione del messaggio "Nessun libro disponibile". | ✅ |

---

## 6. Design Tecnico

### 6.1 Sequence Diagram
![Sequence Diagram](img/uc03_sequence.drawio.svg)

### 6.2 Backend Flowchart
![Backend Flowchart](img/uc03_backend_flowchart.drawio.svg)

### 6.3 Class Diagram (Catalogo Libri)
![Class Diagram](img/books_class.drawio.svg)
