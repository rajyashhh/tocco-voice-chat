'use client';

import Container from './Container';

export default function DownloadCTA() {
  return (
    <section id="download" className="bg-white py-24 lg:py-32">
      <Container>
        <div className="bg-brand rounded-3xl p-10 sm:p-14 lg:p-20 text-center relative overflow-hidden">
          {/* Decorative elements */}
          <div className="absolute top-0 right-0 w-[300px] h-[300px] bg-white/[0.04] rounded-full -translate-y-1/2 translate-x-1/4 pointer-events-none" />
          <div className="absolute bottom-0 left-0 w-[200px] h-[200px] bg-white/[0.04] rounded-full translate-y-1/2 -translate-x-1/4 pointer-events-none" />

          {/* Content */}
          <div className="relative z-10">
            <h2 className="text-[2rem] sm:text-[2.5rem] lg:text-[3rem] font-bold text-white tracking-tight leading-[1.1] mb-5">
              Ready to Connect?
            </h2>
            <p className="text-white/80 text-[1.05rem] leading-relaxed max-w-[440px] mx-auto mb-10">
              Download Tocco Voice Live and start streaming, chatting, and connecting with people around the world.
            </p>

            {/* Store buttons */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              {[
                { label: 'App Store', icon: '🍎', href: process.env.NEXT_PUBLIC_APP_STORE_URL || '#' },
                { label: 'Google Play', icon: '▶️', href: process.env.NEXT_PUBLIC_GOOGLE_PLAY_URL || '#' },
                { label: 'AppGallery', icon: '🏪', href: process.env.NEXT_PUBLIC_APP_GALLERY_URL || '#' },
              ].map((store) => (
                <a
                  key={store.label}
                  href={store.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center justify-center gap-3 bg-white text-brand px-7 py-3.5 rounded-full text-[15px] font-semibold hover:bg-white/90 transition-all shadow-[0_4px_14px_rgba(0,0,0,0.1)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.15)] min-w-[180px]"
                >
                  <span className="text-[18px]">{store.icon}</span>
                  <div className="text-left">
                    <p className="text-[11px] font-normal text-brand/70 leading-none mb-0.5">Get it on</p>
                    <p className="text-[14px] font-bold text-brand leading-none">{store.label}</p>
                  </div>
                </a>
              ))}
            </div>
          </div>
        </div>
      </Container>
    </section>
  );
}
