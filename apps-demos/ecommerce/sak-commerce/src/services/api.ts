import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL + '/api',
  withCredentials: true, // Required for Laravel Sanctum
});

// Separate instance for CSRF cookie, as it's not under /api
const sanctumApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
});

// Function to get CSRF token
export const getCsrfCookie = () => {
  console.log('Attempting to get CSRF cookie...');
  return sanctumApi.get('/sanctum/csrf-cookie').then(response => {
    console.log('CSRF cookie request successful.');
    return response;
  }).catch(error => {
    console.error('CSRF cookie request failed:', error);
    throw error;
  });
};

export const login = (credentials) => {
  return sanctumApi.post('/login', credentials);
};

// Log API requests and responses
api.interceptors.request.use(request => {
  console.log('API Request:', request);
  return request;
}, error => {
  console.error('API Request Error:', error);
  return Promise.reject(error);
});

api.interceptors.response.use(response => {
  console.log('API Response:', response);
  return response;
}, error => {
  console.error('API Response Error:', error.response || error);
  return Promise.reject(error);
});


export default api;