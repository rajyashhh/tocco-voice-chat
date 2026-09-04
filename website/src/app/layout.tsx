import type { Metadata, Viewport } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'Tocco Voice Live — Official Website',
  description:
    'Real-time communication platform for live streaming, chat, and translation. Download Tocco Voice Live and start connecting.',
  keywords: ['Tocco Voice Live', 'live streaming', 'chat', 'translation', 'communication'],
  authors: [{ name: 'Tocco Voice Live' }],
  openGraph: {
    type: 'website',
    title: 'Tocco Voice Live — Official Website',
    description:
      'Real-time communication platform for live streaming, chat, and translation.',
    siteName: 'Tocco Voice Live',
    url: 'https://toccovoice.com',
    images: [
      {
        url: '/branding/og-image.png',
        width: 1200,
        height: 630,
        alt: 'Tocco Voice Live',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Tocco Voice Live — Official Website',
    description:
      'Real-time communication platform for live streaming, chat, and translation.',
    images: ['/branding/og-image.png'],
  },
  icons: {
    icon: '/branding/logo.png',
    apple: '/branding/logo.png',
  },
  manifest: '/manifest.json',
  metadataBase: new URL('https://toccovoice.com'),
};

export const viewport: Viewport = {
  themeColor: '#9174D5',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={inter.variable}>
      <head>
        <link rel="canonical" href="https://toccovoice.com" />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              '@context': 'https://schema.org',
              '@type': 'SoftwareApplication',
              name: 'Tocco Voice Live',
              applicationCategory: 'CommunicationApplication',
              operatingSystem: 'iOS, Android',
              description:
                'Real-time communication platform for live streaming, chat, and translation.',
              url: 'https://toccovoice.com',
            }),
          }}
        />
      </head>
      <body className="antialiased">{children}</body>
    </html>
  );
}
