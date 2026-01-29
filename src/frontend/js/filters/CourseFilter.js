import { FilterStrategy } from './FilterStrategy.js';

export class CourseFilter extends FilterStrategy {
    constructor(course) {
        super();
        this.course = course ? course.toLowerCase().trim() : '';
    }

    matches(book) {
        if (!this.course) return true;
        return book.course && book.course.toLowerCase().includes(this.course);
    }
}
