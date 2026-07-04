import path from 'node:path';
import fs from 'node:fs/promises';
import { BrowserContext } from '@playwright/test';

export class TraceUtility {
  constructor(private readonly context: BrowserContext) {}

  async start(name: string): Promise<void> {
    await this.context.tracing.start({
      name,
      screenshots: true,
      snapshots: true,
      sources: true
    });
  }

  async stop(name: string): Promise<void> {
    const destination = path.resolve('reports/traces', `${name}.zip`);
    await fs.mkdir(path.dirname(destination), { recursive: true });
    await this.context.tracing.stop({
      path: destination
    });
  }
}
