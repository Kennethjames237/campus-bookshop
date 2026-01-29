# UC02 - Login Utente

## 1. Panoramica
**Descrizione:** Consente a un visitatore registrato di autenticarsi nel sistema per accedere alle funzionalità riservate agli utenti (come la pubblicazione di annunci o l'acquisto).

| &nbsp; | &nbsp; |
| :--- | :--- |
| **Attori** | Visitatore (Registrato) |
| **Pre-condizioni** | Il visitatore possiede un account e non è già autenticato. |
| **Post-condizioni** | Il sistema crea una sessione/token e l'utente viene autenticato. |

![Use Case Diagram](img/uc02.drawio.svg)

---

## 2. Flussi di Eventi

### Flusso Principale
1. Il **Visitatore** accede alla pagina di login.
2. Il sistema mostra il modulo (Email e Password).
3. Il **Visitatore** inserisce le proprie credenziali e conferma.
4. Il sistema valida il formato dei dati (lato Frontend).
5. Il sistema verifica la corrispondenza delle credenziali nel database (lato Backend).
6. Il sistema genera un token di sessione.
7. Il sistema reindirizza l'utente alla Dashboard/Home con messaggio di successo.

### Flussi Alternativi

* **A1: Credenziali errate o Account inesistente**
    1. Il sistema rileva che l'email non esiste o la password è errata.
    2. Il sistema mostra un messaggio di errore generico: *"Email o password non corretti"*.
    3. Il flusso riprende dal punto 3.
    *Note: Si usa un messaggio generico per evitare che malintenzionati scoprano quali email sono registrate.*

* **A2: Formato dati non valido**
    1. Il sistema rileva che la mail non è nel formato corretto o i campi sono vuoti.
    2. Il sistema blocca l'invio e segnala i campi da correggere.

---

## 3. Activity Diagram

![Activity Diagram Login](img/uc02_flowchart.drawio.svg)

---

## 4. Criteri di Accettazione
* **Sicurezza:** Le password non devono mai apparire in chiaro nel modulo.
* **Sessione:** Al login effettuato, il sistema deve memorizzare lo stato di "Loggato".
* **Feedback:** In caso di errore, i campi non devono essere resettati per permettere la correzione veloce.

---

## 5. Piano di Test Manuale
| ID | Azione | Risultato Atteso | Valida |
| :--- | :--- | :--- | :--- |
| **T01** | Inserire email non registrata | Messaggio di errore generico | ✅ |
| **T02** | Inserire email corretta ma password errata | Messaggio di errore generico | ✅ |
| **T03** | Lasciare il campo password vuoto | Il tasto "Login" deve essere disabilitato o mostrare errore | ✅ |
| **T04** | Inserire credenziali corrette | Reindirizzamento alla Home e Navbar aggiornata con "Logout" | ✅ |

---

## 6. Design Tecnico

### 6.1 Sequence Diagram
![Sequence Diagram](img/uc02_sequence.drawio.svg)

### 6.2 Flowchart Backend
![Backend Flowchart](img/uc02_backend_flowchart.drawio.svg)

### 6.3 Ciclo di Vita JWT Token
![JWT Lifecycle](img/jwt_lifecycle.drawio.svg)

