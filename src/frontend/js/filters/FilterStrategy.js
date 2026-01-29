/**
 * Base Strategy Interface for Filtering Books
 */
export class FilterStrategy {
    matches(book) {
        return true;
    }
}
