import { ApiService } from './ApiService.js';
import { GeneralSearchFilter } from '../filters/GeneralSearchFilter.js';
import { IsbnFilter } from '../filters/IsbnFilter.js';
import { TeacherFilter } from '../filters/TeacherFilter.js';
import { CourseFilter } from '../filters/CourseFilter.js';
import { CompositeFilter } from '../filters/CompositeFilter.js';

export class BooksPresenter {
    constructor() {
        this.books = [];
        this.renderTarget = document.getElementById('books-container');
        this.currentUser = JSON.parse(localStorage.getItem('currentUser')) || null;
        this.init();
    }

    shouldShowChatButton(book) {
        // Hide if not logged in
        if (!this.currentUser) return false;
        
        // Hide if seller is missing
        if (!book.sellerId) return false;
        
        // Hide if seller is self (prevent self-messaging error)
        if (String(book.sellerId) === String(this.currentUser.id)) return false;
        
        return true;
    }

    async init() {
        await this.fetchBooks();
        this.renderBooks();
        this.attachEventListeners();
        this.setupAuthButton();
    }


    async fetchBooks() {
        try {
            // Use ApiService instead of MockServer directly
            const response = await ApiService.getBooks();
            
            if (response.status === 'success') {
                // Filter: only show available books on the dashboard
                this.books = response.data.filter(book => book.available === true || book.available === 1);
            } else {
                console.error("Failed to fetch books:", response);
                this.showError("Could not load books.");
            }
        } catch (error) {
            console.error("Error fetching books:", error);
            this.showError("An unexpected error occurred.");
        }
    }

    renderBooks(booksToRender = this.books) {
        if (!this.renderTarget) return;
        
        this.renderTarget.innerHTML = '';

        if (booksToRender.length === 0) {
            this.renderTarget.innerHTML = '<p class="no-books-msg">Nessun libro trovato.</p>';
            return;
        }

        booksToRender.forEach(book => {
            const isMyBook = this.currentUser && String(book.sellerId) === String(this.currentUser.id);
            const bookCard = document.createElement('div');
            bookCard.className = 'book-card';
            let actionsHtml = '';
            
            if (isMyBook) {
                 actionsHtml = `
                    <button class="btn-edit" data-id="${book.id}" style="background-color: #ffc107; color: black; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; margin-right: 5px;">Modifica</button>
                    <button class="btn-delete" data-id="${book.id}" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Cancella</button>
                 `;
            } else {
                 actionsHtml = `
                    <button class="btn-buy" data-id="${book.id}">Compra</button>
                    ${this.shouldShowChatButton(book) ? 
                        `<button class="btn-chat" onclick="window.location.href='/chat?userId=${book.sellerId}'">Contatta</button>` 
                        : ''}
                 `;
            }

            bookCard.innerHTML = `
                <div class="book-image">
                    <!-- Placeholder image if imagePath fails or is mock -->
                    <img src="${book.imagePath}" alt="${book.name}" onerror="this.src='https://via.placeholder.com/150'">
                </div>
                <div class="book-info">
                    <h3>${book.name}</h3>
                    <p class="author">by ${book.author}</p>
                    <p class="detail"><strong>ISBN:</strong> ${book.isbn || 'N/A'}</p>
                    <p class="detail"><strong>Course:</strong> ${book.course}</p>
                    <p class="detail"><strong>Teacher:</strong> ${book.teacher || 'N/A'}</p>
                    <p class="detail"><strong>Posted by:</strong> ${book.sellerUsername || 'Unknown'}</p>
                    <p class="price">$${parseFloat(book.price).toFixed(2)}</p>
                    <div class="card-actions">
                        ${actionsHtml}
                    </div>
                </div>
            `;
            this.renderTarget.appendChild(bookCard);
            
            // Attach listeners immediately if it's my book
            if (isMyBook) {
                const deleteBtn = bookCard.querySelector('.btn-delete');
                if (deleteBtn) {
                     deleteBtn.addEventListener('click', () => this.handleDeleteBook(book.id));
                }
                const editBtn = bookCard.querySelector('.btn-edit');
                if (editBtn) {
                     editBtn.addEventListener('click', () => this.handleEditBook(book));
                }
            } else {
                 const buyBtn = bookCard.querySelector('.btn-buy');
                 if (buyBtn) {
                     buyBtn.addEventListener('click', () => this.handleBuyBook(book.id));
                 }
            }
        });
    }

