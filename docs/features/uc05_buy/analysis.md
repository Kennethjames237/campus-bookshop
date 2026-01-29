# UC05 - Acquisto Diretto

## 1. Panoramica
**Descrizione:** Consente a un Utente Autenticato di acquisire un libro. L'operazione è puramente logica (non c'è transazione monetaria reale): il libro sparisce dalla bacheca e viene spostato negli storici dei due utenti coinvolti.

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Utente Autenticato (Acquirente) |
| **Pre-condizioni** | L'acquirente è loggato e il libro ha stato "Disponibile". |
| **Post-condizioni** | Libro impostato come "Venduto", rimosso dalla bacheca e aggiunto agli storici. |

![Use Case Diagram](img/uc05.drawio.svg)

---

## 2. Flussi di Eventi

### Flusso Principale
1. L'**Acquirente** visualizza i dettagli di un libro nella bacheca.
2. L'**Acquirente** clicca sul pulsante "Acquista".
3. Il sistema chiede conferma dell'operazione.
4. L'**Acquirente** conferma.
5. Il sistema (Backend) verifica che il libro sia ancora nello stato "Disponibile".
6. Il sistema crea un record nella tabella `transactions` (o `purchases`).
7. Il sistema aggiorna lo stato del libro in `status = 'sold'`.
8. Il sistema mostra un messaggio di successo e fornisce i contatti del venditore (es. email) per accordarsi sulla consegna.

### Flussi Alternativi

* **A1: Libro già venduto**
    1. Il sistema rileva che il libro è appena stato acquistato da un altro utente.
    2. Il sistema mostra l'errore: *"Spiacenti, il libro è stato appena venduto"*.
    3. L'utente viene reindirizzato alla bacheca aggiornata.

* **A2: Tentativo di auto-acquisto**
    1. Il sistema rileva che l'acquirente è anche il venditore del libro.
    2. Il pulsante "Acquista" è disabilitato o restituisce un errore di logica.

---

## 3. Activity Diagram

![Activity Diagram Acquisto Diretto](img/uc05_activity.drawio.svg)

---

## 4. Criteri di Accettazione
* Un libro venduto non deve comparire nei risultati di ricerca.
* L'acquisto deve generare una voce nello storico "Miei Acquisti" dell'acquirente.
* L'acquisto deve generare una voce nello storico "Libri Venduti" del venditore.
* L'operazione deve essere atomica (se fallisce il salvataggio dello storico, il libro non deve risultare venduto).

---

## 5. Piano di Test Manuale
| ID | Azione | Risultato Atteso |
| :--- | :--- | :--- |
| **T01** | Cliccare su "Acquista" e poi "Annulla" | Nessuna modifica al database, il libro resta disponibile. |
| **T02** | Confermare l'acquisto | Messaggio di successo, libro sparito dalla bacheca. |
| **T03** | Verificare "Storico Acquisti" | Il libro appena comprato deve apparire in cima alla lista. |
| **T04** | Tentativo di accesso diretto tramite URL a un libro già venduto | Il sistema deve mostrare "Libro non disponibile". |

---

## 6. Design Tecnico

### 6.1 Sequence Diagram
![Sequence Diagram](img/uc05_sequence.drawio.svg)

### 6.2 Backend Flowchart
Il seguente diagramma descrive la logica di elaborazione dell'endpoint `POST /purchase` nel backend, includendo tutte le validazioni e i casi di errore.

![Backend Flowchart](img/uc05_backend_flowchart.drawio.svg)

**Flusso di validazione:**
1. **Autenticazione JWT:** Verifica che il token sia valido e non scaduto
2. **Validazione Input:** Controllo presenza e formato del `bookId`
3. **Esistenza Libro:** Verifica che il libro esista nel database
4. **Disponibilità:** Controllo che `book.available == true`
5. **Anti Auto-Acquisto:** Verifica che `buyer_id != seller_id`

**Operazione Atomica:**
La funzione `placeOrder()` esegue in una singola transazione database:
- Creazione record nella tabella `transactions`
- Aggiornamento del campo `available = false` sul libro
