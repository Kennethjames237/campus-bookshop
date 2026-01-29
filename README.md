# Progetto 6: UniprBooks - Mercatino Libri Usati UNIPR

> Piattaforma P2P per la compravendita di libri di testo.
> Corso di Ingegneria del Software 2025/2026.

## Documentazione PDF
La documentazione PDF è consultabile [QUA](docs/pdf/main.pdf)

## Il Team

| Studente | Matricola | Ruolo Principale |
| :--- | :--- | :--- |
| Andrea Belli | XXXX | Database |
| Nna Minkousse Kenneth James | XXXX | Frontend |
| Samuele Premori | XXXX | Backend |

## Quick start

1.  **Clonare il repository**
2.  **Configurare l'ambiente**
    ```bash
    cp .env.example .env
    ```
3.  **Avviare i container**
    ```bash
    docker-compose up -d
    ```
4.  **Accedere all'applicazione**
    * Frontend: [http://localhost:8080](http://localhost:8080)
    * Backend API: [http://localhost:8081](http://localhost:8081)

---
## Gestione Dati e Database

* **Rimuovere il Volume:**
    ```bash
    docker-compose down -v
    ```
* **Avvio Build forzata Immagine:**
    ```bash
    docker-compose up -d --build
    ```

---
## Indice Documentazione di Progetto

1.  [Processo di Sviluppo](docs/01_process.md)
    * [1.1 Metodologia](docs/01_process.md#11-metodologia)
    * [1.2 Organizzazione del Team](docs/01_process.md#12-organizzazione-del-team)
    * [1.3 Branch Strategy](docs/01_process.md#13-branch-strategy)
    * [1.4 Testing](docs/01_process.md#14-testing)

2.  [Analisi dei Requisiti](docs/02_requirements.md)
    * [2.1 Requisiti non Funzionali](docs/02_requirements.md#21-requisiti-non-funzionali)
    * [2.2 Requisiti Funzionali](docs/02_requirements.md#22-requisiti-funzionali)
    * [2.3 Attori del Sistema](docs/02_requirements.md#23-attori-del-sistema)
    * [2.4 User Stories](docs/02_requirements.md#24-user-stories)
    * [2.5 Matrice di Tracciabilità](docs/02_requirements.md#25-matrice-di-tracciabilità)

3.  [Architettura del Sistema](docs/03_architecture.md)
    * [3.1 Pattern MVP](docs/03_architecture.md#32-pattern-mvp)
    * [3.2 Schema Dati](docs/03_architecture.md#33-schema-dati)

4. [Contratti di Interfaccia](docs/04_contracts.md)
    * [4.1 Specifiche API](docs/04_contracts.md#41-specifiche-api)
    * [4.2 Interfaccia Database](docs/04_contracts.md#42-interfaccia-database)

5. [Sprint Log](docs/05_sprintlog.md)

6. **Use Cases**
    * [UC01 - Registrazione Utente](docs/features/uc01_registration/analysis.md)
    * [UC02 - Login Utente](docs/features/uc02_login/analysis.md)
    * [UC03 - Visualizzazione Lista Libri](docs/features/uc03_listing/analysis.md)
    * [UC04 - Pubblicazione Annuncio](docs/features/uc04_adding/analysis.md)
    * [UC05 - Acquisto diretto](docs/features/uc05_buy/analysis.md)
    * [UC06 - Ricerca](docs/features/uc06_search/analysis.md)
    * [UC07 - Messaggistica](docs/features/uc07_messaging/analysis.md)
---
# campus-bookshop
