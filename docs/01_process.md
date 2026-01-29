# 1 Processo di Sviluppo

## 1.1 Metodologia
Il processo adottato è **Iterativo** ed **Incrementale**. Ogni [Sprint](05_sprintlog.md) trasforma un sottoinsieme di [Requisiti](02_requirements.md) in un incremento software funzionante, testato e documentato.

### A. Sprint Planning
* **Analisi dei Requisiti:** Revisione [Requisiti](02_requirements.md) ed eventuale aggiornamento.
* **Selezione User Stories:** Spostamento delle user stories nello [Sprintlog](05_sprintlog.md).
* **Analisi:** Le user stories selezionate vengono dettagliate in `docs/features/` tramite:
    * Lo Use Case Diagram, con tabella Use Case per dettaglio ed eventuali Activity Diagram.
    * I [Criteri di Accettazione](#14-testing).
    * La strategia per i [Test di Integrazione](#14-testing) manuali.
* **Design Session:** Definizione dei [contratti](04_contracts.md):
    * Specifica JSON delle API.
    * Specifica metodi interfaccia database.
* **Task Assignment:** Suddivisione dei compiti, creazione dei [branch](#13-branch-strategy) `feature/` e creazione della issue relativa alla user story.

### B. Development Loop
* **Detailed Design:** Prima della codifica, ogni sviluppatore progetta il proprio componente nella cartella della feature:
    * Diagrammi delle Classi.
    * Diagrammi ER specifici.
* **Coding & Unit Testing:** Scrittura del codice sorgente e degli eventuali [Unit Test](#14-testing)
* **Pull Request:** Lo sviluppatore apre una PR verso il ramo di integrazione (`develop`).
* **Merge:** Se la review è positiva e i test passano, il codice viene integrato in `develop`.

### C. Sprint Review 
* **Integration Test:** Verifica completa del flusso basata sui **Criteri di Accettazione**.

![Activity Diagram](img/process-diagram.drawio.svg)
---

## 1.2 Organizzazione del Team

### Database
* **Responsabile:** Andrea Belli
* **Responsabilità:** gestione Database, gestione classe interfaccia Database.

### Backend
* **Responsabile:** Samuele Premori
* **Responsabilità:** Logica business, esposizione API, test di unità.

### Frontend
* **Responsabile:** Nna Minkousse Kenneth James
* **Responsabilità:** Interfaccia utente, integrazione API, test manuali.

---

## 1.3 Branch Strategy

### Branch structure
* `main`: codice stabile e testato
* `dev`: ramo di integrazione continua delle feature
* `feature/T{ID}`: ramo riguardante la task specifica, singolo per sviluppatore.

### Regole
* Vietato il push diretto su `main`.
* Il push diretto su `dev` avviene solo per modifiche della documentazione o modifiche tra gli sprint approvate in maniera unanime.
* Ogni ramo `feature` si chiude con un pull request su `dev`.
* Ogni pull request da `dev` a `main` deve contenere la documentazione aggiornate e il tag relativo alla versione.

---
## 1.4 Testing
La strategia di testing è divisa in:

* **Unit Testing:**
    * Applicato alle classi del Backend.
    * Obbligatorio passare i test prima della PR.
    * PHPUnit.
 
* **Integration Testing:**
    * Eseguito manualmente seguendo i file di collaudo compilati a inizio sprint.

---
