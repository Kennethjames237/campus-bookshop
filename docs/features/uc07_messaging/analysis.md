# UC07 - Messaggistica tra Utenti

## 1. Panoramica
**Descrizione:** Consente a un Utente Autenticato di inviare e ricevere messaggi diretti da altri utenti, per accordarsi su prezzo, pagamento e consegna dei libri.

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Utente Autenticato (Mittente), Utente Autenticato (Destinatario) |
| **Pre-condizioni** | Entrambi gli utenti sono registrati nel sistema. Il mittente è autenticato. |
| **Post-condizioni** | Il messaggio è salvato nel DB e visibile al destinatario. |

![Use Case Diagram](img/uc07.drawio.svg)

---

## 2. Flussi di Eventi

### Flusso Principale
1. L'**Utente** accede alla sezione messaggi o clicca "Contatta venditore" da un annuncio.
2. Il sistema mostra la lista delle conversazioni esistenti o apre una nuova conversazione.
3. L'**Utente** scrive un messaggio nel campo di testo.
4. L'**Utente** clicca su "Invia".
5. Il sistema (Backend) salva il messaggio nel database con mittente, destinatario e timestamp.
6. Il sistema aggiorna la conversazione mostrando il nuovo messaggio.

### Flussi Alternativi

* **A1: Messaggio vuoto**
    1. L'utente tenta di inviare un messaggio senza contenuto.
    2. Il sistema disabilita il pulsante "Invia" o mostra un errore.

* **A2: Destinatario non trovato**
    1. Il sistema non trova l'utente destinatario (es. account eliminato).
    2. Il sistema mostra l'errore: *"Impossibile inviare il messaggio. Utente non trovato"*.

* **A3: Visualizzazione nuovi messaggi**
    1. L'utente accede alla sezione messaggi.
    2. Il sistema evidenzia le conversazioni con messaggi non letti.

---

## 3. Activity Diagram

![Activity Diagram](img/uc07_flowchart.drawio.svg)

---

## 4. Criteri di Accettazione
* Solo gli utenti autenticati possono inviare e ricevere messaggi.
* Ogni messaggio deve essere associato a un mittente e un destinatario.
* I messaggi devono essere ordinati cronologicamente nella conversazione.
* L'utente deve poter visualizzare lo storico dei messaggi con un altro utente.
* I messaggi non letti devono essere evidenziati o contrassegnati.
* Non è possibile inviare messaggi a se stessi.

---

## 5. Piano di Test Manuale
| ID | Azione | Risultato Atteso |
| :--- | :--- | :--- |
| **T01** | Inviare un messaggio a un altro utente | Il messaggio appare nella conversazione di entrambi gli utenti. |
| **T02** | Tentativo di invio messaggio vuoto | Pulsante "Invia" disabilitato o errore di validazione. |
| **T03** | Accedere alla sezione messaggi con messaggi non letti | I messaggi non letti sono evidenziati. |
| **T04** | Visualizzare lo storico di una conversazione | Tutti i messaggi sono mostrati in ordine cronologico. |
| **T05** | Tentativo di inviare messaggio da utente non autenticato | Redirect alla pagina di login. |
| **T06** | Contattare venditore da un annuncio | Si apre la conversazione con il venditore di quel libro. |

---

## 6. Design Tecnico

### 6.1 Sequence Diagram
Il diagramma seguente mostra l'interazione tra Frontend, Backend e Database per le operazioni di messaggistica.

![Sequence Diagram](img/uc07_sequence.drawio.svg)

### 6.2 Backend Flowchart
Il seguente diagramma descrive la logica di elaborazione degli endpoint API per la messaggistica:
- `GET /conversations` - Recupera la lista delle conversazioni dell'utente
- `GET /messages?userId=X` - Recupera i messaggi scambiati con un utente specifico
- `POST /messages` - Invia un nuovo messaggio

![Backend Flowchart](img/uc07_backend_flowchart.drawio.svg)

**Validazioni comuni a tutti gli endpoint:**
1. **Autenticazione JWT:** Tutti gli endpoint richiedono un token JWT valido
2. **Estrazione senderId:** Il mittente viene sempre estratto dal token, mai dal body della richiesta

**Validazioni specifiche per POST /messages:**
1. **Contenuto non vuoto:** Il messaggio deve avere contenuto
2. **Anti auto-invio:** Non è possibile inviare messaggi a se stessi (`senderId != receiverId`)
3. **Destinatario esistente:** Verifica che l'utente destinatario esista nel sistema
