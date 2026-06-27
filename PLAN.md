# banha.shop — Architecture Plan (MVP: Core Marketplace Loop)

> Scope of this document: data model, modules, and build order for the **core
> marketplace loop** — buyer publishes demand → merchants matched & notified →
> offers submitted → buyer compares → chat → selects winner → review.
> Monetization, AI, gamification, and PWA polish are sketched but deferred.

---

## 1. The loop we are building

```
Buyer publishes Request
   → Matching Engine scores candidate merchants
      → Leads created + merchants notified
         → Merchant opens lead, submits Offer (consumes 1 credit)
            → Buyer compares Offers
               → Chat (buyer ↔ merchant, per request)
                  → Buyer selects winning Offer
                     → Deal completed → Review
```

Everything else in the PRD hangs off these entities.

---

## 2. Tech decisions (given the current `.env`)

| Concern | Current | Recommendation for the loop |
|---|---|---|
| DB | SQLite (dev) | **MySQL** (locked). Use it in dev too so geo + JSON behave identically. Real lat/lng distance via Haversine in SQL; bounding-box prefilter for index use. |
| Queue | `database` | Keep. Matching + notifications + (later) AI run as queued jobs. |
| Broadcasting | `log` | Add **Laravel Reverb** in Phase 5 for live offers + chat. Until then, poll / page-refresh. |
| Auth | none | **Laravel Breeze (Blade + Livewire stack).** Add a `type` to users (buyer/merchant/admin) + a `MerchantProfile`. |
| Frontend | Blade only | **Locked: Blade + Livewire + Alpine.js.** Breeze Livewire scaffold; PWA later via service worker over the same Blade views. |
| Locale | `en` | **Locked: Arabic-first, RTL.** `APP_LOCALE=ar`, `dir="rtl"`, Arabic `lang` files; English as fallback. |
| AI | no keys | Deferred. When added: Claude via queued jobs (Haiku 4.5 for cheap enrichment, Sonnet 4.6 for offer comparison). |

---

## 3. Data model

### Identity & profiles
- **users** (extend default): add `type` enum `buyer|merchant|admin`, `phone`, `locale` (`ar|en`), `avatar_path`.
- **merchant_profiles** (1:1 with merchant users): `business_name`, `description`, `logo_path`, `city`, `lat`, `lng`, `verified_at`, `credits_balance` (int), `subscription_tier`, cached aggregates `rating_avg`, `response_minutes_avg`, `win_rate`, `completed_deals`.
- **categories**: `name`, `name_ar`, `slug`, `parent_id` (self-referential for Phones→Electronics), `icon`. Seeded from the PRD's 16 categories.
- **category_merchant** (pivot): which categories a merchant serves.

### Requests (buyer demand)
- **requests**: `buyer_id`, `category_id`, `title`, `description`, `budget_min`, `budget_max`, `currency` (EGP), `city`, `lat`, `lng`, `condition` (`new|used|any`), `urgency` (`low|normal|high`), `payment_method`, `warranty_required` (bool), `preferred_delivery`, `specifications` (JSON, AI-enriched later), `status` (`draft|open|matched|closed|expired|completed`), `selected_offer_id` (nullable FK), `expires_at`.
- **attachments** (polymorphic: `attachable_type/id`): `path`, `mime`, `size` — used by requests *and* offers and messages.

### Offers (merchant bids)
- **offers**: `request_id`, `merchant_id` (user), `lead_id`, `price`, `currency`, `warranty`, `delivery_days`, `description`, `negotiation_enabled` (bool), `status` (`submitted|shortlisted|accepted|rejected|withdrawn`).

### Matching & leads
- **leads**: `request_id`, `merchant_id`, `quality_score` (0–100), `status` (`notified|viewed|offered|ignored|expired`), `charged_at` (when a credit was consumed). One row per (request, merchant) match. **Credit consumption policy: charge on offer submission** (configurable) — protects merchants from paying for leads they don't act on.

### Messaging
- **conversations**: `request_id`, `buyer_id`, `merchant_id` (unique together).
- **messages**: `conversation_id`, `sender_id`, `body`, `read_at`. Attachments via polymorphic table.

### Trust
- **reviews**: `request_id`, `reviewer_id` (buyer), `merchant_id`, `rating` (1–5), `quality_score`, `delivery_score`, `response_score`, `comment`. Recomputes cached aggregates on `merchant_profiles`.

### Monetization (tables sketched, deferred to post-MVP)
- **credit_transactions**: `merchant_id`, `type` (`purchase|consume|refund|bonus`), `amount`, `balance_after`, `reference` (lead/order id).
- **subscriptions / plans / credit_packages**: start as config; graduate to Cashier when real payments land.

