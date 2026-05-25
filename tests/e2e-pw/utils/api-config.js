'use strict';

const fs = require('fs');
const path = require('path');

const CONFIG_PATH = path.resolve(__dirname, '../.api-config.json');

function loadConfigFile() {
  if (!fs.existsSync(CONFIG_PATH)) {
    return {};
  }

  try {
    const raw = fs.readFileSync(CONFIG_PATH, 'utf8');
    return JSON.parse(raw);
  } catch (error) {
    console.warn(`Unable to read API config file at ${CONFIG_PATH}:`, error.message);
    return {};
  }
}

function getCredentials() {
  const fileConfig = loadConfigFile();

  return {
    client_id: process.env.API_CLIENT_ID || fileConfig.client_id || process.env.CLIENT_ID,
    client_secret:
      process.env.API_CLIENT_SECRET || fileConfig.client_secret || process.env.CLIENT_SECRET,
    username: process.env.API_USERNAME || fileConfig.username || process.env.ADMIN_EMAIL || process.env.ADMIN_USERNAME,
    password: process.env.API_PASSWORD || fileConfig.password || process.env.ADMIN_PASSWORD,
  };
}

module.exports = { getCredentials };
