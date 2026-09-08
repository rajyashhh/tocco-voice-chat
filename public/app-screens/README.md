# Tocco Voice Live — App Screenshots

Place app screenshots here for the website showcase.

## Supported filenames

The website automatically loads screenshots from this folder. Add files with these names:

```
public/app-screens/
  home.png
  rooms.png
  room.png
  profile.png
  settings.png
  chat.png
```

You can add any `.png`, `.jpg`, or `.webp` file. The website config in
`resources/views/welcome.blade.php` (screenshots array in the JS section)
maps each file to a title and description.

## Adding a new screenshot

1. Drop the image into this folder
2. Add an entry to the `screenshots` array in `welcome.blade.php`:

```javascript
{
    file: 'your-screenshot.png',
    title: 'Screen Title',
    description: 'Short description',
    feature: 'Optional feature label'
}
```

## Guidelines

- Recommended width: 400px (2x for retina: 800px)
- Format: PNG or WebP preferred
- Aspect ratio: 9:16 (mobile phone) or 4:5
- Keep files under 500KB for fast loading
- Name files in lowercase with hyphens: `room-view.png`
