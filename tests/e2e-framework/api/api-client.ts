import { expect } from '@playwright/test';
import type { APIRequestContext } from '@playwright/test';
import { environment } from '../config/environment';

export class ApiClient {
  private token?: string;

  constructor(private readonly request: APIRequestContext) {}

  async authenticate(): Promise<string | null> {
    if (this.token) {
      return this.token;
    }

    if (!environment.apiClientId || !environment.apiClientSecret) {
      return null;
    }

    const response = await this.request.post(`${environment.apiBaseUrl}/oauth/token`, {
      headers: {
        Authorization: `Basic ${Buffer.from(`${environment.apiClientId}:${environment.apiClientSecret}`).toString('base64')}`,
        Accept: 'application/json'
      },
      data: {
        grant_type: 'password',
        username: environment.adminEmail,
        password: environment.adminPassword
      }
    });

    if (!response.ok()) {
      return null;
    }

    const body = (await response.json()) as { access_token: string };
    this.token = body.access_token;
    return this.token;
  }

  async get(path: string) {
    const token = await this.authenticate();
    expect(token, 'API credentials are required for API validation tests').toBeTruthy();

    return this.request.get(`${environment.apiBaseUrl}${path}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
    });
  }

  async post(path: string, data: unknown) {
    const token = await this.authenticate();
    expect(token, 'API credentials are required for API validation tests').toBeTruthy();

    return this.request.post(`${environment.apiBaseUrl}${path}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data
    });
  }
}
