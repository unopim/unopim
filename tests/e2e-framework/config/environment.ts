import path from 'node:path';
import dotenv from 'dotenv';

dotenv.config({ path: process.env.ENV_FILE ?? '.env' });

const normalizeBaseUrl = (value: string | undefined): string => {
  const candidate = value?.trim() || 'http://127.0.0.1:8000';
  return candidate.replace(/\/+$/, '');
};

const normalizeAdminPath = (value: string | undefined): string => {
  const candidate = value?.trim() || '/admin';
  return `/${candidate.replace(/^\/+|\/+$/g, '')}`;
};

export const environment = {
  name: process.env.TEST_ENV ?? 'local',
  baseUrl: normalizeBaseUrl(process.env.BASE_URL),
  adminPath: normalizeAdminPath(process.env.ADMIN_PATH),
  adminEmail: process.env.ADMIN_USERNAME ?? process.env.ADMIN_EMAIL ?? 'admin@example.com',
  adminPassword: process.env.ADMIN_PASSWORD ?? 'admin123',
  apiBaseUrl: normalizeBaseUrl(process.env.API_BASE_URL ?? process.env.BASE_URL),
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
