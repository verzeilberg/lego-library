<h1 align="center">BRICKS library</h1>

<p align="center">Your personal LEGO library — organize, track, and manage your LEGO collection.</p>

BRICKS library is a full-stack LEGO collection management app. It lets you browse sets, minifigures, and parts; build custom boards (set lists); track part conditions; and share your collection with friends.

## Features

- **Sets** — browse and manage LEGO sets with detailed metadata, parts, and minifigures (powered by Rebrickable)
- **Boards (set lists)** — create custom boards to organize sets by theme, project, or collection
- **Parts** — track part conditions (missing, damaged, discoloured) and keep inventory accurate
- **Users & friends** — accounts, profiles, friend requests, and notifications
- **Android app** — native mobile client (currently Android only)

## Components

| Component | Technology | Location |
|-----------|------------|----------|
| Backend API | Symfony 6.4 + API Platform | `./` (root) |
| Web client (PWA) | Next.js / React | `./pwa` |
| Android app | Expo / React Native | external repo (APK built via EAS) |

## Android app

The mobile app is currently **Android only** (iOS is not supported yet).

The latest APK is served from the homepage at `/` (button: *Download App*), and is stored in `public/downloads/bricks-library-2.0.apk`.

To update the APK: replace the file in `public/downloads/`, bump the version in the filename and on the homepage button, then deploy.

## Getting started

### Development (Docker)

```bash
docker compose up -d
```

### Production (bare metal)

Run the deployment script:

```bash
./deploy.sh
```

This pulls the latest `master`, installs dependencies, clears/warms the Symfony cache, installs assets, runs migrations, and reloads the web server.

## API documentation

- Swagger UI: `/api/docs`
- Human-readable API reference: `/api/docs/api`
- API Platform docs: `./docs/api.md`

## License

Proprietary — all rights reserved.