    renderPurchases(purchases) {
        if (!this.renderTarget) return;
        
        this.renderTarget.innerHTML = '<h2 style="width:100%; margin-bottom: 20px;">Storico Acquisti</h2>';

        if (!purchases || purchases.length === 0) {
            this.renderTarget.innerHTML += '<p class="no-books-msg">Nessun acquisto trovato.</p>';
            return;
        }

        purchases.forEach(item => {
            const book = item.book;
            const date = new Date(item.purchaseDate).toLocaleDateString();
            
            const card = document.createElement('div');
            card.className = 'book-card'; // Reuse style
            
            // We use a simplified view for purchased items
            card.innerHTML = `
                <div class="book-info" style="padding: 15px;">
                    <h3>${book.name}</h3>
                    <p class="author">by ${book.author}</p>
                    <p class="price" style="color: green;">Acquistato: $${parseFloat(book.price).toFixed(2)}</p>
                    <hr>
                    <p class="detail"><strong>Ordine #:</strong> ${item.orderId}</p>
                    <p class="detail"><strong>Data:</strong> ${date}</p>
                    <p class="detail"><strong>Venditore:</strong> ${item.sellerUsername}</p>
                    
                    <div class="card-actions" style="margin-top: 10px;">
                        <!-- Button removed as requested -->
                    </div>
                </div>
            `;
            this.renderTarget.appendChild(card);
        });
    }

