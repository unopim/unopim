import { environment } from '../config/environment';

export class EnvironmentUtility {
  static isCI(): boolean {
    return Boolean(process.env.CI);
  }

  static baseUrl(): string {
    return environment.baseUrl;
  }

  static adminUrl(path = ''): string {
    return `${environment.baseUrl}${environment.adminPath}${path}`;
  }
}
