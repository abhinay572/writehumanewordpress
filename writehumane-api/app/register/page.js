'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Copy, CheckCircle, ArrowRight } from 'lucide-react';

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({ name: '', email: '', password: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [apiKey, setApiKey] = useState('');
  const [copied, setCopied] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const res = await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });
      const data = await res.json();

      if (!data.success) {
        setError(data.error);
        return;
      }

      setApiKey(data.api_key);
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  function copyKey() {
    navigator.clipboard.writeText(apiKey);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  if (apiKey) {
    return (
      <div className="min-h-screen flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-lg">
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
              <CheckCircle size={32} className="text-green-600" />
            </div>
            <h1 className="text-2xl font-bold">Account Created!</h1>
            <p className="text-gray-600 mt-2">Save your API key below. You&apos;ll need it for API access.</p>
          </div>

          <div className="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
            <label className="text-sm font-medium text-green-800 block mb-2">Your API Key</label>
            <div className="flex items-center gap-2">
              <code className="flex-1 bg-green-100 px-4 py-3 rounded-lg text-sm font-mono text-green-900 break-all">{apiKey}</code>
              <button onClick={copyKey} className="shrink-0 p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                {copied ? <CheckCircle size={18} /> : <Copy size={18} />}
              </button>
            </div>
          </div>

          <div className="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-6">
            <h3 className="font-semibold mb-3">WordPress Setup</h3>
            <ol className="text-sm text-gray-600 space-y-2">
              <li>1. Install the WriteHumane AI Humanizer plugin</li>
              <li>2. Go to Settings → AI Humanizer</li>
              <li>3. Select &quot;WriteHumane API&quot; as provider</li>
              <li>4. Paste your API key above</li>
              <li>5. Click &quot;Test Connection&quot; to verify</li>
            </ol>
          </div>

          <div className="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-6">
            <h3 className="font-semibold mb-3">Quick API Test</h3>
            <div className="bg-gray-900 rounded-lg p-4 text-sm font-mono text-green-400 overflow-x-auto">
              <pre>{`curl -X POST https://writehumane.com/api/v1/humanize \\
  -H "Authorization: Bearer ${apiKey}" \\
  -H "Content-Type: application/json" \\
  -d '{"text": "Test content here"}'`}</pre>
            </div>
          </div>

          <Link href="/dashboard" className="block w-full gradient-bg text-white text-center px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition">
            Go to Dashboard <ArrowRight size={18} className="inline ml-1" />
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <Link href="/" className="text-2xl font-bold gradient-text">WriteHumane</Link>
          <h1 className="text-2xl font-bold mt-4">Create your account</h1>
          <p className="text-gray-600 mt-2">Get 1,000 free words/month. No credit card required.</p>
        </div>

        <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 space-y-5">
          {error && <div className="bg-red-50 text-red-700 px-4 py-3 rounded-lg text-sm">{error}</div>}

          <div>
            <label className="block text-sm font-medium mb-1">Name (optional)</label>
            <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })}
              className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" placeholder="John Doe" />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Email</label>
            <input type="email" required value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })}
              className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" placeholder="you@example.com" />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Password</label>
            <input type="password" required minLength={8} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })}
              className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" placeholder="Min. 8 characters" />
          </div>

          <button type="submit" disabled={loading}
            className="w-full gradient-bg text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50">
            {loading ? 'Creating account...' : 'Create Account & Get API Key'}
          </button>

          <p className="text-center text-sm text-gray-600">
            Already have an account? <Link href="/login" className="text-indigo-600 font-medium hover:underline">Login</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
