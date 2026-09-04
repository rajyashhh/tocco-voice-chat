/**
 * App Screenshot Configuration
 *
 * To add a new screenshot:
 * 1. Place the image in public/app-screens/
 * 2. Add an entry below with file, title, description, and optional feature label
 *
 * The website auto-discovers and displays these in both the hero slideshow
 * and the app showcase grid.
 */

export interface Screenshot {
  file: string;
  title: string;
  description: string;
  feature?: string;
}

export const screenshots: Screenshot[] = [
  {
    file: "home.png",
    title: "Home Feed",
    description: "Discover live streams and trending content from creators worldwide",
    feature: "Discovery",
  },
  {
    file: "rooms.png",
    title: "Live Rooms",
    description: "Browse and join live streaming rooms with hosts and communities",
    feature: "Live Streaming",
  },
  {
    file: "room.png",
    title: "In-Room Experience",
    description: "Interactive live streaming with real-time engagement and gifts",
    feature: "Streaming",
  },
  {
    file: "chat.png",
    title: "Real-Time Chat",
    description: "Instant messaging with built-in real-time translation across languages",
    feature: "Communication",
  },
  {
    file: "profile.png",
    title: "Your Profile",
    description: "Manage your profile, followers, and content",
    feature: "Social",
  },
  {
    file: "settings.png",
    title: "Settings",
    description: "Customize your Tocco Voice Live experience",
    feature: "Settings",
  },
];

export const getScreenshotUrl = (filename: string) =>
  `/app-screens/${filename}`;
