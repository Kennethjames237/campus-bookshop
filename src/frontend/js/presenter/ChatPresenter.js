import { ApiService } from './ApiService.js';

export class ChatPresenter {
    constructor() {
        this.activeConversationId = null;
        this.conversations = [];
        this.messages = {}; 
        this.currentUser = JSON.parse(localStorage.getItem('currentUser')) || {};
        this.init();
    }

    async init() {
        this.setupAuth();
        await this.fetchConversations();
        
        // Handle Deep Linking
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('userId');
        
        if (userId) {
            // Check if conversation exists
            const existingConv = this.conversations.find(c => String(c.userId) === String(userId));
            
            if (existingConv) {
                this.selectConversation(existingConv.userId, existingConv.username);
            } else {
                // New conversation or not in list
                // Use a fallback username since we don't have it in URL
                this.selectConversation(userId, `Utente ${userId}`);
            }
        }
    }

    async fetchConversations() {
        try {
            const response = await ApiService.getConversations();
            if (response.status === 'success') {
                this.conversations = response.data;
                this.renderConversations();
            } else {
                console.error("Failed to load conversations:", response.message);
                this.showError("Impossibile caricare le conversazioni.");
            }
        } catch (error) {
            console.error("Error fetching conversations:", error);
            this.showError("Errore di connessione.");
        }
    }

    renderConversations() {
        const listEl = document.getElementById('conversation-list');
        if (!listEl) return;

        listEl.innerHTML = '';

        if (this.conversations.length === 0) {
            listEl.innerHTML = '<div class="text-center p-3 text-muted">Nessuna conversazione.</div>';
            return;
        }
        
        this.conversations.forEach(conv => {
            const isActive = conv.userId === this.activeConversationId ? 'active' : '';
            // Contract: userId, username, lastMessage, lastMessageDate
            
            // Format time
            const dateObj = new Date(conv.lastMessageDate.date);
	    const today = new Date();
	    const isToday = dateObj.getDate() === today.getDate() &&
                dateObj.getMonth() === today.getMonth() &&
                dateObj.getFullYear() === today.getFullYear();

            const timeStr = isToday
               ? dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) // 14:30
               : dateObj.toLocaleDateString([], {day: '2-digit', month: '2-digit'}); // 28/01
		console.log(conv.lastMessageDate);

            const item = document.createElement('a');
            item.className = `list-group-item list-group-item-action conversation-item d-flex justify-content-between align-items-center ${isActive}`;
            item.setAttribute('data-id', conv.username);
            item.innerHTML = `
                <div class="d-flex align-items-center">
                     <!-- Placeholder avatar for now since contract doesn't explicitly return one yet -->
                    <img src="../img/profile.png" alt="${conv.username}" class="rounded-circle me-3" style="width: 40px; height: 40px;">
                    <div>
                        <h6 class="mb-0">${conv.username}</h6>
                        <small class="text-muted">${conv.lastMessage}</small>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end">
                    <small class="text-muted mb-1">${timeStr}</small>
                </div>
            `;
            
            item.addEventListener('click', () => this.selectConversation(conv.userId, conv.username));
            listEl.appendChild(item);
        });
    }

    selectConversation(userId, username) {
        this.activeConversationId = userId;
        this.renderConversations(); // Re-render to update active state
        this.renderMessages(userId, username);
    }
    
    async renderMessages(conversationId, username) {
        const headerEl = document.getElementById('chat-header');
        const messagesEl = document.getElementById('messages-area');
        const inputArea = document.getElementById('input-area');
        
        // Update Header
        headerEl.innerHTML = `
            <img src="../img/profile.png" alt="${username}" class="rounded-circle me-2" style="width: 35px; height: 35px;">
            <h6 class="mb-0">${username}</h6>
        `;

        // Clear and show loading
        messagesEl.innerHTML = '<div class="text-center mt-5 text-muted">Caricamento messaggi...</div>';
        
        try {
            const response = await ApiService.getMessages(conversationId);
            messagesEl.innerHTML = ''; // Clear loading

            if (response.status === 'success') {
                const messages = response.data;
                if (messages.length === 0) {
                     messagesEl.innerHTML = '<div class="text-center mt-5 text-muted">Nessun messaggio. Scrivi il primo!</div>';
                } else {
                    messages.forEach(msg => {
                      
                        const isMe = String(msg.senderId) === String(this.currentUser.id);
			    console.log(this.currentUser.id);
                        const msgClass = isMe ? 'message-sent' : 'message-received';
                        
                        const timeStr = new Date(msg.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                        const msgHtml = `
                            <div class="message-row">
                                <div class="message-bubble ${msgClass}">
                                    ${msg.content}
                                    <div class="message-time">${timeStr}</div>
                                </div>
                            </div>
                        `;
                        messagesEl.insertAdjacentHTML('beforeend', msgHtml);
                    });
                }
                messagesEl.scrollTop = messagesEl.scrollHeight;
            } else {
                 messagesEl.innerHTML = `<div class="text-center mt-5 text-danger">${response.message || 'Errore nel caricamento'}</div>`;
            }

        } catch (error) {
            console.error("Load messages error:", error);
            messagesEl.innerHTML = '<div class="text-center mt-5 text-danger">Errore di connessione.</div>';
        }

        // Show Input
        inputArea.style.display = 'block';

        // Re-attach listener for this conversation
        this.attachMessageListener();
    }
    
    attachMessageListener() {
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message-input');

        // Remove old listeners to avoid duplicates if possible, or handle via single attachment in init
        // For simplicity:
        form.onsubmit = (e) => {
            e.preventDefault();
            this.sendMessage(input.value);
            input.value = '';
        };
    }

    async sendMessage(text) {
         if (!this.activeConversationId || !text.trim()) return;
         
         try {
             const response = await ApiService.sendMessage(this.activeConversationId, text);
             if (response.status === 'success') {
                 // Append message to UI
                 const messagesEl = document.getElementById('messages-area');
                 const msgHtml = `
                    <div class="message-row">
                        <div class="message-bubble message-sent">
                            ${text}
                            <div class="message-time">Adesso</div>
                        </div>
                    </div>
                `;
                messagesEl.insertAdjacentHTML('beforeend', msgHtml);
                messagesEl.scrollTop = messagesEl.scrollHeight;
             } else {
                 alert(response.message || "Errore nell'invio del messaggio");
             }
         } catch (error) {
             console.error("Send error:", error);
             alert("Errore di connessione.");
         }
    }

    showError(msg) {
        const listEl = document.getElementById('conversation-list');
        if (listEl) listEl.innerHTML = `<div class="text-center p-3 text-danger">${msg}</div>`;
    }

    setupAuth() {
        const authWrapper = document.getElementById('auth-wrapper');
        const token = localStorage.getItem('token');
        
        if (token) {
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
            // Attach Logout
             document.getElementById('logoutBtn').addEventListener('click', () => {
                 localStorage.removeItem('token');
                 localStorage.removeItem('currentUser');
                 window.location.href = '/login';
             });
        } else {
             window.location.href = '/login'; // Redirect if not logged in
        }
    }
}

// Init
new ChatPresenter();
