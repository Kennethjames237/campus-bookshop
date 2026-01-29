# 4 Contratti di Interfaccia

## 4.1 Specifiche API

In questa sezione vengono definiti gli endpoint del sistema. Tutte le richieste e le risposte utilizzano il formato **JSON**. Per le operazioni di scrittura e protezione dei dati, il sistema adotta lo standard **JWT** (JSON Web Token).

> **Nota sull'Autenticazione:** Gli endpoint che richiedono autorizzazione devono includere l'header:
> `Authorization: Bearer <token_jwt>`

> **Risposta Errore Autorizzazione:** Se il token è mancante, invalido o scaduto, gli endpoint protetti restituiscono:
> ```json
> {
>   "status": "error",
>   "message": "Unauthorized"
> }
> ```

---

### POST /register

Registrazione di un nuovo utente.

* **Request Body:**
```json
{
  "email": "utente@esempio.it",
  "username": "mario_rossi",
  "password": "password"
}

```


* **Response (Success):**
```json
{
  "status": "success",
  "message": "User registered successfully"
}

```

* **Response (Error - Input non valido):**
```json
{
  "status": "error",
  "message": "Invalid input"
}

```

---

### POST /login

Autenticazione e rilascio del token.

* **Request Body:**
```json
{
  "email": "utente@esempio.it",
  "password": "password"
}

```


* **Response (Success):**
```json
{
  "status": "success",
  "message": "Login successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}

```

* **Response (Error - Input non valido):**
```json
{
  "status": "error",
  "message": "Invalid input"
}

```

* **Response (Error - Credenziali errate):**
```json
{
  "status": "error",
  "message": "Invalid credentials"
}

```

> **Nota Sicurezza:** Il messaggio "Invalid credentials" è volutamente generico per prevenire attacchi di enumerazione utenti. Non viene mai rivelato se l'email è registrata o meno.

---

### GET /books

Recupero della lista di tutti i libri disponibili.

* **Auth:** Opzionale (JWT)

> **Nota Filtro Identità:** Se l'utente è autenticato (JWT valido), il sistema esclude automaticamente i libri pubblicati dall'utente stesso dalla lista restituita.

* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Ingegneria del Software",
      "author": "Ian Sommerville",
      "isbn": "978-8871926284",
      "imagePath": "data:image/jpeg;base64,/9j/4AAQ...",
      "teacher": "Prof. Bagnara",
      "course": "Informatica",
      "price": 35.00,
      "sellerId": 10,
      "sellerUsername": "username",
      "available": true
    }
  ]
}
```

> **Nota Immagine:** Il campo `imagePath` contiene l'immagine codificata in formato base64 data URI, pronta per essere utilizzata direttamente come attributo `src` di un tag `<img>`. Se il libro non ha un'immagine associata, il campo sarà una stringa vuota.

```

* **Response (Error - Errore server):**
```json
{
  "status": "error",
  "message": "Server error"
}

```

--- 

### GET /my-books

Recupera la lista di tutti i libri messi in vendita dall'utente correntemente loggato.

* **Auth:** Obbligatoria (JWT)

* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 5,
      "name": "Sistemi Operativi",
      "author": "Silberschatz",
      "isbn": "978-1118063330",
      "imagePath": "data:image/jpeg;base64,...",
      "teacher": "Prof. Veltri",
      "course": "Informatica",
      "price": 40.00,
      "sellerId": 10,
      "sellerUsername": "username",
      "available": true
    }
  ]
}
```
---

### POST /books

Inserimento di un nuovo libro nel catalogo.

* **Auth:** JWT Required
* **Request Body:**
```json
{
  "name": "Sistemi Operativi",
  "author": "Silberschatz",
  "isbn": "978-1122334455",
  "image": "data:image/jpeg;base64,/9j/4AAQ...",
  "teacher": "Prof. Bagnara",
  "course": "Informatica",
  "price": 28.00
}

```

> **Nota:** Il campo `sellerId` viene estratto automaticamente dal JWT (claim `sub`) e non deve essere specificato nel body.

> **Nota Upload Immagine:** Il campo `image` è opzionale e accetta dati in formato base64 (con o senza prefisso data URI). Formati supportati: JPEG, PNG, WebP. Dimensione massima: 5MB. Il server elabora l'immagine e salva il percorso risultante nel campo `imagePath` della risposta GET.

* **Response (Success):**
```json
{ "status": "success", "id": 105,
  "message": "Libro messo in vendita con successo"
}

