'use client';

import { useState } from 'react';
import { siteConfig } from '@/config/site';

export default function PaymentPage() {
  const walletAddress = 'TNHaEVYKYmf52XP8eZkyGrucMe4Ei81fM6';
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    if (!walletAddress) return;
    try {
      await navigator.clipboard.writeText(walletAddress);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      /* fallback: do nothing */
    }
  };

  return (
    <div className="min-h-screen bg-surface-bg">
      {/* Header */}
      <header className="bg-white border-b border-border-light">
        <div className="mx-auto max-w-[1200px] px-5 sm:px-6 lg:px-8 h-[72px] flex items-center">
          <a href="/" className="flex items-center gap-2.5 shrink-0">
            <img src="/branding/logo.png" alt="Tocco Voice Live" className="h-9 w-9 rounded-[12px] object-contain" />
            <span className="text-[17px] font-bold text-brand tracking-tight">
              {siteConfig.name}
            </span>
          </a>
        </div>
      </header>

      {/* Main content */}
      <main className="mx-auto max-w-[520px] px-5 sm:px-6 py-12 sm:py-16 lg:py-20">
        {/* Back link */}
        <a
          href="/"
          className="inline-flex items-center gap-2 text-[14px] text-text-muted hover:text-brand transition-colors mb-8"
        >
          ← Back to website
        </a>

        {/* Payment card */}
        <div className="bg-white rounded-3xl border border-border-light shadow-[0_1px_3px_rgba(0,0,0,0.04)] p-8 sm:p-10">
          {/* Network badge */}
          <div className="flex items-center gap-2 mb-6">
            <span className="inline-flex items-center gap-1.5 bg-brand-light text-brand px-3 py-1 rounded-full text-[12px] font-semibold">
              TRON
            </span>
            <span className="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-[12px] font-semibold">
              TRC20
            </span>
          </div>

          {/* Heading */}
          <h1 className="text-[1.5rem] sm:text-[1.75rem] font-bold text-text mb-2 tracking-tight">
            Make a Payment
          </h1>
          <p className="text-slate-500 text-[0.9rem] leading-relaxed mb-8">
            Send payment through the TRON network using the TRC20 standard.
          </p>

          {/* QR code */}
          <div className="flex justify-center mb-8">
            <div className="w-[220px] h-[220px] rounded-2xl bg-surface-dim border border-border-light flex items-center justify-center overflow-hidden">
              <img
                src="/payment/trc20-qr.png"
                alt="TRC20 payment QR code"
                className="w-full h-full object-contain"
                onError={(e) => {
                  const target = e.target as HTMLImageElement;
                  target.style.display = 'none';
                  const parent = target.parentElement;
                  if (parent) {
                    parent.innerHTML =
                      '<div class="flex flex-col items-center justify-center text-center p-4"><div class="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center text-[18px] mb-2">📱</div><p class="text-[13px] text-slate-400">QR code</p><p class="text-[11px] text-slate-300 mt-1">Coming soon</p></div>';
                  }
                }}
              />
            </div>
          </div>

          {/* Wallet address */}
          <div className="mb-6">
            <label className="block text-[12px] font-semibold text-text-muted uppercase tracking-[0.1em] mb-2">
              TRC20 Wallet Address
            </label>
            {walletAddress ? (
              <div className="flex items-stretch gap-2">
                <div className="flex-1 bg-surface-dim rounded-xl px-4 py-3 font-mono text-[13px] text-text break-all border border-border-light leading-relaxed">
                  {walletAddress}
                </div>
                <button
                  onClick={handleCopy}
                  className={`shrink-0 w-[52px] rounded-xl font-semibold text-[13px] transition-all border ${
                    copied
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : 'bg-brand text-white border-brand hover:bg-brand-hover'
                  }`}
                  aria-label="Copy wallet address"
                >
                  {copied ? '✓' : 'Copy'}
                </button>
              </div>
            ) : (
              <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-[13px] text-amber-700">
                Wallet address not configured.
              </div>
            )}
          </div>

          {/* Warning */}
          <div className="bg-amber-50 border border-amber-100 rounded-xl px-5 py-4">
            <p className="text-[13px] text-amber-800 leading-relaxed">
              <strong>⚠️ Important:</strong> Send payment only through the <strong>TRON (TRC20)</strong> network. Verify the wallet address and network before confirming your transaction. Payments sent through other networks may be lost permanently.
            </p>
          </div>
        </div>

        {/* Payment instructions */}
        <div className="mt-8 bg-white rounded-2xl border border-border-light p-6 sm:p-8">
          <h2 className="text-[1.1rem] font-semibold text-text mb-4">How to Pay</h2>
          <ol className="space-y-3 text-[0.9rem] text-slate-500 leading-relaxed">
            <li className="flex gap-3">
              <span className="shrink-0 w-6 h-6 rounded-full bg-brand/10 text-brand text-[12px] font-bold flex items-center justify-center">1</span>
              <span>Open your TRON wallet application (TronLink, Trust Wallet, etc.)</span>
            </li>
            <li className="flex gap-3">
              <span className="shrink-0 w-6 h-6 rounded-full bg-brand/10 text-brand text-[12px] font-bold flex items-center justify-center">2</span>
              <span>Select <strong>Send</strong> and choose the <strong>TRC20</strong> network</span>
            </li>
            <li className="flex gap-3">
              <span className="shrink-0 w-6 h-6 rounded-full bg-brand/10 text-brand text-[12px] font-bold flex items-center justify-center">3</span>
              <span>Copy and paste the wallet address above, or scan the QR code</span>
            </li>
            <li className="flex gap-3">
              <span className="shrink-0 w-6 h-6 rounded-full bg-brand/10 text-brand text-[12px] font-bold flex items-center justify-center">4</span>
              <span>Verify the address and network, then confirm your transaction</span>
            </li>
          </ol>
        </div>
      </main>
    </div>
  );
}
