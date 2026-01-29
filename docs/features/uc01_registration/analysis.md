# UC01 - Registrazione Utente

## 1. Panoramica
**Descrizione:** Consente a un visitatore di creare un nuovo account nel sistema, permettendogli successivamente di accedere alle funzionalità riservate.

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Visitatore |
| **Pre-condizioni** | Il visitatore non è autenticato e si trova nella pagina di registrazione. |
| **Post-condizioni** | L'account è creato nel DB e il visitatore può fare login. |

![Use Case Diagram](img/uc01.drawio.svg)

## 2. Flussi di Eventi

### Flusso Principale
1. Il **Visitatore** accede alla pagina di registrazione.
2. Il sistema mostra il modulo (Nome, Email, Password).
3. Il **Visitatore** compila i campi e conferma.
4. Il sistema verifica che l'email non sia già registrata.
5. Il sistema valida il formato dei dati (es. password strong).
6. Il sistema crea il nuovo account nel database.
7. Il sistema reindirizza al login con messaggio di successo.
### Flussi Alternativi
* **A1: Email già registrata**
    1. Il sistema rileva che l'email esiste.
    2. Il sistema mostra errore: "Email già in uso".
    3. Il flusso termina (l'utente deve riprovare).

* **A2: Dati non validi**
    1. Il sistema rileva formato errato.
    2. Il sistema evidenzia i campi in rosso.
    3. L'utente corregge e si torna al passo 3.

---

## 3. Activity Diagram

![Activity Diagram Registrazione](img/uc01_flowchart.drawio.svg)

---

## 4. Criteri di Accettazione & Testing

* La password deve avere almeno 8 caratteri.
* Se la registrazione ha successo, l'utente non viene loggato automaticamente ma va al login.
* La mail deve contenere "@" e un dominio valido.

| ID Test | Azione | Risultato Atteso | Valida |
| :--- | :--- | :--- | :--- |
| T01 | Invia form vuoto | Bordi rossi sui campi | ✅ |
| T02 | Invia mail esistente | Messaggio errore specifico | ✅ |
| T03 | Invia dati corretti | Redirect a /login | ✅ |

---

## 5. Specifiche Tecniche

### 5.1 Sequence Diagram
![Sequence Diagram](img/uc01_sequence.drawio.svg)

### 5.2 Class Diagram Backend (Autenticazione)
![Class Diagram Auth](img/auth_class.drawio.svg)

### 5.3 Flowchart Backend
![Backend Flowchart](img/uc01_backend_flowchart.drawio.svg)

