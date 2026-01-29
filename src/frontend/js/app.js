import { AuthPresenter } from './presenter/AuthPresenter.js';
import { BooksPresenter } from './presenter/BooksPresenter.js';


/*
 * Entry point of the application.
 */
document.addEventListener('DOMContentLoaded', () => {

    // If we are on an auth page, initialize the AuthPresenter
    if (document.getElementById('loginForm') || document.getElementById('registerForm')) {
        new AuthPresenter();
    } else if (document.getElementById('books-container')) {
        new BooksPresenter();
    }

    
    console.log("App Initialized (MVP + jQuery AJAX Mock).");
});
