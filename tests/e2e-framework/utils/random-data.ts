export class RandomData {
  private readonly runId = `${Date.now()}${Math.floor(Math.random() * 1000)}`;

  code(prefix: string): string {
    return `${prefix}_${this.runId}`.toLowerCase();
  }

  email(prefix = 'qa'): string {
    return `${prefix}.${this.runId}@example.test`;
  }

  text(prefix: string): string {
    return `${prefix} ${this.runId}`;
  }

  sku(prefix = 'SKU'): string {
    return `${prefix}-${this.runId}`;
  }
}
