/**
 * Centralized API test configuration.
 *
 * All URLs and defaults derive from env variables with sane fallbacks so the
 * suite can run unmodified against `127.0.0.1:8000` (the default `php artisan
 * serve` target) or any CI environment that exports `BASE_URL`.
 *
 * Precedence (high → low):
 *   1. Explicit env var (API_BASE_URL, API_CLIENT_ID, ...)
 *   2. `tests/e2e-pw/.api-config.json` (UI-generated integration credentials)
 *   3. Hard-coded fallback (admin@example.com / admin123)
 */
'use strict';

const path = require('path');
const { getCredentials } = require('../api-config');

const RAW_BASE = process.env.API_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8000';
const BASE_URL = RAW_BASE.replace(/\/$/, '');

const REST_ROOT = `${BASE_URL}/api/v1/rest`;
const OAUTH_URL = `${BASE_URL}/oauth/token`;

/**
 * Maximum acceptable response time per assertion class (ms).
 *
 * - `fast`   : single-resource GETs (e.g. /locales/{code})
 * - `list`   : paginated lists with light joins (locales, currencies, channels,
 *              categories, category-fields, attribute-groups, configurables)
 * - `heavy`  : paginated lists with large datasets or heavy joins
 *              (products, attributes) — these can run into multi-second
 *              latencies under concurrent worker load on a production-sized DB
 * - `write`  : POST/PUT/PATCH/DELETE
 * - `upload` : multipart media upload, plus the very slowest reads
 *              (/families with its nested groups+custom_attributes joins)
 */
const RESPONSE_TIME = {
  fast: 1500,
  list: 3000,
  heavy: 8000,
  write: 5000,
  upload: 15000,
};

/** Known seed data on a fresh UnoPim install. Override via env if your env differs. */
const SEED = {
  rootCategory: process.env.API_ROOT_CATEGORY || 'root',
  defaultLocale: process.env.API_DEFAULT_LOCALE || 'en_US',
  secondaryLocale: process.env.API_SECONDARY_LOCALE || 'fr_FR',
  defaultChannel: process.env.API_DEFAULT_CHANNEL || 'default',
  defaultCurrency: process.env.API_DEFAULT_CURRENCY || 'USD',
  defaultFamily: process.env.API_DEFAULT_FAMILY || 'default',
};

/** Static file paths used by media-upload tests. */
const MEDIA_FIXTURES = {
  jpegSmall: path.resolve(__dirname, '../berlin.jpeg'),
  jpegMedium: path.resolve(__dirname, '../bikes.jpeg'),
  jpegLarge: path.resolve(__dirname, '../laptop.jpeg'),
};

module.exports = {
  BASE_URL,
  REST_ROOT,
  OAUTH_URL,
  RESPONSE_TIME,
  SEED,
  MEDIA_FIXTURES,
  getCredentials,
};
