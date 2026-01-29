import { FilterStrategy } from './FilterStrategy.js';

export class TeacherFilter extends FilterStrategy {
    constructor(teacher) {
        super();
        this.teacher = teacher ? teacher.toLowerCase().trim() : '';
    }

    matches(book) {
        if (!this.teacher) return true;
        return book.teacher && book.teacher.toLowerCase().includes(this.teacher);
    }
}
