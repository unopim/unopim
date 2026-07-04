import mysql, { Pool } from 'mysql2/promise';
import { environment } from '../config/environment';

export class DatabaseHelper {
  private pool?: Pool;

  private get connection(): Pool {
    this.pool ??= mysql.createPool({
      host: environment.db.host,
      port: environment.db.port,
      database: environment.db.database,
      user: environment.db.user,
      password: environment.db.password,
      waitForConnections: true,
      connectionLimit: 4
    });

    return this.pool;
  }

  async count(table: string, where: Record<string, string | number | boolean> = {}): Promise<number> {
    const keys = Object.keys(where);
    const clause = keys.length ? ` where ${keys.map((key) => `\`${key}\` = ?`).join(' and ')}` : '';
    const values = keys.map((key) => where[key]);
    const [rows] = await this.connection.query(`select count(*) as total from \`${table}\`${clause}`, values);
    return Number((rows as Array<{ total: number }>)[0].total);
  }

  async close(): Promise<void> {
    await this.pool?.end();
  }
}
