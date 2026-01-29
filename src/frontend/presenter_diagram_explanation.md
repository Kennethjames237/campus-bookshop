# Frontend Presenter Architecture - Class Diagram Explanation

This document explains the structure and relationships of the frontend classes as depicted in the class diagram. The architecture follows a **Model-View-Presenter (MVP)** pattern variant, where "Presenters" handle UI logic and API communication, while the "View" is manipulated directly via the DOM (or simple HTML references).

## 1. ApiService (Static Service)
**Role**: The central communication hub for the entire frontend.
*   **Nature**: It consists of static methods handling AJAX requests (`$.ajax`).
*   **Responsibilities**:
    *   Manages the JWT Token (attaching `Authorization` header).
    *   Exposes endpoints for Login, Registration, Book operations (Get, Create, Update, Delete), Purchases, and Messaging.
    *   **Abstraction**: Hides the underlying HTTP details from the Presenters.

## 2. AuthPresenter
**Role**: Manages User Authentication flows.
*   **Key Responsibilities**:
    *   Listens to Login and Registration forms.
    *   Calls `ApiService.login()` and `ApiService.register()`.
    *   **JWT Handling**: Parses the received JWT token to extract user info (`parseJwt`) and stores it in `localStorage`.
    *   Redirects the user to the dashboard upon success.

## 3. BooksPresenter
**Role**: The primary controller for the Book Marketplace interface.
*   **Key Responsibilities**:
    *   **Dashboard**: Fetches and renders all specific books (`fetchBooks`, `renderBooks`).
    *   **Filters**: Implements filtering logic (Strategy Pattern with `CompositeFilter`, `GeneralSearchFilter`, etc.) to search books by title, ISBN, teacher, or course.
    *   **My Books**: Displays books belonging to the logged-in user (`handleMyBooks`).
    *   **CRUD Operations**: Handles creating (`handleInsertAd`), updating price (`handleEditBook`), and deleting (`handleDeleteBook`) advertisements.
    *   **Commerce**: Handles the buying process (`handleBuyBook`) and displays Purchase History (`renderPurchases`) and Sales History (`renderSales`).
    *   **Navigation**: Manages the user dropdown menu including logout.

## 4. ChatPresenter
**Role**: Manages the Real-time(ish) Messaging System.
*   **Key Responsibilities**:
    *   **Conversations**: Fetches and lists active conversations (`fetchConversations`).
    *   **Messaging**: Displays chat history (`renderMessages`) and sends new messages (`sendMessage`).
    *   **Deep Links**: Handles URL parameters (e.g., `?userId=X`) to open a specific chat directly from a book card.
    *   **UI Updates**: Formats timestamps and distinguishes between sent and received messages.

## 5. User (Model)
**Role**: A simple data transfer object (DTO).
*   **Responsibilities**:
    *   Encapsulates user registration data (`username`, `email`, `password`) before sending it to the API.

## Relationships
*   **Dependency**: All Presenters (`AuthPresenter`, `BooksPresenter`, `ChatPresenter`) depend on `ApiService` to perform network operations.
*   **Association**: `AuthPresenter` uses the `User` class to structure registration data.
*   **Flow**:
    1.  `BooksPresenter` (Book Card) -> Request to Chat (`window.location.href`).
    2.  `ChatPresenter` initializes -> Checks URL params -> Opens Conversation.
