import path from 'node:path';
import dotenv from 'dotenv';

dotenv.config({ path: process.env.ENV_FILE ?? '.env' });

export const environment = {
  name: process.env.TEST_ENV ?? 'local',
  baseUrl: process.env.BASE_URL ?? 'http://127.0.0.1:8000',
  adminPath: process.env.ADMIN_PATH ?? '/admin',
  adminEmail: process.env.ADMIN_USERNAME ?? process.env.ADMIN_EMAIL ?? 'admin@example.com',
  adminPassword: process.env.ADMIN_PASSWORD ?? 'admin123',
  apiBaseUrl: process.env.API_BASE_URL ?? process.env.BASE_URL ?? 'http://127.0.0.1:8000',
  apiClientId: process.env.API_CLIENT_ID ?? '',
  apiClientSecret: process.env.API_CLIENT_SECRET ?? '',
  db: {
    host: process.env.DB_HOST ?? '127.0.0.1',
    port: Number(process.env.DB_PORT ?? 3306),
    database: process.env.DB_DATABASE ?? 'unopim',
    user: process.env.DB_USERNAME ?? 'root',
    password: process.env.DB_PASSWORD ?? ''
  },
  storageStatePath: path.resolve('reports/.auth/admin.json')
} as const;
