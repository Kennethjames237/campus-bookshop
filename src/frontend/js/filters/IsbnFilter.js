import { FilterStrategy } from './FilterStrategy.js';

export class IsbnFilter extends FilterStrategy {
    constructor(isbn) {
        super();
        this.isbn = isbn ? isbn.toLowerCase().trim() : '';
    }

    matches(book) {
        if (!this.isbn) return true;
        // Basic partial match, can be strict if needed
        return book.isbn && book.isbn.toLowerCase().includes(this.isbn);
    }
}
