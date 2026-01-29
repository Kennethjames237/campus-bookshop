export class ApiService {
    
    /**
     * generic AJAX helper using jQuery
     */
static async _send(method, endpoint, data = null) {
    const headers = {};
	const api_base_url = "http://localhost:8081";
    
    const token = localStorage.getItem('token');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    return $.ajax({
        url: api_base_url + endpoint,
        type: method,
        contentType: 'application/json',
        headers: headers, 
        data: data ? JSON.stringify(data) : null,
    });
}

    /**
     * Authenticate a user.
     * @param {string} email 
     * @param {string} password 
     * @returns {Promise<Object>}
     */
    static async login(email, password) {
        return this._send('POST', '/login', { email, password });
    }

    /**
     * Register a new user.
     * @param {User} user - User model instance or plain object
     * @returns {Promise<Object>}
     */
    static async register(user) {
        // Ensure we send plain JSON
        const userData = user.toJSON ? user.toJSON() : user;
        return this._send('POST', '/register', userData);
    }

    /**
     * Fetch books from the backend.
     * @param {Object} filters - Optional filters
     * @returns {Promise<Object>}
     */
    static async getBooks() {
        return this._send('GET', '/books');
    }

    /**
     * Create a new book advertisement.
     * @param {Object} bookData - The book object to create
     * @returns {Promise<Object>}
     */
    static async createBook(bookData) {
        return this._send('POST', '/books', bookData);
    }

    /**
     * Fetch user's own books.
     * @returns {Promise<Object>}
     */
    static async getMyBooks() {
        return this._send('GET', '/my-books');
    }

    /**
     * Delete a book.
     * @param {number} id 
     * @returns {Promise<Object>}
     */
    static async deleteBook(id) {
        return this._send('DELETE', '/books', { id });
    }

    /**
     * Update a book.
     * @param {Object} data - { id, price, available, ... }
     * @returns {Promise<Object>}
     */
    static async updateBook(data) {
        return this._send('PUT', '/books', data);
    }

    /**
     * Fetch purchase history.
     * @returns {Promise<Object>}
     */
    static async getPurchases() {
        return this._send('GET', '/purchases');
    }

    /**
     * Fetch sales history.
     * @returns {Promise<Object>}
     */
    static async getSales() {
        return this._send('GET', '/sales');
    }

    /**
     * Purchase a book.
     * @param {number} bookId 
     * @returns {Promise<Object>}
     */
    static async purchaseBook(bookId) {
        return this._send('POST', '/purchase', { bookId });
    }

    /**
     * Fetch user conversations.
     * @returns {Promise<Object>}
     */
    static async getConversations() {
        return this._send('GET', '/conversations');
    }

    /**
     * Send a message to a user.
     * @param {number} receiverId 
     * @param {string} content 
     * @returns {Promise<Object>}
     */
    static async sendMessage(receiverId, content) {
        return this._send('POST', '/messages', { receiverId, content });
    }

    /**
     * Get messages exchanged with a specific user.
     * @param {number} userId 
     * @returns {Promise<Object>}
     */
    static async getMessages(userId) {
        return this._send('GET', `/messages?userId=${userId}`);
    }
}