### Notifications
- Use Laravel's built-in `notifications` table (DB channel now, broadcast + push later).

---

## 4. Modules (bounded contexts)

1. **Identity** — auth, roles, merchant onboarding & verification.
2. **Catalog** — categories (seeded, hierarchical).
3. **Requests** — buyer demand lifecycle + images.
4. **Matching** — `MatchRequestJob`: scores merchants, creates leads, dispatches notifications.
5. **Offers** — merchant bids + credit consumption.
6. **Messaging** — per-request chat.
7. **Trust** — reviews + cached merchant scores.
8. **Billing/Credits** *(deferred)*.
9. **AI** *(deferred)* — queued enrichment/comparison jobs.

---

## 5. Matching engine (Phase 2)

`MatchRequestJob` dispatched when a request transitions `draft → open`:

1. **Filter** candidates: serves the category, within location bounding box, verified, has credits or active subscription.
2. **Score** each (weighted 0–100): category exactness, budget fit, distance, reputation (`rating_avg`), response speed, historical win rate, availability.
3. **Create `leads`** for the top *N*, set `quality_score`, fire a `NewLead` notification (DB now; broadcast/push later).

State machines:
- **Request**: `draft → open → matched → completed` (or `closed`/`expired`).
- **Offer**: `submitted → shortlisted → accepted` (others auto-`rejected` on winner pick) / `withdrawn`.
- Selecting a winner: set `request.selected_offer_id` + `completed`, accept the offer, reject the rest, unlock review.

---

## 6. Build order (roadmap)

- **Phase 0 — Foundations:** ✅ **DONE.** user `type` + `merchant_profiles`, hierarchical `categories` (16 seeded), Breeze Livewire auth with buyer/merchant registration, `user.type` + `SetLocale` middleware, Arabic-first RTL layout (Cairo font, `lang/ar.json`), factories & seeders. 28 tests pass.
- **Phase 1 — Requests:** ✅ **DONE.** `requests` + polymorphic `attachments` tables; `Request`/`Attachment` models; buyer-only CRUD via Volt (create/edit/show/index) with a shared `RequestForm` + `_fields` partial; multi-image upload to the `public` disk; `RequestPolicy` (owner-scoped); draft→open publish action; status-badge component; buyer dashboard wired to live counts + active list; `My requests` nav link. 39 tests pass.
- **Phase 2 — Matching (basic):** ✅ **DONE.** `leads` table + `Lead` model; `MatchingService` (hard filters: verified + credits/subscription + category incl. parent/child; weighted 0–100 score over category exactness, reputation, Haversine distance, win-rate, responsiveness; `MAX_LEADS=20`, idempotent re-runs); queued `MatchRequestJob` dispatched via a `Request::saved` model event on any draft→open transition; `NewLead` DB notification; `LeadPolicy`; merchant "New leads" list + lead detail (marks viewed); merchant dashboard live lead count + nav link. 51 tests pass; verified end-to-end (publish → job → 4 leads + notifications).
- **Phase 3 — Offers:** ✅ **DONE.** `offers` table + `requests.selected_offer_id` FK; `Offer` model; `OfferService` submitting an offer inside a row-locked transaction that **consumes 1 credit atomically** (subscription merchants exempt, `InsufficientCreditsException` when broke); lead flips to `offered` + `charged_at` stamped; merchant offer form on the lead page (`LeadPolicy::submitOffer`); buyer offer-comparison on the request page (sort by price/delivery/rating); `OfferPolicy::view`. 59 tests pass; verified end-to-end.
- **Phase 4 — Selection + Chat + Reviews:** ✅ **DONE.** `conversations`/`messages`/`reviews` tables + models; `SelectionService` (accept winner, reject rest, complete request, recompute `win_rate` + `completed_deals`, open winner conversation — all row-locked); buyer select-winner + review form on the request page; per-request buyer↔merchant chat (polled, read receipts, participant-guarded); `ReviewService` recomputes `rating_avg`. 69 tests pass; full loop verified end-to-end.
- **Phase 5 — Real-time:** ✅ **DONE.** Laravel Reverb installed + configured (`BROADCAST_CONNECTION=reverb`, env keys, `config/reverb.php`); Echo + pusher-js wired (`resources/js/echo.js`); `MessageSent` broadcast event on a participant-guarded `conversations.{id}` private channel (`routes/channels.php`); chat listens live via `#[On('echo-private:…')]` with a 15s poll fallback; `NewLead` + `NewOffer` notifications now `database` + `broadcast` to each user's private channel. `reverb:start` added to `composer dev`. 73 tests pass; Reverb server boots and listens; suite stays hermetic via `BROADCAST_CONNECTION=null`.
- **Billing/credits** ✅ **DONE.** `credit_transactions` ledger; `config/banha.php` packages (Starter/Growth/Pro) + plans (Basic/Gold/Premium); `CreditService` (purchase/subscribe/consume, row-locked); merchant billing page (buy/subscribe/history, manual capture — gateway-ready); `OfferService` writes a `consume` ledger entry. 6 tests.
- **Gamification** ✅ **DONE.** Merchant levels (Bronze→Elite by completed deals) + computed badges (verified, top-merchant, fast-responder, rising-star); `x-merchant-badges` component; leaderboard page; badges on the merchant dashboard. 3 tests.
- **AI enrichment** ✅ **DONE.** Official Anthropic **PHP SDK**; `AiEnrichmentService` (config-gated on `ANTHROPIC_API_KEY`, model default `claude-opus-4-8` overridable via `ANTHROPIC_MODEL`) extracts `specifications` + suggests budget (fills only blanks); queued `EnrichRequestJob` dispatched on publish; graceful no-op without a key; specs shown on the request page. 4 tests.
- **PWA** ✅ **DONE.** `manifest.webmanifest` (RTL/Arabic, installable, standalone), `sw.js` (network-first pages + offline fallback, cache-first hashed assets), `offline.html`, SVG icon, `x-pwa-head` (manifest + theme-color + Apple meta + SW registration) in both layouts.
- **Design pass** ✅ **DONE.** Branded Arabic RTL landing page (hero tagline, how-it-works, value props, live category chips, CTA), Cairo font, indigo brand system; consistent across the authenticated app.
- **Full UI/UX overhaul + brand** ✅ **DONE.**
  - **Brand rename:** `banha.shop` → **Tanafos** (تنافس — "merchants compete") across `APP_NAME`, layouts, manifest, PWA meta, offline page. Domain references in DEPLOY left as-is.
  - **Design system:** inline-SVG `<x-icon>` set (no icon fonts/emoji — all emoji replaced); `[x-cloak]` + safe-area utilities.
  - **PWA-aware app shell:** sticky top bar + role-aware **mobile bottom nav** (`App\Support\Nav`), profile menu, real `logout` route, safe-area insets.
  - **Notifications:** `notifications.bell` (unread badge, dropdown, mark-all-read, 30s poll) + full notifications page; `App\Support\Notifications` maps NewLead/NewOffer → title/icon/url. 3 tests.
  - **Admin panel:** `/admin` (user.type:admin) — overview stats, merchants (verify/unverify), users (filter), requests (filter); seeded `admin@tanafos`. 4 tests.
  - **Marketing site:** `<x-marketing-layout>` (header nav + footer) with redesigned landing (hero, trust stats, steps, value props, categories, FAQ, CTA) + standalone **/how-it-works**, **/merchants**, **/pricing** (config-driven). 93 tests pass.

