'use client';

import { useState } from 'react';
import Container from './Container';

const features = [
  {
    icon: '📡',
    title: 'Live Streaming',
    description: 'Broadcast live to your audience with real-time video and audio. Engage viewers through comments and reactions as the moment unfolds.',
  },
  {
    icon: '💬',
    title: 'Real-time Chat',
    description: 'Instant messaging with friends, followers, and communities. Stay connected with people who matter.',
  },
  {
    icon: '🎁',
    title: 'Gifts & Rewards',
    description: 'Send and receive virtual gifts to support streamers and strengthen community bonds.',
  },
  {
    icon: '🌐',
    title: 'Live Translation',
    description: 'Built-in translation breaks language barriers during live sessions, connecting people across the globe.',
  },
];

export default function Features() {
  const [imgError, setImgError] = useState(false);

  return (
    <section id="features" className="bg-surface-bg py-24 lg:py-32">
      <Container>
        {/* Section header */}
        <div className="text-center mb-16 lg:mb-20">
          <p className="text-[13px] font-semibold uppercase tracking-[0.15em] text-brand mb-4">
            Features
          </p>
          <h2 className="text-[2rem] sm:text-[2.5rem] lg:text-[3rem] font-bold text-text tracking-tight leading-[1.1]">
            Built for Connection
          </h2>
          <p className="mt-5 text-slate-500 text-[1.05rem] leading-relaxed max-w-[520px] mx-auto">
            Everything you need for real-time communication, designed to feel effortless and natural.
          </p>
        </div>

        {/* Featured large card */}
        <div className="bg-white rounded-3xl border border-border-light overflow-hidden mb-8 lg:mb-10 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
          <div className="flex flex-col lg:flex-row">
            {/* Left: content */}
            <div className="flex-1 p-8 lg:p-12 flex flex-col justify-center">
              <div className="w-12 h-12 rounded-2xl bg-brand/10 flex items-center justify-center text-[24px] mb-6">
                📡
              </div>
              <h3 className="text-[1.5rem] lg:text-[1.75rem] font-bold text-text mb-4 leading-tight">
                Go Live, Reach Everyone
              </h3>
              <p className="text-slate-500 text-[0.95rem] leading-relaxed max-w-[400px]">
                Broadcast live streams with real-time video and audio. Your audience can watch, interact, and engage through comments and reactions — all happening simultaneously.
              </p>
            </div>
            {/* Right: visual */}
            <div className="flex-1 bg-brand/[0.03] flex items-center justify-center p-8 lg:p-12 min-h-[240px]">
              {imgError ? (
                <div className="w-[160px] h-[320px] rounded-[28px] bg-white border border-brand/10 shadow-lg flex flex-col items-center justify-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-brand flex items-center justify-center text-white text-[16px] font-bold">T</div>
                  <div className="w-24 h-2 bg-brand/10 rounded-full" />
                  <div className="w-16 h-1.5 bg-brand/[0.06] rounded-full" />
                  <div className="grid grid-cols-2 gap-2 mt-2">
                    <div className="w-12 h-12 bg-brand/[0.08] rounded-lg" />
                    <div className="w-12 h-12 bg-brand/[0.08] rounded-lg" />
                  </div>
                </div>
              ) : (
                <img
                  src="/app-screens/rooms.png"
                  alt="Live streaming feature"
                  className="max-h-[320px] w-auto object-contain rounded-[28px]"
                  onError={() => setImgError(true)}
                />
              )}
            </div>
          </div>
        </div>

        {/* Supporting feature grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {features.slice(1).map((feature) => (
            <div
              key={feature.title}
              className="bg-white rounded-2xl border border-border-light p-7 hover:border-brand/20 hover:shadow-[0_8px_30px_rgba(145,116,213,0.08)] transition-all duration-300"
            >
              <div className="w-11 h-11 rounded-xl bg-brand/[0.08] flex items-center justify-center text-[20px] mb-5">
                {feature.icon}
              </div>
              <h3 className="text-[1.1rem] font-semibold text-text mb-2.5">{feature.title}</h3>
              <p className="text-[0.9rem] text-slate-500 leading-relaxed">{feature.description}</p>
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}
