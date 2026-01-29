import { FilterStrategy } from './FilterStrategy.js';

export class GeneralSearchFilter extends FilterStrategy {
    constructor(query) {
        super();
        this.query = query ? query.toLowerCase().trim() : '';
    }

    matches(book) {
        if (!this.query) return true;
        const text = (book.name + " " + book.author).toLowerCase();
        return text.includes(this.query);
    }
}
