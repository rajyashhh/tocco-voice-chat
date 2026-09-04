export const siteConfig = {
  name: "Tocco Voice Live",
  title: "Tocco Voice Live — Official Website",
  description:
    "Tocco Voice Live — Go live, connect with people worldwide through live streaming, real-time chat, and translation. Download now.",
  url: "https://toccovoice.com",
  ogImage: "/branding/og-image.png",
  favicon: "/branding/logo.png",
  themeColor: "#9174D5",

  // App store links
  appStoreUrl: process.env.NEXT_PUBLIC_APP_STORE_URL || "#",
  googlePlayUrl: process.env.NEXT_PUBLIC_GOOGLE_PLAY_URL || "#",
  appGalleryUrl: process.env.NEXT_PUBLIC_APP_GALLERY_URL || "#",

  // Payment
  trc20WalletAddress:
    "TNHaEVYKYmf52XP8eZkyGrucMe4Ei81fM6",
  trc20QrCode: "/payment/trc20-qr.png",

  // API
  apiBaseUrl:
    process.env.NEXT_PUBLIC_API_BASE_URL || "https://api.toccovoice.com",

  // Navigation
  nav: [
    { label: "Features", href: "#features" },
    { label: "App", href: "#app" },
    { label: "Download", href: "#download" },
    { label: "Payment", href: "/payment" },
  ],

  // Footer
  footer: {
    product: [
      { label: "Features", href: "#features" },
      { label: "Download", href: "#download" },
      { label: "App Showcase", href: "#app" },
      { label: "Payment", href: "/payment" },
    ],
    support: [
      { label: "About Us", href: "#" },
      { label: "Privacy Policy", href: "/privacy" },
      { label: "Help Center", href: "#" },
      { label: "Contact", href: "#" },
    ],
    legal: [
      { label: "Privacy Policy", href: "/privacy" },
      { label: "Terms of Service", href: "#" },
      { label: "Community Guidelines", href: "#" },
    ],
  },
} as const;
