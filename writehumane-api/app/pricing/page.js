'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Check, Zap, ArrowRight } from 'lucide-react';

const plans = [
  {
    name: 'Free',
    price: 0,
    period: 'forever',
    words: '1,000',
    features: ['1,000 words/month', '5 requests/minute', '1 API key', 'All humanization modes', 'WordPress plugin access', 'Community support'],
    cta: 'Get Started Free',
    popular: false,
  },
  {
    name: 'Starter',
    price: 9,
    period: '/month',
    words: '10,000',
    features: ['10,000 words/month', '15 requests/minute', '3 API keys', 'All humanization modes', 'WordPress plugin access', 'Email support'],
    cta: 'Start Starter Plan',
    popular: false,
  },
  {
    name: 'Pro',
    price: 19,
    period: '/month',
    words: '50,000',
    features: ['50,000 words/month', '30 requests/minute', '5 API keys', 'All humanization modes', 'WordPress plugin access', 'Priority email support', 'Dedicated success manager'],
    cta: 'Start Pro Plan',
    popular: true,
  },
  {
    name: 'Unlimited',
    price: 49,
    period: '/month',
    words: '999,999',
    features: ['~1M words/month', '60 requests/minute', '5 API keys', 'All humanization modes', 'WordPress plugin access', 'Priority support', 'Custom integrations', 'SLA guarantee'],
    cta: 'Go Unlimited',
    popular: false,
  },
];

const faqs = [
  { q: 'What counts as a "word"?', a: 'Words are counted from your input text. We count by splitting on whitespace. A 500-word blog post uses 500 words from your monthly limit.' },
  { q: 'Does humanized text really pass AI detectors?', a: 'Yes. Our balanced and aggressive modes consistently score below 15% on GPTZero, Originality.ai, Copyleaks, and Turnitin. Results vary by content type and length.' },
  { q: 'Can I use this with WordPress?', a: 'Absolutely. We have a free WordPress plugin that integrates with both Gutenberg and Classic Editor. One-click humanization right in your editor.' },
  { q: 'What happens if I exceed my monthly limit?', a: 'API calls will return a 402 error. You can upgrade your plan at any time to get more words instantly. Your counter resets on the 1st of each month.' },
  { q: 'Which AI providers do you use?', a: 'We use OpenAI (GPT-4o-mini) and Anthropic (Claude) as our AI backends. Our system prompts are specifically engineered for humanization, not generic rewriting.' },
  { q: 'Is there a free trial for paid plans?', a: 'The Free plan is your trial — 1,000 words/month forever. Test the quality before upgrading. No credit card required to start.' },
];

export default function PricingPage() {
  const [openFaq, setOpenFaq] = useState(null);

  return (
    <div className="min-h-screen">
      {/* Nav */}
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
          <Link href="/" className="text-xl font-bold gradient-text">WriteHumane</Link>
          <div className="flex items-center gap-4">
            <Link href="/login" className="text-sm text-gray-600 hover:text-gray-900">Login</Link>
            <Link href="/register" className="gradient-bg text-white px-4 py-2 rounded-lg text-sm font-medium">Get Started</Link>
          </div>
        </div>
      </nav>

      {/* Pricing Header */}
      <section className="pt-16 pb-12 px-4 text-center">
        <h1 className="text-4xl font-bold mb-4">Simple, transparent pricing</h1>
        <p className="text-xl text-gray-600 max-w-2xl mx-auto">Start free. Upgrade when you need more words. Cancel anytime.</p>
      </section>

      {/* Pricing Grid */}
      <section className="pb-20 px-4">
        <div className="max-w-7xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {plans.map((plan) => (
            <div key={plan.name}
              className={`relative bg-white rounded-2xl border p-6 flex flex-col transition-all ${
                plan.popular ? 'border-indigo-500 ring-2 ring-indigo-500 scale-105 shadow-xl' : 'border-gray-200 shadow-lg'
              }`}>
              {plan.popular && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 gradient-bg text-white text-xs font-bold px-3 py-1 rounded-full">
                  MOST POPULAR
                </div>
              )}
              <div className="mb-6">
                <h3 className="text-lg font-semibold">{plan.name}</h3>
                <div className="mt-3">
                  <span className="text-4xl font-bold">${plan.price}</span>
                  <span className="text-gray-500 ml-1">{plan.period}</span>
                </div>
                <div className="text-sm text-gray-500 mt-1">{plan.words} words/month</div>
              </div>

              <ul className="space-y-3 flex-1 mb-6">
                {plan.features.map((f, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm">
                    <Check size={16} className="text-green-600 shrink-0 mt-0.5" />
                    <span>{f}</span>
                  </li>
                ))}
              </ul>

              <Link href="/register"
                className={`block text-center py-3 rounded-lg font-semibold transition ${
                  plan.popular
                    ? 'gradient-bg text-white hover:opacity-90'
                    : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                }`}>
                {plan.cta}
              </Link>
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section className="pb-20 px-4">
        <div className="max-w-3xl mx-auto">
          <h2 className="text-2xl font-bold text-center mb-8">Frequently Asked Questions</h2>
          <div className="space-y-3">
            {faqs.map((faq, i) => (
              <div key={i} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button onClick={() => setOpenFaq(openFaq === i ? null : i)}
                  className="w-full text-left px-6 py-4 font-medium flex justify-between items-center">
                  {faq.q}
                  <span className="text-gray-400 ml-4">{openFaq === i ? '−' : '+'}</span>
                </button>
                {openFaq === i && (
                  <div className="px-6 pb-4 text-gray-600 text-sm">{faq.a}</div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-gray-400 py-8 px-4 text-center text-sm">
        &copy; {new Date().getFullYear()} VIIONR INFOTECH. All rights reserved.
      </footer>
    </div>
  );
}
