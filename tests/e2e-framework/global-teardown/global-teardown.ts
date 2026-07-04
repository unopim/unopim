import { logger } from '../utils/logger';

export default async function globalTeardown(): Promise<void> {
  logger.info('UnoPim Playwright framework run completed');
}
