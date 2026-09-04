'use client';

import { useState, useEffect } from 'react';
import { siteConfig } from '@/config/site';
import Container from './Container';

const navLinks = [
  { label: 'Features', href: '#features' },
  { label: 'App', href: '#app' },
  { label: 'Download', href: '#download' },
  { label: 'Payment', href: '/payment' },
];

export default function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => {
    if (mobileOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileOpen]);

  return (
    <>
      <header
        className={`fixed top-0 left-0 right-0 z-[100] transition-all duration-300 ${
          scrolled
            ? 'bg-white/95 backdrop-blur-md shadow-[0_1px_0_0_rgba(0,0,0,0.06)]'
            : 'bg-white'
        }`}
      >
        <Container>
          <nav className="flex items-center justify-between h-[72px]">
            {/* Logo */}
            <a href="/" className="flex items-center gap-2.5 shrink-0">
              <img src="/branding/logo.png" alt="Tocco Voice Live" className="h-9 w-9 rounded-[12px] object-contain" />
              <span className="text-[17px] font-bold text-brand tracking-tight hidden sm:block">
                {siteConfig.name}
              </span>
            </a>

            {/* Desktop nav */}
            <div className="hidden md:flex items-center gap-1">
              {navLinks.map((link) => (
                <a
                  key={link.href}
                  href={link.href}
                  className="px-4 py-2 text-[15px] font-medium text-slate-600 hover:text-brand rounded-lg hover:bg-purple-50 transition-colors"
                >
                  {link.label}
                </a>
              ))}
            </div>

            {/* Desktop CTA */}
            <a
              href="#download"
              className="hidden md:inline-flex items-center gap-2 bg-brand text-white px-5 py-2.5 rounded-full text-[14px] font-semibold hover:bg-brand-hover transition-colors shadow-sm"
            >
              Get the App
            </a>

            {/* Mobile hamburger */}
            <button
              className="md:hidden w-10 h-10 flex items-center justify-center rounded-lg hover:bg-purple-50 transition-colors"
              onClick={() => setMobileOpen(!mobileOpen)}
              aria-label="Toggle navigation"
            >
              <div className="w-5 flex flex-col gap-[5px]">
                <span
                  className={`block h-[2px] bg-slate-700 rounded-full transition-all duration-300 origin-center ${
                    mobileOpen ? 'rotate-45 translate-y-[7px]' : ''
                  }`}
                />
                <span
                  className={`block h-[2px] bg-slate-700 rounded-full transition-all duration-200 ${
                    mobileOpen ? 'opacity-0 scale-x-0' : ''
                  }`}
                />
                <span
                  className={`block h-[2px] bg-slate-700 rounded-full transition-all duration-300 origin-center ${
                    mobileOpen ? '-rotate-45 -translate-y-[7px]' : ''
                  }`}
                />
              </div>
            </button>
          </nav>
        </Container>
      </header>

      {/* Mobile overlay */}
      <div
        className={`fixed inset-0 z-[99] bg-black/20 backdrop-blur-sm transition-opacity duration-300 md:hidden ${
          mobileOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
        }`}
        onClick={() => setMobileOpen(false)}
      />

      {/* Mobile menu */}
      <div
        className={`fixed top-[72px] left-0 right-0 z-[99] bg-white border-b border-slate-100 shadow-lg transition-all duration-300 md:hidden ${
          mobileOpen
            ? 'opacity-100 translate-y-0 pointer-events-auto'
            : 'opacity-0 -translate-y-4 pointer-events-none'
        }`}
      >
        <div className="px-5 py-6 space-y-1">
          {navLinks.map((link) => (
            <a
              key={link.href}
              href={link.href}
              onClick={() => setMobileOpen(false)}
              className="block px-4 py-3 text-[16px] font-medium text-slate-700 hover:text-brand hover:bg-purple-50 rounded-xl transition-colors"
            >
              {link.label}
            </a>
          ))}
          <div className="pt-3">
            <a
              href="#download"
              onClick={() => setMobileOpen(false)}
              className="block text-center bg-brand text-white px-5 py-3 rounded-full text-[15px] font-semibold hover:bg-brand-hover transition-colors"
            >
              Get the App
            </a>
          </div>
        </div>
      </div>
    </>
  );
}