```

* **Response (Error - Input non valido):**
```json
{
  "status": "error",
  "message": "Invalid input"
}

```

* **Response (Error - Formato immagine non valido):**
```json
{
  "status": "error",
  "message": "Invalid file format"
}

```

* **Response (Error - File troppo grande):**
```json
{
  "status": "error",
  "message": "File too large"
}

```



---

### PUT /books

Aggiornamento di un libro esistente. L'**id** è obbligatorio.

* **Auth:** JWT Required

> **Nota Ownership:** L'utente può modificare solo i propri libri. Il sistema verifica che `book.sellerId` corrisponda all'ID dell'utente autenticato.

* **Request Body:**
```json
{
  "id": 172,
  "price": 25.00,
  "available": false
}

```

* **Response (Success):**
```json
{
  "status": "success",
  "message": "Book updated"
}

```

* **Response (Error - Input non valido):**
```json
{
  "status": "error",
  "message": "Invalid input"
}

```

* **Response (Error - Libro non trovato):**
```json
{
  "status": "error",
  "message": "Book not found"
}

```

* **Response (Error - Non proprietario):**
```json
{
  "status": "error",
  "message": "Forbidden"
}

```



---

### DELETE /books

Rimozione di un libro.

* **Auth:** JWT Required

> **Nota Ownership:** L'utente può eliminare solo i propri libri. Il sistema verifica che `book.sellerId` corrisponda all'ID dell'utente autenticato.

* **Request Body:** `{"id": 78}`

* **Response (Success):**
```json
{
  "status": "success",
  "message": "Book deleted"
}

```

* **Response (Error - Input non valido):**
```json
{
  "status": "error",
  "message": "Invalid input"
}

```

* **Response (Error - Libro non trovato):**
```json
{
  "status": "error",
  "message": "Book not found"
}

```

* **Response (Error - Non proprietario):**
```json
{
  "status": "error",
  "message": "Forbidden"
}

```



---

### POST /purchase

Effettua l'acquisto di un libro.

* **Auth:** JWT Required
* **Request Body:**
```json
{
  "bookId": 78
}

```

* **Response (Success):**
```json
{
  "status": "success",
  "message": "Purchase completed successfully",
  "orderId": 501,
  "sellerEmail": "venditore@email.it"
}

```

* **Response (Error - Libro non trovato):**
```json
{
  "status": "error",
  "message": "Book not found"
}

```

* **Response (Error - Libro già venduto):**
```json
{
  "status": "error",
  "message": "Book already sold"
}

```

* **Response (Error - Auto-acquisto):**
```json
{
  "status": "error",
  "message": "Cannot purchase your own book"
}

```

---

### GET /purchases

Recupera lo storico degli acquisti dell'utente autenticato.

* **Auth:** JWT Required
* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "orderId": 501,
      "book": {
        "id": 78,
        "name": "Ingegneria del Software",
        "author": "Ian Sommerville",
        "price": 35.00
      },
      "sellerUsername": "mario_rossi",
      "purchaseDate": "2026-01-25T14:30:00Z"
    }
  ]
}

```

* **Response (Error - Errore server):**
```json
{
  "status": "error",
  "message": "Server error"
}

```

---

### GET /sales

Recupera lo storico delle vendite dell'utente autenticato.

* **Auth:** JWT Required
* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "orderId": 501,
      "book": {
        "id": 78,
        "name": "Ingegneria del Software",
        "author": "Ian Sommerville",
        "price": 35.00
      },
      "buyerUsername": "luigi_verdi",
      "saleDate": "2026-01-25T14:30:00Z"
    }
  ]
}

```

* **Response (Error - Errore server):**
```json
{
  "status": "error",
  "message": "Server error"
}

```

---

### GET /conversations

Recupera la lista delle conversazioni dell'utente autenticato.

* **Auth:** JWT Required
* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "userId": 5,
      "username": "luigi_verdi",
      "lastMessage": "Ok, ci vediamo domani",
      "lastMessageDate": "2026-01-25T14:30:00Z"
    }
  ]
}

```

