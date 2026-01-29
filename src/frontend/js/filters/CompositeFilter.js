import { FilterStrategy } from './FilterStrategy.js';

export class CompositeFilter extends FilterStrategy {
    constructor(strategies = []) {
        super();
        this.strategies = strategies;
    }

    add(strategy) {
        this.strategies.push(strategy);
    }

    matches(book) {
        return this.strategies.every(strategy => strategy.matches(book));
    }
}
