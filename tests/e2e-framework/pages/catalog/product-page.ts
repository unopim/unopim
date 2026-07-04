import { Page } from '@playwright/test';
import { environment } from '../../config/environment';
import { CrudPage } from '../shared/crud-page';

export class ProductPage extends CrudPage {
  constructor(page: Page) {
    super(page);
  }

  async open(): Promise<void> {
    await this.goto(`${environment.adminPath}/catalog/products`);
  }
}