* **Response (Error - Errore server):**
```json
{
  "status": "error",
  "message": "Server error"
}

```

---

### GET /messages?userId={id}

Recupera i messaggi scambiati con un utente specifico.

* **Auth:** JWT Required
* **Query Params:** `userId` (required)
* **Response (Success):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "senderId": 5,
      "receiverId": 10,
      "content": "Ciao, il libro è ancora disponibile?",
      "createdAt": "2026-01-25T14:00:00Z"
    }
  ]
}

```

* **Response (Error - Utente non trovato):**
```json
{
  "status": "error",
  "message": "User not found"
}

```

---

### POST /messages

Invia un nuovo messaggio a un utente.

* **Auth:** JWT Required
* **Request Body:**
```json
{
  "receiverId": 5,
  "content": "Ciao, sono interessato al libro"
}

```

* **Response (Success):**
```json
{
  "status": "success",
  "message": "Message sent",
  "messageId": 42
}

```

* **Response (Error - Destinatario non trovato):**
```json
{
  "status": "error",
  "message": "User not found"
}

```

* **Response (Error - Messaggio a se stesso):**
```json
{
  "status": "error",
  "message": "Cannot message yourself"
}

```

* **Response (Error - Contenuto vuoto):**
```json
{
  "status": "error",
  "message": "Message content required"
}

```

---

> **Nota sulla Ricerca Avanzata (RF05):** La funzionalità di ricerca per ISBN, corso e docente è implementata interamente lato Frontend mediante filtri JavaScript sui dati già recuperati tramite `GET /books`. Non sono previsti endpoint dedicati.

---

## 4.2 Interfaccia Database

### Entity Classes

Oggetti php rappresentanti i dati scambiati tra i vari moduli. Il campo `id` è nullable per poter creare nuovi oggetti.

```php
class User {
    public ?int $id;
    public string $username;
    public string $email;
    public string $password;
}

class Book {
    public ?int $id;
    public string $name;
    public string $author;
    public string $isbn;
    public string $imagePath;
    public string $teacher;
    public string $course;
    public float $price;
    public int $sellerId;
    public bool $available;
}

class Transaction {
    public ?int $id;
    public int $bookId;
    public int $buyerId;
    public int $sellerId;
    public DateTime $createdAt;
}

class Message {
    public ?int $id;
    public int $senderId;
    public int $receiverId;
    public string $content;
    public DateTime $createdAt;
}

```

### Metodi Gestione Utenti

* `registerUser(User $user): bool`: Esegue l'insert del nuovo utente.
* `verifyCredentials(string $email, string $password): ?int`: Ritorna l'ID utente se le credenziali sono corrette (con verifica `password_verify`), altrimenti `null`.
* `getUserById(int $id): ?User`: Ritorna l'utente con l'ID specificato, oppure `null` se non trovato.
  > **Nota:** Metodo aggiunto per supportare il recupero dell'email del venditore in `POST /purchase`.

### Metodi Gestione Libri

* `getAllBooks(): array`: Ritorna un array di oggetti `Book` disponibili.
* `insertBook(Book $book): int`: Inserisce un libro e ritorna l'ID generato.
* `updateBook(Book $book): bool`: Aggiorna i campi modificati.
* `deleteBook(int $id): bool`: Rimuove logicamente o fisicamente il record.

### Metodi Gestione Transazioni

* `placeOrder(int $bookId, int $buyerId): int`: Crea un record nella tabella ordini, aggiorna `available = false` sul libro e ritorna l'ID ordine.
* `getPurchasesByBuyer(int $buyerId): array`: Ritorna lo storico acquisti dell'utente.
* `getSalesBySeller(int $sellerId): array`: Ritorna lo storico vendite dell'utente.

### Metodi Gestione Messaggi

* `getConversations(int $userId): array`: Ritorna la lista delle conversazioni con ultimo messaggio.
* `getMessages(int $userId1, int $userId2): array`: Ritorna i messaggi tra due utenti ordinati cronologicamente.
* `sendMessage(Message $message): int`: Inserisce un messaggio e ritorna l'ID generato.

---

