'use client';

import { siteConfig } from '@/config/site';

export default function Footer() {
  return (
    <footer className="bg-brand text-white">
      {/* Main footer content */}
      <div className="mx-auto max-w-[1200px] px-5 sm:px-6 lg:px-8 py-16 lg:py-20">
        <div className="grid grid-cols-1 md:grid-cols-[1.5fr_1fr_1fr_1fr] gap-12 lg:gap-16">
          {/* Brand column */}
          <div className="space-y-5">
            <div className="flex items-center gap-3">
              <img src="/branding/logo.png" alt="Tocco Voice Live" className="h-10 w-10 rounded-[14px] object-contain" />
              <span className="text-[20px] font-bold tracking-tight">
                Tocco Voice Live
              </span>
            </div>
            <p className="text-white/70 text-[15px] leading-relaxed max-w-[280px]">
              Real-time communication platform for live streaming, chat, and meaningful connections.
            </p>
            <div className="flex gap-4 pt-2">
              {/* Social placeholders — update when social URLs are available */}
              <a
                href="#"
                aria-label="Twitter"
                className="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-[14px]"
              >
                𝕏
              </a>
              <a
                href="#"
                aria-label="Instagram"
                className="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-[14px]"
              >
                IG
              </a>
            </div>
          </div>

          {/* Product links */}
          <div>
            <h4 className="text-[13px] font-semibold uppercase tracking-[0.12em] text-white/50 mb-5">
              Product
            </h4>
            <ul className="space-y-3">
              <li>
                <a href="#features" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Features
                </a>
              </li>
              <li>
                <a href="#download" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Download
                </a>
              </li>
              <li>
                <a href="/payment" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Payment
                </a>
              </li>
            </ul>
          </div>

          {/* Support links */}
          <div>
            <h4 className="text-[13px] font-semibold uppercase tracking-[0.12em] text-white/50 mb-5">
              Support
            </h4>
            <ul className="space-y-3">
              <li>
                <a href="mailto:toccovoicechat@gmail.com" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Help Center
                </a>
              </li>
              <li>
                <a href="mailto:toccovoicechat@gmail.com" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Contact Us
                </a>
              </li>
            </ul>
          </div>

          {/* Legal links */}
          <div>
            <h4 className="text-[13px] font-semibold uppercase tracking-[0.12em] text-white/50 mb-5">
              Legal
            </h4>
            <ul className="space-y-3">
              <li>
                <a href="/privacy" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Privacy Policy
                </a>
              </li>
              <li>
                <a href="#" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Terms of Service
                </a>
              </li>
              <li>
                <a href="#" className="text-[15px] text-white/80 hover:text-white transition-colors">
                  Community Guidelines
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Bottom bar */}
      <div className="border-t border-white/10">
        <div className="mx-auto max-w-[1200px] px-5 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-[13px] text-white/50">
            © {new Date().getFullYear()} {siteConfig.name}. All rights reserved.
          </p>
          <p className="text-[13px] text-white/50">
            {siteConfig.name} — Real-time communication platform
          </p>
        </div>
      </div>
    </footer>
  );
}
