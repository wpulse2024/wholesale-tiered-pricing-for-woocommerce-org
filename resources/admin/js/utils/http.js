// src/services/api.js

// Base URL and nonce injected from WordPress
const API_BASE = window.whtproleTieredPricingVar?.ajaxUrl;
const API_NONCE = window.whtproleTieredPricingVar?.nonce;

// Generic request wrapper using fetch
async function request(endpoint, method = 'GET', data = null) {
  const options = {
    method,
    headers: {
      'X-WP-Nonce': API_NONCE,
      'Content-Type': 'application/json'
    }
  };

  if (data) {
    options.body = JSON.stringify(data);
  }

  const response = await fetch(`${API_BASE}${endpoint}`, options);

  if (!response.ok) {
    throw new Error(`API error: ${response.status}`);
  }

  return await response.json();
}

// Export CRUD helpers
export default {
  get(endpoint) {
    return request(endpoint, 'GET');
  },
  post(endpoint, data) {
    return request(endpoint, 'POST', data);
  },
  put(endpoint, data) {
    return request(endpoint, 'PUT', data);
  },
  delete(endpoint) {
    return request(endpoint, 'DELETE');
  }
};
