'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Zap, Shield, Globe, Search, CreditCard, Clock, Menu, X, Code, CheckCircle, ArrowRight } from 'lucide-react';

function Navbar() {
  const [open, setOpen] = useState(false);
  return (
    <nav className="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md border-b border-gray-200 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <Link href="/" className="text-xl font-bold gradient-text">WriteHumane</Link>
          <div className="hidden md:flex items-center gap-6">
            <a href="#features" className="text-gray-600 hover:text-gray-900 text-sm">Features</a>
            <Link href="/pricing" className="text-gray-600 hover:text-gray-900 text-sm">Pricing</Link>
            <a href="#api-docs" className="text-gray-600 hover:text-gray-900 text-sm">API Docs</a>
            <Link href="/login" className="text-gray-600 hover:text-gray-900 text-sm">Login</Link>
            <Link href="/register" className="gradient-bg text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition">Get Free API Key</Link>
          </div>
          <button className="md:hidden" onClick={() => setOpen(!open)}>
            {open ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>
        {open && (
          <div className="md:hidden pb-4 space-y-2">
            <a href="#features" className="block px-3 py-2 text-gray-600">Features</a>
            <Link href="/pricing" className="block px-3 py-2 text-gray-600">Pricing</Link>
            <a href="#api-docs" className="block px-3 py-2 text-gray-600">API Docs</a>
            <Link href="/login" className="block px-3 py-2 text-gray-600">Login</Link>
            <Link href="/register" className="block px-3 py-2 gradient-bg text-white rounded-lg text-center">Get Free API Key</Link>
          </div>
        )}
      </div>
    </nav>
  );
}

const features = [
  { icon: Code, title: 'One API Endpoint', desc: 'Single POST request to humanize any text. Simple integration with any language or platform.' },
  { icon: Shield, title: 'Passes Every Detector', desc: 'Defeats GPTZero, Originality.ai, Copyleaks, Turnitin, and ZeroGPT consistently.' },
  { icon: Globe, title: 'WordPress Plugin', desc: 'Free plugin with Gutenberg, Classic Editor, and shortcode support. One-click humanization.' },
  { icon: Search, title: 'SEO Preserved', desc: 'Keywords, key phrases, and semantic meaning stay intact. Your rankings are safe.' },
  { icon: CreditCard, title: 'Pay Per Word', desc: 'Start free with 1,000 words/month. Scale to unlimited. No hidden costs or surprises.' },
  { icon: Clock, title: 'Fast & Reliable', desc: 'Average response in 3-8 seconds. 99.9% uptime backed by Vercel edge infrastructure.' },
];

export default function HomePage() {
  return (
    <div className="min-h-screen">
      <Navbar />

      {/* Hero */}
      <section className="pt-32 pb-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <div className="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-sm font-medium mb-6">
                <Zap size={14} /> API-first AI humanizer
              </div>
              <h1 className="text-5xl lg:text-6xl font-bold leading-tight mb-6">
                Make AI Content<br />
                <span className="gradient-text">Sound Human</span>
              </h1>
              <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                One API call transforms AI-generated text into naturally human content that passes every detector. Built for developers, agencies, and content teams.
              </p>
              <div className="flex flex-wrap gap-4">
                <Link href="/register" className="gradient-bg text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition inline-flex items-center gap-2">
                  Get Free API Key <ArrowRight size={18} />
                </Link>
                <a href="#api-docs" className="border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                  View API Docs
                </a>
              </div>
              <p className="text-sm text-gray-500 mt-4">1,000 free words/month. No credit card required.</p>
            </div>
            <div className="bg-gray-900 rounded-2xl p-6 text-sm font-mono shadow-2xl">
              <div className="flex gap-2 mb-4">
                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div className="w-3 h-3 rounded-full bg-green-500"></div>
              </div>
              <pre className="text-green-400 overflow-x-auto"><code>{`curl -X POST https://writehumane.com/api/v1/humanize \\
  -H "Authorization: Bearer wh_live_xxxxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "text": "Your AI-generated content here...",
    "mode": "balanced",
    "tone": "professional"
  }'`}</code></pre>
              <div className="mt-4 pt-4 border-t border-gray-700">
                <pre className="text-blue-300 overflow-x-auto"><code>{`{
  "success": true,
  "humanized_text": "Naturally rewritten content...",
  "detection_score": 0.12,
  "credits_used": 150,
  "credits_remaining": 850
}`}</code></pre>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="py-20 bg-white px-4">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-bold mb-4">Everything you need to humanize AI content</h2>
            <p className="text-gray-600 text-lg max-w-2xl mx-auto">Built for developers who want reliable, fast AI content humanization without the complexity.</p>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((f, i) => (
              <div key={i} className="p-6 rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-lg transition-all">
                <div className="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center mb-4">
                  <f.icon size={24} className="text-white" />
                </div>
                <h3 className="text-lg font-semibold mb-2">{f.title}</h3>
                <p className="text-gray-600">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* API Docs */}
      <section id="api-docs" className="py-20 px-4">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-bold mb-4">Simple API, Powerful Results</h2>
            <p className="text-gray-600 text-lg">One endpoint. That&apos;s all you need.</p>
          </div>

          <div className="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div className="p-6 border-b border-gray-200">
              <div className="flex items-center gap-3">
                <span className="bg-green-100 text-green-700 px-3 py-1 rounded-md text-sm font-bold">POST</span>
                <code className="text-lg">/api/v1/humanize</code>
              </div>
            </div>

            <div className="p-6">
              <h3 className="font-semibold mb-3">Request Headers</h3>
              <div className="bg-gray-50 rounded-lg p-4 mb-6 font-mono text-sm">
                <div>Authorization: Bearer wh_live_your_api_key_here</div>
                <div>Content-Type: application/json</div>
              </div>

              <h3 className="font-semibold mb-3">Parameters</h3>
              <div className="overflow-x-auto mb-6">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="text-left px-4 py-2 font-medium">Parameter</th>
                      <th className="text-left px-4 py-2 font-medium">Type</th>
                      <th className="text-left px-4 py-2 font-medium">Required</th>
                      <th className="text-left px-4 py-2 font-medium">Description</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    <tr><td className="px-4 py-2 font-mono">text</td><td className="px-4 py-2">string</td><td className="px-4 py-2">Yes</td><td className="px-4 py-2">Text to humanize (max 5,000 words)</td></tr>
                    <tr><td className="px-4 py-2 font-mono">mode</td><td className="px-4 py-2">string</td><td className="px-4 py-2">No</td><td className="px-4 py-2">light, balanced (default), aggressive</td></tr>
                    <tr><td className="px-4 py-2 font-mono">tone</td><td className="px-4 py-2">string</td><td className="px-4 py-2">No</td><td className="px-4 py-2">professional (default), casual, academic, friendly</td></tr>
                    <tr><td className="px-4 py-2 font-mono">preserve_keywords</td><td className="px-4 py-2">boolean</td><td className="px-4 py-2">No</td><td className="px-4 py-2">Keep SEO keywords intact (default true)</td></tr>
                  </tbody>
                </table>
              </div>

              <h3 className="font-semibold mb-3">Example Response</h3>
              <div className="bg-gray-900 rounded-lg p-4 font-mono text-sm text-green-400 overflow-x-auto">
                <pre>{`{
  "success": true,
  "humanized_text": "Your naturally rewritten content...",
  "original_length": 150,
  "humanized_length": 148,
  "detection_score": 0.12,
  "credits_used": 150,
  "credits_remaining": 850,
  "processing_time": 3200
}`}</pre>
              </div>

              <h3 className="font-semibold mt-6 mb-3">Error Codes</h3>
              <div className="space-y-2 text-sm">
                <div className="flex gap-3"><span className="font-mono text-red-600 font-bold w-12">400</span><span className="text-gray-600">Bad request — missing or invalid parameters</span></div>
                <div className="flex gap-3"><span className="font-mono text-red-600 font-bold w-12">401</span><span className="text-gray-600">Unauthorized — invalid or missing API key</span></div>
                <div className="flex gap-3"><span className="font-mono text-red-600 font-bold w-12">402</span><span className="text-gray-600">Payment required — monthly word limit exceeded</span></div>
                <div className="flex gap-3"><span className="font-mono text-red-600 font-bold w-12">429</span><span className="text-gray-600">Rate limited — too many requests per minute</span></div>
                <div className="flex gap-3"><span className="font-mono text-red-600 font-bold w-12">500</span><span className="text-gray-600">Server error — try again later</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Banner */}
      <section className="py-20 px-4">
        <div className="max-w-4xl mx-auto gradient-bg rounded-3xl p-12 text-center text-white">
          <h2 className="text-3xl font-bold mb-4">Start humanizing in 60 seconds</h2>
          <p className="text-indigo-100 text-lg mb-8 max-w-2xl mx-auto">Sign up for free, grab your API key, and make your first request. No credit card. No complex setup.</p>
          <Link href="/register" className="bg-white text-indigo-700 px-8 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition inline-flex items-center gap-2">
            Get Free API Key <ArrowRight size={18} />
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-gray-400 py-12 px-4">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
          <div>
            <div className="text-white font-bold text-lg mb-1">WriteHumane</div>
            <div className="text-sm">&copy; {new Date().getFullYear()} VIIONR INFOTECH. All rights reserved.</div>
          </div>
          <div className="flex gap-6 text-sm">
            <Link href="/pricing" className="hover:text-white transition">Pricing</Link>
            <a href="#api-docs" className="hover:text-white transition">API Docs</a>
            <Link href="/register" className="hover:text-white transition">Sign Up</Link>
            <Link href="/login" className="hover:text-white transition">Login</Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