### Deployment target: shared hosting ✅ adapted
Real-time degrades to **polling** (Echo is opt-in — `app.js` only loads it when `VITE_REVERB_APP_KEY` is set; chat polls every 5s; `BROADCAST_CONNECTION=null`). Background jobs run via a **single cron entry** (`* * * * * php artisan schedule:run`) — the scheduler drains the `database` queue every minute (`routes/console.php`). Full guide in [DEPLOY.md](DEPLOY.md); `.env.example` defaults to production/MySQL/Arabic. Verified: one `schedule:run` tick drains a published request's matching job → leads + notifications. Re-enabling instant real-time later is a Pusher/Ably config + keys swap (no code change).

Each phase ships a usable vertical and has its own feature tests.

---

## 7. Decisions (locked)

1. **Frontend:** Blade + Livewire + Alpine.js (Breeze Livewire scaffold).
2. **Database:** MySQL (dev and prod) — real Haversine geo queries.
3. **Credit-charge timing:** on **offer submission**. Opening/viewing a lead is free; submitting an offer consumes 1 credit (atomically, inside the offer-create transaction; reject submission if `credits_balance < 1`).
4. **Locale:** Arabic-first, RTL. `APP_LOCALE=ar`, `<html dir="rtl" lang="ar">`, RTL-aware Tailwind, Arabic translation files with English fallback.

### RTL / i18n implications threaded through every phase
- Tailwind logical properties (`ps-`/`pe-`, `ms-`/`me-`) instead of left/right so LTR fallback works.
- All user-facing strings in `lang/ar/*.php`; `name` + `name_ar` already in the schema for categories — apply the same pattern to any seeded/enum labels.
- Validation, dates, and currency formatted for `ar` locale (EGP, Arabic-Indic numerals optional).
