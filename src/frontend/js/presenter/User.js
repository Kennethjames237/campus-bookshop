export class User {
    /**
     * @param {string} username 
     * @param {string} email 
     * @param {string} password 
     */
    constructor(username, email, password) {
        this.username = username;
        this.email = email;
        this.password = password;
    }

    /**
     * Converts the user instance to a plain object for JSON serialization.
     * @returns {Object}
     */
    toJSON() {
        return {
            username: this.username,
            email: this.email,
            password: this.password
        };
    }
}
