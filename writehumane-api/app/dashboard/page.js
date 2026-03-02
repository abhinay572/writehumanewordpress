'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Copy, CheckCircle, Trash2, Plus, Key, BarChart3, Clock, FileText } from 'lucide-react';

export default function DashboardPage() {
  const router = useRouter();
  const [usage, setUsage] = useState(null);
  const [keys, setKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const [newKeyName, setNewKeyName] = useState('');
  const [copiedId, setCopiedId] = useState('');
  const [creating, setCreating] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    try {
      const [usageRes, keysRes] = await Promise.all([
        fetch('/api/v1/usage'),
        fetch('/api/v1/auth/api-key'),
      ]);

      if (usageRes.status === 401 || keysRes.status === 401) {
        router.push('/login');
        return;
      }

      const usageData = await usageRes.json();
      const keysData = await keysRes.json();

      if (usageData.success) setUsage(usageData);
      if (keysData.success) setKeys(keysData.api_keys);
    } catch {
      router.push('/login');
    } finally {
      setLoading(false);
    }
  }

  async function createKey() {
    setCreating(true);
    try {
      const res = await fetch('/api/v1/auth/api-key', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: newKeyName || undefined }),
      });
      const data = await res.json();
      if (data.success) {
        setNewKeyName('');
        loadData();
      }
    } finally {
      setCreating(false);
    }
  }

  async function revokeKey(id) {
    if (!confirm('Revoke this API key? This cannot be undone.')) return;
    await fetch(`/api/v1/auth/api-key?id=${id}`, { method: 'DELETE' });
    loadData();
  }

  function copyToClipboard(text, id) {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(''), 2000);
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  const usedPercent = usage ? Math.min(100, (usage.usage.wordsUsedThisMonth / usage.usage.monthlyLimit) * 100) : 0;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
          <Link href="/" className="text-xl font-bold gradient-text">WriteHumane</Link>
          <div className="flex items-center gap-4">
            <Link href="/pricing" className="text-sm text-gray-600 hover:text-gray-900">Upgrade</Link>
            <span className="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium">{usage?.plan?.name || 'Free'}</span>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-2xl font-bold mb-8">Dashboard</h1>

        {/* Stats Cards */}
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-xl border border-gray-200 p-6 relative overflow-hidden">
            <div className="absolute inset-0 gradient-bg opacity-5"></div>
            <div className="relative">
              <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                <BarChart3 size={16} /> Words This Month
              </div>
              <div className="text-3xl font-bold gradient-text">{(usage?.usage?.wordsUsedThisMonth || 0).toLocaleString()}</div>
              <div className="mt-3 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div className="gradient-bg h-full rounded-full transition-all duration-500" style={{ width: `${usedPercent}%` }}></div>
              </div>
              <div className="text-xs text-gray-500 mt-1">{usedPercent.toFixed(0)}% used</div>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
              <FileText size={16} /> Monthly Limit
            </div>
            <div className="text-3xl font-bold">{(usage?.usage?.monthlyLimit || 0).toLocaleString()}</div>
            <div className="text-sm text-gray-500 mt-1">{usage?.plan?.name} plan</div>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
              <Clock size={16} /> Total Requests
            </div>
            <div className="text-3xl font-bold">{(usage?.totals?.totalRequests || 0).toLocaleString()}</div>
            <div className="text-sm text-gray-500 mt-1">All time</div>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
              <BarChart3 size={16} /> Total Words
            </div>
            <div className="text-3xl font-bold">{(usage?.totals?.totalInputWords || 0).toLocaleString()}</div>
            <div className="text-sm text-gray-500 mt-1">All time processed</div>
          </div>
        </div>

        {/* API Keys */}
        <div className="bg-white rounded-xl border border-gray-200 p-6 mb-8">
          <h2 className="text-lg font-semibold mb-4 flex items-center gap-2"><Key size={20} /> API Keys</h2>

          <div className="space-y-3 mb-4">
            {keys.map((k) => (
              <div key={k.id} className={`flex items-center gap-3 p-3 rounded-lg border ${k.isActive ? 'border-gray-200 bg-gray-50' : 'border-red-200 bg-red-50 opacity-60'}`}>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-medium">{k.name}</div>
                  <code className="text-xs text-gray-500 break-all">{k.key_masked}</code>
                </div>
                <button onClick={() => copyToClipboard(k.key, k.id)}
                  className="shrink-0 p-2 text-gray-400 hover:text-gray-700 transition" title="Copy full key">
                  {copiedId === k.id ? <CheckCircle size={16} className="text-green-600" /> : <Copy size={16} />}
                </button>
                {k.isActive && (
                  <button onClick={() => revokeKey(k.id)}
                    className="shrink-0 p-2 text-gray-400 hover:text-red-600 transition" title="Revoke key">
                    <Trash2 size={16} />
                  </button>
                )}
                {!k.isActive && <span className="text-xs text-red-600 font-medium">Revoked</span>}
              </div>
            ))}
          </div>

          <div className="flex gap-2">
            <input type="text" placeholder="Key name (optional)" value={newKeyName}
              onChange={(e) => setNewKeyName(e.target.value)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
            <button onClick={createKey} disabled={creating}
              className="gradient-bg text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center gap-1">
              <Plus size={16} /> Create Key
            </button>
          </div>
        </div>

        {/* Recent Requests */}
        <div className="bg-white rounded-xl border border-gray-200 p-6 mb-8">
          <h2 className="text-lg font-semibold mb-4">Recent Requests</h2>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Time</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Words In</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Words Out</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Mode</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Provider</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Latency</th>
                  <th className="text-left py-2 px-3 font-medium text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {(usage?.recent_requests || []).map((r) => (
                  <tr key={r.id}>
                    <td className="py-2 px-3 text-gray-500">{new Date(r.createdAt).toLocaleString()}</td>
                    <td className="py-2 px-3">{r.inputWords}</td>
                    <td className="py-2 px-3">{r.outputWords}</td>
                    <td className="py-2 px-3 capitalize">{r.mode}</td>
                    <td className="py-2 px-3 capitalize">{r.provider}</td>
                    <td className="py-2 px-3">{r.latencyMs ? `${(r.latencyMs / 1000).toFixed(1)}s` : '-'}</td>
                    <td className="py-2 px-3">
                      {r.success
                        ? <span className="text-green-600 font-medium">OK</span>
                        : <span className="text-red-600 font-medium">Error</span>}
                    </td>
                  </tr>
                ))}
                {(!usage?.recent_requests || usage.recent_requests.length === 0) && (
                  <tr><td colSpan={7} className="py-8 text-center text-gray-400">No requests yet. Make your first API call!</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* WordPress Setup */}
        <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-6">
          <h2 className="text-lg font-semibold mb-3">WordPress Setup Guide</h2>
          <ol className="text-sm text-gray-700 space-y-2">
            <li><strong>1.</strong> Download and install the WriteHumane AI Humanizer plugin (.zip)</li>
            <li><strong>2.</strong> Go to <strong>Settings → AI Humanizer</strong> in your WordPress admin</li>
            <li><strong>3.</strong> Select <strong>WriteHumane API</strong> as the provider</li>
            <li><strong>4.</strong> Enter your API URL: <code className="bg-indigo-100 px-2 py-0.5 rounded">https://writehumane.com</code></li>
            <li><strong>5.</strong> Paste your API key and click <strong>Test Connection</strong></li>
            <li><strong>6.</strong> Start humanizing content in the Gutenberg or Classic Editor!</li>
          </ol>
        </div>
      </div>
    </div>
  );
}
