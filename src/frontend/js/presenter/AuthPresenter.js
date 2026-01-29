import { ApiService } from './ApiService.js';
import { User } from './User.js';

export class AuthPresenter {
    constructor() {
        this.loginForm = document.getElementById('loginForm');
        this.registerForm = document.getElementById('registerForm');
        this.alertPlaceholder = document.getElementById('alertPlaceholder');

        this.init();
    }

    init() {
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (this.registerForm) {
            this.registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }
    }

    showAlert(message, type) {
        if (!this.alertPlaceholder) return;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        
        this.alertPlaceholder.innerHTML = ''; // Clear previous alerts
        this.alertPlaceholder.append(wrapper);
    }

    async handleLogin(event) {
        event.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const response = await ApiService.login(email, password);
             // Contract allows { status: "success" } or similar.
             // We check logic loosely or strictly as per previous impl.
	     // console.log("Response: ", JSON.stringify(response));
             if(response.status === 'success') {
                 // Save user to session (simulate JWT result)
                localStorage.setItem('token', response.token);
                 const payload = this.parseJwt(response.token);
                const userObj = {
                    id: payload.sub || payload.id,
                    username: payload.username
                };
                localStorage.setItem('currentUser', JSON.stringify(userObj));
	        this.showAlert(`Login effettuato! Benvenuto`, 'success');
                 // this.showAlert(`Login effettuato! Benvenuto ${response.user.username}`, 'success');
                 setTimeout(() => {
                    window.location.href = '/books'; 
                 }, 1500);
             } else {
                 this.showAlert(response.message, 'danger');
             }


        } catch (error) {
            console.error(error);
            // Strict requirement: take error from response
            let msg = "An unexpected error occurred.";
            if (error.responseJSON && error.responseJSON.message) {
                msg = error.responseJSON.message;
            } else if (error.responseText) {
                 // Sometimes it might not be JSON (e.g. fatal PHP error HTML), but we try
                 try {
                     const parsed = JSON.parse(error.responseText);
                     if (parsed.message) msg = parsed.message;
                 } catch(e) {
                     // If parsing fails, use generic or limited text
                     msg = "Server Error (Non-JSON response)";
                 }
            }
            this.showAlert(msg, 'danger');
        }
    }

    async handleRegister(event) {
        event.preventDefault();
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const newUser = new User(username, email, password);
            
            const response = await ApiService.register(newUser);
            
            if (response.status === "success") {
                this.showAlert(response.message, 'success');
                setTimeout(() => {
                    window.location.href = '/login';
                }, 1500);
            } else {
                // Backend sent { status: 'error', message: '...' }
                 this.showAlert(response.message, 'danger');
            }

        } catch (error) {
            console.error("AJAX Error:", error);
            let msg = "Registration failed.";
            if (error.responseJSON && error.responseJSON.message) {
                msg = error.responseJSON.message;
            }
            this.showAlert(msg, 'danger');
        }
    }

    parseJwt(token) {
    	if (!token) return null;
    	try {
        	const base64Url = token.split('.')[1];
        	const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        	const jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
            	return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        	return JSON.parse(jsonPayload);
    	} catch (e) {
        	return null;
    	}
    }

    
}