    attachEventListeners() {
        const insertAdBtn = document.getElementById('btn-insert-ad');
        if (insertAdBtn) {
            insertAdBtn.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('insertAdModal'));
                modal.show();
            });
        }

        const insertAdForm = document.getElementById('insertAdForm');
        if (insertAdForm) {
            insertAdForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleInsertAd();
            });
        }
        
        this.attachFilterListeners();
    }

    attachFilterListeners() {
        // IDs: filter-general, filter-isbn, filter-teacher, filter-course, btn-reset-filters
        const inputs = ['filter-general', 'filter-isbn', 'filter-teacher', 'filter-course'];
        
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                // Real-time filtering on keyup/change
                el.addEventListener('input', () => this.applyFilters());
                el.addEventListener('change', () => this.applyFilters());
            }
        });

        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                inputs.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = ''; // Reset value
                });
                this.applyFilters(); // Re-apply (will define "empty" filters -> show all)
            });
        }
    }

    applyFilters() {
        const generalQuery = document.getElementById('filter-general').value;
        const isbnQuery = document.getElementById('filter-isbn').value;
        const teacherQuery = document.getElementById('filter-teacher').value;
        const courseQuery = document.getElementById('filter-course').value;

        // Build the Composite Filter
        const composite = new CompositeFilter();
        
        if (generalQuery) composite.add(new GeneralSearchFilter(generalQuery));
        if (isbnQuery) composite.add(new IsbnFilter(isbnQuery));
        if (teacherQuery) composite.add(new TeacherFilter(teacherQuery));
        if (courseQuery) composite.add(new CourseFilter(courseQuery));

        // Filter the books
        const filteredBooks = this.books.filter(book => composite.matches(book));
        
        // Render filtered result
        this.renderBooks(filteredBooks);
    }

    setupAuthButton() {
        const authWrapper = document.getElementById('auth-wrapper');
        if (!authWrapper) return;

        const token = localStorage.getItem('token');
        const user = JSON.parse(localStorage.getItem('currentUser'));

        const messageWrapper = document.getElementById('message-wrapper');
        if (messageWrapper) {
            messageWrapper.style.display = token ? 'block' : 'none';
        }

        if (token) {
            // User is logged in: Render Dropdown with SVG
            authWrapper.classList.add('dropdown');
            authWrapper.innerHTML = `
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <img src="../img/profile.png" alt="Profile" style="width: 30px; height: 30px;">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="#" id="booksForSaleBtn">
                        <img src="../img/vendita.png" alt="Icon" style="width: 20px; height: 20px; margin-right: 5px;"> Libri in vendita
                    </a></li>
                    <li><a class="dropdown-item" href="#" id="myBooksBtn">
                        <img src="../img/storico.png" alt="Icon" style="width: 20px; height: 20px; margin-right: 5px;"> Storico vendite
                    </a></li>
                    <li><a class="dropdown-item" href="#" id="purchaseHistoryBtn">
                        <img src="../img/storico.png" alt="Icon" style="width: 20px; height: 20px; margin-right: 5px;"> Storico acquisti
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" id="logoutBtn">
                        <img src="../img/logout.png" alt="Logout" style="width: 20px; height: 20px; margin-right: 5px;"> Logout
                    </a></li>
                </ul>
            `;

            // Attach Logout Listener
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleLogout();
                });
            }

            // Attach "Libri in vendita" Listener (ID: booksForSaleBtn)
            const booksForSaleBtn = document.getElementById('booksForSaleBtn');
            if (booksForSaleBtn) {
                booksForSaleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleMyBooks();
                });
            }

            // Attach "Storico vendite" Listener (ID: myBooksBtn - legacy name from older code)
            const salesHistoryBtn = document.getElementById('myBooksBtn');
            if (salesHistoryBtn) {
                salesHistoryBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleSalesHistory();
                });
            }

            // Attach "Storico acquisti" Listener
            const purchHistoryBtn = document.getElementById('purchaseHistoryBtn');
            if (purchHistoryBtn) {
                purchHistoryBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handlePurchaseHistory();
                });
            }

        } else {
            // User is not logged in: Render Login Link
            authWrapper.classList.remove('dropdown');
            authWrapper.innerHTML = `<a class="nav-link" href="/login" id="authBtn">Login</a>`;
            
            // Just for safety if we wanted to attach listener, but href=/login handles it
            /* 
            const authBtn = document.getElementById('authBtn');
            authBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = '/login';
            });
            */
        }
    }

    async handleMyBooks() {
        try {
            const response = await ApiService.getMyBooks();
            if (response.status === 'success') {
                this.renderBooks(response.data);
                // Optionally update UI title or state to indicate "My Books" view
            } else {
                console.error("Failed to fetch my books:", response);
                this.showError("Could not load your books.");
            }
        } catch (error) {
            console.error("Error fetching my books:", error);
            this.showError("An unexpected error occurred.");
        }
    }

    async handlePurchaseHistory() {
        try {
            const response = await ApiService.getPurchases();
            if (response.status === 'success') {
                this.renderPurchases(response.data);
            } else {
                console.error("Failed to fetch purchases:", response);
                this.showError("Could not load purchase history.");
            }
        } catch (error) {
            console.error("Error fetching purchases:", error);
            this.showError("An unexpected error occurred.");
        }
    }

    async handleSalesHistory() {
        try {
            const response = await ApiService.getSales();
            if (response.status === 'success') {
                this.renderSales(response.data);
            } else {
                console.error("Failed to fetch sales:", response);
                this.showError("Could not load sales history.");
            }
        } catch (error) {
            console.error("Error fetching sales:", error);
            this.showError("An unexpected error occurred.");
        }
    }

    renderSales(sales) {
        if (!this.renderTarget) return;
        
        this.renderTarget.innerHTML = '<h2 style="width:100%; margin-bottom: 20px;">Storico Vendite</h2>';

        if (!sales || sales.length === 0) {
            this.renderTarget.innerHTML += '<p class="no-books-msg">Nessuna vendita trovata.</p>';
            return;
        }

        sales.forEach(item => {
            const book = item.book;
            const date = new Date(item.saleDate).toLocaleDateString();
            
            const card = document.createElement('div');
            card.className = 'book-card';
            
            card.innerHTML = `
                <div class="book-info" style="padding: 15px;">
                    <h3>${book.name}</h3>
                    <p class="author">by ${book.author}</p>
                    <p class="price" style="color: blue;">Venduto: $${parseFloat(book.price).toFixed(2)}</p>
                    <hr>
                    <p class="detail"><strong>Ordine #:</strong> ${item.orderId}</p>
                    <p class="detail"><strong>Data:</strong> ${date}</p>
                    <p class="detail"><strong>Acquirente:</strong> ${item.buyerUsername}</p>
                    
                     <div class="card-actions" style="margin-top: 10px;">
                        <!-- No action button needed for now -->
                    </div>
                </div>
            `;
            this.renderTarget.appendChild(card);
        });
    }

    async handleEditBook(book) {
        // Simple prompt for new price
        const newPriceStr = prompt("Inserisci il nuovo prezzo:", book.price);
        if (newPriceStr === null) return; // Cancelled
        
        const newPrice = parseFloat(newPriceStr);
        if (isNaN(newPrice) || newPrice <= 0) {
            alert("Prezzo non valido.");
            return;
        }

        try {
            const response = await ApiService.updateBook({
                id: book.id,
                price: newPrice,
                // We keep availability as is for now, or true if not tracked on UI
                available: book.available
            });

            if (response.status === 'success') {
                alert(response.message || "Libro aggiornato!");
                // Update local model and re-render
                // Find book and update price
                const bookIndex = this.books.findIndex(b => b.id === book.id);
                if (bookIndex !== -1) {
                    this.books[bookIndex].price = newPrice;
                    this.renderBooks();
                } else {
                    // Fallback re-fetch if not found (shouldn't happen)
                    this.fetchBooks();
                }
            } else {
                alert("Errore: " + (response.message || "Impossibile aggiornare il libro."));
            }
        } catch (error) {
            console.error(error);
            alert("Si è verificato un errore durante l'aggiornamento.");
        }
    }

    async handleBuyBook(bookId) {
        if (!confirm("Confermi l'acquisto del libro?")) return;

        try {
            const response = await ApiService.purchaseBook(bookId);
            if (response.status === 'success') {
                alert(response.message || "Acquisto completato con successo!");
                // Remove the book locally and re-render
                this.books = this.books.filter(b => b.id != bookId);
                this.renderBooks();
            } else {
                alert("Errore: " + (response.message || "Impossibile completare l'acquisto."));
            }
        } catch (error) {
            console.error(error);
            // Check if we have a JSON error response
             let msg = "Si è verificato un errore durante l'acquisto.";
            if (error.responseJSON && error.responseJSON.message) {
                msg = error.responseJSON.message;
            }
            alert(msg);
        }
    }

    async handleDeleteBook(bookId) {
        if (!confirm("Sei sicuro di voler eliminare questo libro?")) return;

        try {
            const response = await ApiService.deleteBook(bookId);
            if (response.status === 'success') {
                alert(response.message || "Libro eliminato!");
                // Refresh the current view
                // If we are in "My Books" mode, we should ideally re-fetch "My Books", 
                // but checking the current 'mode' is complex without extra state.
                // For now, re-fetching all books serves as a safe fallback or we could infer.
                // Let's re-trigger the last action if possible, or just fetch all logic.
                // A simpler approach: remove from this.books and re-render.
                this.books = this.books.filter(b => b.id != bookId);
                this.renderBooks(); 
            } else {
                alert("Errore: " + (response.message || "Impossibile eliminare il libro."));
            }
        } catch (error) {
            console.error(error);
            alert("Si è verificato un errore durante l'eliminazione.");
        }
    }

    handleLogout() {
        localStorage.removeItem('token');
        localStorage.removeItem('currentUser');
        window.location.href = '/login';
    }

    async handleInsertAd() {
        // Collect data
        const title = document.getElementById('bookTitle').value;
        const author = document.getElementById('bookAuthor').value;
        const course = document.getElementById('bookCourse').value;
        const teacher = document.getElementById('bookTeacher').value;
        const isbn = document.getElementById('bookIsbn').value; // New field
        const price = parseFloat(document.getElementById('bookPrice').value);
        const imageFile = document.getElementById('bookImageFile').files[0];

        // Simple validation
        if (!title || !author || !isbn || isNaN(price)) {
            this.showAdError("Compila tutti i campi obbligatori.");
            return;
        }

        // Convert image to Base64 if present
        let imageBase64 = null;
        if (imageFile) {
            try {
                imageBase64 = await this.toBase64(imageFile);
            } catch (e) {
                this.showAdError("Errore nella lettura del file.");
                return;
            }
        }

        const newBook = {
            name: title,
            author: author,
            isbn: isbn,
            teacher: teacher,
            course: course,
            price: price,
            image: imageBase64 // Contract: "image": "base64 string"
        };
        
        // Remove image if null? Contract says optional.
        if (!imageBase64) delete newBook.image;


        // Call backend via ApiService
        try {
            const response = await ApiService.createBook(newBook);
            
            if (response.status === 'success') {
                alert(response.message);
                const modalEl = document.getElementById('insertAdModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // Refresh list
                this.fetchBooks();
            } else {
                // If backend returns 200 OK with status='error'
                this.showAdError(response.message);
            }
        } catch (error) {
            console.error(error);
            // If backend returns 4xx/5xx with JSON
            let msg = "Error inserting book.";
            if (error.responseJSON && error.responseJSON.message) {
                msg = error.responseJSON.message;
            }
            this.showAdError(msg);
        }
    }

    toBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    showAdError(msg) {

        const el = document.getElementById('adAlertPlaceholder');
        if (el) el.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
    }


    showError(msg) {
        if (this.renderTarget) {
            this.renderTarget.innerHTML = `<p class="error-msg">${msg}</p>`;
        }
    }
}
