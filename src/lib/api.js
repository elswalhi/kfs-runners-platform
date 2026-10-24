const API_BASE = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');

export const isApiConfigured = () => Boolean(API_BASE);

export async function getPublicRaces() {
  if (!API_BASE) return null;
  const response = await fetch(`${API_BASE}/api/races`, { headers: { Accept: 'application/json' } });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(result.message || `Could not load race options (${response.status}).`);
  if (!Array.isArray(result.data)) throw new Error('The races endpoint returned an unexpected response.');
  return result.data;
}

/**
 * API boundary for the Laravel backend.
 * The front end intentionally does not simulate successful payment.
 */
export async function submitRegistration(formData) {
  if (!API_BASE) {
    return {
      connected: false,
      message: 'Front-end preview only. Set VITE_API_BASE_URL and connect the Laravel registration/payment endpoint to submit live registrations.',
    };
  }

  const response = await fetch(`${API_BASE}/api/registrations`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: formData,
  });

  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message = result.message || 'Registration could not be submitted. Please check your details and try again.';
    throw new Error(message);
  }

  return { connected: true, ...result };
}
