'use client';

import Container from './Container';

const capabilities = [
  {
    icon: '📡',
    label: 'Live Streaming',
    description: 'Go live and connect with your audience in real-time with high-quality video and audio.',
  },
  {
    icon: '💬',
    label: 'Real-time Chat',
    description: 'Instant messaging with friends, followers, and communities across the platform.',
  },
  {
    icon: '🎁',
    label: 'Gifts & Rewards',
    description: 'Send and receive virtual gifts to support your favorite streamers and build community.',
  },
  {
    icon: '🌐',
    label: 'Live Translation',
    description: 'Break language barriers with built-in translation during live sessions.',
  },
];

export default function Stats() {
  return (
    <section id="app" className="bg-white py-24 lg:py-32">
      <Container>
        {/* Section header */}
        <div className="text-center mb-16 lg:mb-20">
          <p className="text-[13px] font-semibold uppercase tracking-[0.15em] text-brand mb-4">
            One App, Many Ways to Connect
          </p>
          <h2 className="text-[2rem] sm:text-[2.5rem] lg:text-[3rem] font-bold text-brand tracking-tight leading-[1.1]">
            Built for Real Communication
          </h2>
          <p className="mt-5 text-slate-500 text-[1.05rem] leading-relaxed max-w-[520px] mx-auto">
            Live streaming, real-time chat, translation, and gifts — all in one platform designed for genuine connections.
          </p>
        </div>

        {/* Capabilities grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {capabilities.map((cap) => (
            <div
              key={cap.label}
              className="group relative bg-surface-dim border border-border-light rounded-2xl p-7 hover:border-brand/20 hover:shadow-[0_8px_30px_rgba(145,116,213,0.08)] transition-all duration-300"
            >
              <div className="text-[2rem] mb-4">{cap.icon}</div>
              <h3 className="text-[1.1rem] font-semibold text-brand mb-2">{cap.label}</h3>
              <p className="text-[0.9rem] text-slate-500 leading-relaxed">{cap.description}</p>
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}
