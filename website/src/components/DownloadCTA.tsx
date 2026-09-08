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
                { label: 'App Store', icon: '🍎', href: '#', comingSoon: true },
                { label: 'Google Play', icon: '▶️', href: '#', comingSoon: true },
                { label: 'Android APK', icon: '🤖', href: '/tocco-voice.apk', download: true },
              ].map((store) => (
                <a
                  key={store.label}
                  href={store.href}
                  download={store.download ? 'tocco-voice.apk' : undefined}
                  target={store.download ? undefined : "_blank"}
                  rel="noopener noreferrer"
                  onClick={(e) => store.comingSoon && e.preventDefault()}
                  className={`inline-flex items-center justify-center gap-3 bg-white text-brand px-7 py-3.5 rounded-full text-[15px] font-semibold transition-all shadow-[0_4px_14px_rgba(0,0,0,0.1)] min-w-[180px] ${
                    store.comingSoon 
                      ? 'opacity-70 cursor-not-allowed' 
                      : 'hover:bg-white/90 hover:shadow-[0_6px_20px_rgba(0,0,0,0.15)]'
                  }`}
                >
                  <span className="text-[18px]">{store.icon}</span>
                  <div className="text-left">
                    <p className="text-[11px] font-normal text-brand/70 leading-none mb-0.5">
                      {store.comingSoon ? 'Coming Soon' : (store.download ? 'Download' : 'Get it on')}
                    </p>
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
