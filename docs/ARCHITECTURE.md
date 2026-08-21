# WebComics Architecture

## Runtime model

`laravel-blade/` is the only application that should be run for the real website.

- `prototype/` is a UI/UX reference and source for visual behavior.
- `server.js` is not part of the production runtime.
- The real website uses one Laravel origin, one session/authentication system, one database, and one route tree.
- Prototype `localStorage` and mock API state must not be used for production features.

## Visitor and role model

### Guest

Guests can browse the website without logging in:

- Home
- Genres
- Schedule
- Originals
- Comic detail pages
- Chapter reader
- Public search
- Public ratings/reviews display
- Public comments display
- Recommendations

A guest must log in only when attempting an authenticated action.

### Member

A normal authenticated user keeps all guest permissions and can additionally:

- Comment and reply
- Like comics
- Rate comics/reviews
- Add/remove comics from Library
- Save reading history/progress
- View personal library and reading statistics

### Admin

An admin keeps all member permissions and can access `/admin` and its children.

Admin authorization is enforced server-side by `AdminMiddleware` and `User::isAdmin()`.

Hiding the Admin button in the UI is not considered security. Direct requests to `/admin` must still be rejected for non-admin users.

## Route policy

```text
PUBLIC
/
/genres
/schedule
/originals
/truyen/{slug}
/truyen/{comicSlug}/{chapterSlug}
/api/search/*
/api/recommendations
/api/comments [read-only GET]
/api/comics/{comicId}/ratings/* [public read endpoints]

AUTHENTICATED USER
/user/library
/api/comments [write]
/api/reading-history
/api/comics/{comicId}/toggle-library
/api/comics/{comicId}/toggle-like
/api/comics/{comicId}/ratings [write]
/api/user/statistics/*
/logout

ADMIN
/admin/*
```

## UI migration rule

When migrating a prototype screen into Blade:

1. Keep the prototype visual hierarchy, layout, responsive behavior, wording, and interaction style where practical.
2. Reuse an existing Laravel feature when that feature already exists.
3. Replace prototype mock data/localStorage with Laravel models, controllers, Blade data, and JSON endpoints.
4. Do not create duplicate features when Laravel already provides the same capability.
5. When Laravel already provides a feature but its UI differs from the prototype, adapt the Blade UI to the prototype instead of replacing the backend behavior.
6. Keep authentication and authorization in Laravel, never in client-side JavaScript alone.

## Expected user flow

```text
Guest
  -> browse
  -> read
  -> attempts comment/library/like/rating
  -> login

Member
  -> browse + read
  -> authenticated interactions
  -> logout

Admin
  -> login
  -> normal website access
  -> /admin
  -> AdminMiddleware
  -> admin dashboard + management modules
```

## Current implementation notes

The Laravel application already contains the main authentication and authorization foundation:

- `AuthController` handles login, registration, session regeneration, logout, banned-user checks, and admin/member redirects.
- `User::isAdmin()` is the single role helper.
- `AdminMiddleware` protects the `/admin` route group.
- Blade views already use `@auth`/`@else` for library, like, and rating actions, so guests can still read normally.
- The prototype interaction layer has been moved onto the Laravel origin through `laravel-blade/public/js/app.js`; it no longer depends on `localhost:3000` for live search and navigation.

## Definition of done for the single-app migration

- No production page requires the Node prototype server.
- No production feature depends on prototype `localStorage` mock state.
- Every real URL is served by Laravel.
- Guest can read without authentication.
- Auth-required actions redirect or prompt the guest to `/login`.
- Non-admin authenticated users cannot access `/admin/*` even by typing the URL directly.
- Admin users can access `/admin/*` while normal users remain restricted.
- Prototype and Blade screens are visually aligned where the feature is migrated.
