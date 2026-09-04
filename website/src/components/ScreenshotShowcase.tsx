"use client";

import { useState } from "react";
import Image from "next/image";
import { screenshots, getScreenshotUrl } from "@/config/screenshots";

function CardPlaceholder({ title }: { title: string }) {
  return (
    <div className="w-full h-full flex flex-col items-center justify-center gap-3 bg-gradient-to-b from-primary-light/60 via-white to-primary-light/30 p-6">
      <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9174D5" strokeWidth="1.5" strokeLinecap="round">
          <rect x="5" y="2" width="14" height="20" rx="2" ry="2" />
          <line x1="12" y1="18" x2="12.01" y2="18" />
        </svg>
      </div>
      <span className="text-[11px] font-medium text-primary/40 text-center">{title}</span>
    </div>
  );
}

export default function ScreenshotShowcase() {
  const [errors, setErrors] = useState<Record<string, boolean>>({});

  return (
    <section className="py-20 md:py-28 bg-surface-dim" id="app">
      <div className="site-container">
        {/* Section header */}
        <div className="text-center mb-12 md:mb-14">
          <div className="inline-flex items-center gap-3 text-[11px] font-semibold text-primary uppercase tracking-[0.18em] mb-4">
            <span className="w-8 h-px bg-primary/30" />
            The App
            <span className="w-8 h-px bg-primary/30" />
          </div>
          <h2 className="text-[1.75rem] md:text-[2.25rem] lg:text-[2.5rem] font-extrabold text-text tracking-[-0.02em] leading-tight mb-3">
            Experience Tocco Voice Live
          </h2>
          <p className="text-[15px] text-text-secondary max-w-md mx-auto leading-relaxed">
            See the features that make Tocco Voice Live the platform of choice
            for users worldwide.
          </p>
        </div>

        {/* Screenshot grid */}
        <div className="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-5">
          {screenshots.map((s) => (
            <div
              key={s.file}
              className="group bg-white rounded-2xl border border-border-light overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgba(145,116,213,0.1)] hover:border-primary/20"
            >
              <div className="relative w-full aspect-[9/16] overflow-hidden">
                {!errors[s.file] ? (
                  <Image
                    src={getScreenshotUrl(s.file)}
                    alt={`${s.title} — Tocco Voice Live`}
                    fill
                    className="object-cover group-hover:scale-[1.02] transition-transform duration-500"
                    sizes="(max-width: 768px) 50vw, 33vw"
                    loading="lazy"
                    onError={() => setErrors((p) => ({ ...p, [s.file]: true }))}
                  />
                ) : (
                  <CardPlaceholder title={s.title} />
                )}
              </div>
              <div className="p-4 md:p-5">
                {s.feature && (
                  <div className="text-[10px] font-semibold uppercase tracking-wider text-primary mb-1">
                    {s.feature}
                  </div>
                )}
                <h3 className="text-[14px] font-semibold text-text mb-1">{s.title}</h3>
                <p className="text-[12px] text-text-muted leading-relaxed">{s.description}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
