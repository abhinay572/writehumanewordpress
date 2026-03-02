/**
 * Simple API key auth for the backend.
 * Plugin sends WHAH_BACKEND_KEY with every request.
 * Admin endpoints require ADMIN_SECRET.
 */

export function verifyPluginKey(request) {
  const authHeader = request.headers.get('authorization') || '';
  const key = authHeader.replace('Bearer ', '');
  return key === process.env.PLUGIN_API_KEY;
}

export function verifyAdminKey(request) {
  const authHeader = request.headers.get('authorization') || '';
  const key = authHeader.replace('Bearer ', '');
  return key === process.env.ADMIN_SECRET;
}
