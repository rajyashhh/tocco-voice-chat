export default function PhonePlaceholder({ label }: { label?: string }) {
  return (
    <div className="w-full h-full flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-primary-light via-white to-primary-light/30 p-8">
      {/* Decorative circles */}
      <div className="relative w-20 h-20">
        <div className="absolute inset-0 rounded-full bg-primary/10 animate-pulse" />
        <div className="absolute inset-2 rounded-full bg-primary/15" />
        <div className="absolute inset-0 flex items-center justify-center">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9174D5" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="opacity-60">
            <rect x="5" y="2" width="14" height="20" rx="2" ry="2" />
            <line x1="12" y1="18" x2="12.01" y2="18" />
          </svg>
        </div>
      </div>
      {label && (
        <span className="text-xs font-medium text-primary/60 text-center leading-tight">
          {label}
        </span>
      )}
    </div>
  );
}
