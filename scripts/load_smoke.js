import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: Number(__ENV.VUS || 10),
  duration: __ENV.DURATION || '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
  },
};

const targets = [
  { name: 'ecommerce_products', url: __ENV.ECOM_PRODUCTS_URL || 'https://apiecommerce.labarcaministerio.com/api/store/products' },
  { name: 'delivery_health', url: __ENV.DELIVERY_HEALTH_URL || 'https://apidelivery.labarcaministerio.com/api/health' },
  { name: 'dunamis_health', url: __ENV.DUNAMIS_HEALTH_URL || 'https://apidunamis.labarcaministerio.com/api/health' },
];

export default function () {
  for (const target of targets) {
    const res = http.get(target.url, { tags: { endpoint: target.name } });
    check(res, {
      'status is 200': (r) => r.status === 200,
    });
  }

  sleep(1);
}
