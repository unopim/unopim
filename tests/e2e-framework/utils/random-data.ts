export class RandomData {
  private readonly runId = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  private seq = 0;

  /** Unique per call, so two code()/sku() calls in one test never collide. */
  private unique(): string {
    return `${this.runId}_${++this.seq}`;
  }

  code(prefix: string): string {
    return `${prefix}_${this.unique()}`.toLowerCase();
  }

  email(prefix = 'qa'): string {
    return `${prefix}.${this.unique()}@example.test`;
  }

  text(prefix: string): string {
    return `${prefix} ${this.unique()}`;
  }

  sku(prefix = 'SKU'): string {
    return `${prefix}-${this.unique()}`;
  }
}
