# TNP Product Manager — Build Specification

## Overview

A two-sided web application for TrustedNutraProduct to manage and display affiliate offers.

- **Admin Side** — Password-protected dashboard to add, edit, delete offers
- **Viewer Side** — Public-facing affiliate offer directory (shareable link)

---

## Tech Stack (Recommended)

| Layer | Choice |
|---|---|
| Frontend | Next.js (App Router) or plain React + Vite |
| Backend / DB | Supabase (PostgreSQL + REST API, free tier) |
| Auth | Static admin password via env variable |
| Hosting | Vercel (free) |
| Styling | Tailwind CSS |

> Supabase gives you a real database + instant API without building a backend from scratch.

---

## Database Schema

### Table: `offers`

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | Primary key, auto-generated |
| `sr` | integer | Display order / serial number |
| `platform` | text | e.g. "BuyGoods", "ClickBank" |
| `offer_name` | text | e.g. "MetaTrim BHB" |
| `offer_id` | text | e.g. "11943", "tnproduct" |
| `category` | text | e.g. "Weight Loss", "Male Enhancement" |
| `top_landers` | jsonb | Array of `{ label, url }` objects |
| `affiliate_page_url` | text | URL for "Click Here" link |
| `revshare` | text | e.g. "75%", "80%" |
| `cpa` | text | e.g. "$170", "Will be Activated..." |
| `allowed_geos` | text | e.g. "Tier-1" |
| `restriction` | text | e.g. "No", "Yes" |
| `created_at` | timestamp | Auto |
| `updated_at` | timestamp | Auto |

---

## Pages & Routes

### 1. `/` — Viewer (Public)

- Shows all offers in a styled table matching the TNP branding
- Columns: Sr, Platform, Offer Name, Offer ID/Nickname, Category, Top Landers, Affiliate/Creative Page, RevShare, CPA, Allowed GEOs, Restriction
- Platform shown as colored badge (BuyGoods = blue, ClickBank = gray)
- Category shown as colored pill (Weight Loss = green, Male Enhancement = dark)
- Top Landers shown as clickable links
- "Click Here" links open in new tab
- No login required
- Auto-refreshes data from DB on load

### 2. `/admin` — Admin Dashboard (Password Protected)

- Simple password prompt on load (compare against `ADMIN_PASSWORD` env var)
- On correct password: show full offer management UI
- Features:
  - View all offers in a table
  - **Add Offer** button → form/modal with all fields
  - **Edit** button per row → pre-filled form/modal
  - **Delete** button per row → confirmation prompt
  - **Reorder** (optional) → drag to change `sr` order
- On save: updates Supabase instantly → viewer reflects changes live

---

## Admin Auth Logic

```js
// Simple client-side password check
const ADMIN_PASSWORD = process.env.NEXT_PUBLIC_ADMIN_PASSWORD;

if (enteredPassword === ADMIN_PASSWORD) {
  sessionStorage.setItem("tnp_admin", "true");
  // show dashboard
}
```

> Store password in `.env.local` as `NEXT_PUBLIC_ADMIN_PASSWORD=yourpassword`

---

## Viewer UI Design

Match existing TNP branding:

| Element | Style |
|---|---|
| Header | Dark navy background, white text, TNP logo/name centered |
| Table header | Dark navy row, white bold text |
| Platform badges | BuyGoods = blue pill, ClickBank = light gray pill |
| Category pills | Weight Loss = green, Male Enhancement = dark gray/maroon |
| Links | Blue underline, open in new tab |
| Alternating rows | White / light gray |
| Font | Clean sans-serif (e.g. Inter or DM Sans) |
| Contact info | Show `contact@trustednutraproduct.com` and `@TrustedNutraProduct` in header |

---

## Data Flow

```
Admin adds/edits offer
        ↓
Supabase DB updated
        ↓
Viewer page fetches fresh data on load
        ↓
Affiliates always see latest offers
```

---

## Environment Variables

```
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_anon_key
NEXT_PUBLIC_ADMIN_PASSWORD=yourpassword
```

---

## Folder Structure (Next.js)

```
/app
  /page.tsx              ← Viewer (public offer table)
  /admin/page.tsx        ← Admin dashboard
  /admin/components/
    OfferForm.tsx         ← Add/Edit modal form
    OfferTable.tsx        ← Admin table with edit/delete
/lib
  supabase.ts            ← Supabase client init
/components
  OfferTable.tsx         ← Public viewer table
  PlatformBadge.tsx      ← Colored platform pill
  CategoryPill.tsx       ← Colored category pill
```

---

## Build Order for Claude Code

1. Init Next.js project + install Supabase client + Tailwind
2. Create Supabase project → run schema SQL → copy env keys
3. Build `/lib/supabase.ts`
4. Build viewer page (`/`) with live data fetch
5. Build admin page (`/admin`) with password gate
6. Build Add/Edit/Delete offer functionality
7. Polish viewer UI to match TNP branding
8. Deploy to Vercel

---

## Notes

- `top_landers` stored as JSON array so multiple lander links per offer are supported
- No user accounts needed — single static admin password is sufficient
- Viewer page should be the default route so the share link is clean: `yourapp.vercel.app`
- Admin accessed at: `yourapp.vercel.app/admin`
