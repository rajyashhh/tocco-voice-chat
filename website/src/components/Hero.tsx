'use client';

import { useState } from 'react';
import Container from './Container';

export default function Hero() {
  const [imgError, setImgError] = useState(false);

  return (
    <section className="bg-white" style={{ marginTop: '72px', minHeight: 'calc(100svh - 72px)' }}>
      <Container>
        <div className="flex flex-col lg:flex-row items-center gap-12 lg:gap-16 py-16 lg:py-0" style={{ minHeight: 'calc(100svh - 72px)' }}>
          {/* Left: Text content */}
          <div className="flex-1 max-w-[560px] text-center lg:text-left">
            {/* Eyebrow badge */}
            <div className="inline-flex items-center gap-2 bg-brand-light border border-brand-muted rounded-full px-4 py-1.5 mb-8">
              <span className="w-2 h-2 rounded-full bg-brand animate-pulse" />
              <span className="text-[13px] font-semibold text-brand">Now Available</span>
            </div>

            {/* Headline */}
            <h1 className="text-[2.5rem] sm:text-[3rem] lg:text-[3.5rem] xl:text-[4rem] font-bold text-text leading-[1.08] tracking-tight mb-6">
              Go Live.
              <br />
              <span className="text-brand">Connect</span> Everywhere.
            </h1>

            {/* Supporting text */}
            <p className="text-slate-500 text-[1.05rem] lg:text-[1.1rem] leading-relaxed max-w-[440px] mx-auto lg:mx-0 mb-10">
              Tocco Voice Live brings live streaming, real-time chat, and translation together in one beautifully designed platform for genuine connections.
            </p>

            {/* CTAs */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
              <a
                href="#download"
                className="inline-flex items-center justify-center gap-2.5 bg-brand text-white px-7 py-3.5 rounded-full text-[15px] font-semibold hover:bg-brand-hover transition-all shadow-[0_4px_14px_rgba(145,116,213,0.3)] hover:shadow-[0_6px_20px_rgba(145,116,213,0.4)]"
              >
                Get the App
              </a>
              <a
                href="#features"
                className="inline-flex items-center justify-center gap-2 bg-white text-brand border border-brand/30 px-7 py-3.5 rounded-full text-[15px] font-semibold hover:bg-brand-light hover:border-brand/50 transition-all"
              >
                See Features
              </a>
            </div>
          </div>

          {/* Right: Phone presentation */}
          <div className="flex-1 flex justify-center lg:justify-end relative">
            {/* Ambient glow */}
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[340px] h-[420px] bg-brand/[0.07] rounded-full blur-[80px] pointer-events-none" />
            <div className="absolute top-1/3 right-0 w-[180px] h-[180px] bg-brand/[0.05] rounded-full blur-[60px] pointer-events-none" />

            {/* Phone device */}
            <div className="relative">
              {/* Phone outer shell */}
              <div
                className="w-[280px] sm:w-[300px] lg:w-[320px] rounded-[40px] bg-slate-900 p-[10px] shadow-[0_20px_60px_rgba(0,0,0,0.12),0_8px_24px_rgba(0,0,0,0.08)]"
                style={{ aspectRatio: '9/19.5' }}
              >
                {/* Screen area */}
                <div className="w-full h-full rounded-[32px] bg-white overflow-hidden relative">
                  {/* Notch */}
                  <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[90px] h-[22px] bg-slate-900 rounded-b-[14px] z-10" />

                  {/* Screen content */}
                  <div className="w-full h-full flex flex-col pt-8 px-5 pb-5">
                    {imgError ? (
                      /* Branded placeholder when no screenshot is available */
                      <div className="flex-1 flex flex-col items-center justify-center bg-gradient-to-b from-brand/[0.06] to-brand/[0.02] rounded-2xl border border-brand/10">
                        {/* App icon */}
                        <div className="w-14 h-14 rounded-[16px] bg-brand flex items-center justify-center text-white text-[22px] font-bold mb-4 shadow-lg">
                          T
                        </div>
                        <p className="text-brand text-[14px] font-bold tracking-tight">Tocco Voice</p>
                        <p className="text-slate-400 text-[11px] mt-1">Live Streaming & Chat</p>
                        {/* Fake UI elements */}
                        <div className="w-full mt-6 space-y-2.5 px-3">
                          <div className="flex gap-2">
                            <div className="flex-1 h-8 bg-brand/[0.08] rounded-lg" />
                            <div className="flex-1 h-8 bg-brand/[0.08] rounded-lg" />
                          </div>
                          <div className="h-10 bg-brand/[0.1] rounded-xl" />
                          <div className="h-8 bg-brand/[0.06] rounded-lg w-3/4" />
                          <div className="h-8 bg-brand/[0.06] rounded-lg w-1/2" />
                          <div className="grid grid-cols-2 gap-2 mt-3">
                            <div className="h-16 bg-brand/[0.08] rounded-xl" />
                            <div className="h-16 bg-brand/[0.08] rounded-xl" />
                          </div>
                        </div>
                      </div>
                    ) : (
                      <img
                        src="/app-screens/home.png"
                        alt="Tocco Voice Live app screenshot"
                        className="w-full h-full object-cover rounded-xl"
                        onError={() => setImgError(true)}
                      />
                    )}
                  </div>
                </div>
              </div>

              {/* Floating feature cards */}
              <div className="absolute -left-16 top-1/4 bg-white rounded-2xl px-4 py-3 shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-slate-100 hidden lg:flex items-center gap-3 animate-[float_6s_ease-in-out_infinite]">
                <div className="w-9 h-9 rounded-xl bg-brand/10 flex items-center justify-center text-[16px]">
                  📡
                </div>
                <div>
                  <p className="text-[13px] font-semibold text-text">Go Live</p>
                  <p className="text-[11px] text-slate-400">HD Streaming</p>
                </div>
              </div>

              <div className="absolute -right-12 bottom-1/3 bg-white rounded-2xl px-4 py-3 shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-slate-100 hidden lg:flex items-center gap-3 animate-[float_7s_ease-in-out_infinite_reverse]">
                <div className="w-9 h-9 rounded-xl bg-brand/10 flex items-center justify-center text-[16px]">
                  💬
                </div>
                <div>
                  <p className="text-[13px] font-semibold text-text">Chat</p>
                  <p className="text-[11px] text-slate-400">Real-time</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Container>

      {/* Float animation keyframes */}
      <style dangerouslySetInnerHTML={{ __html: `
        @keyframes float {
          0%, 100% { transform: translateY(0); }
          50% { transform: translateY(-8px); }
        }
      `}} />
    </section>
  );
}
