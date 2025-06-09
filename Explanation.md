# Explanation.md — FoldSpy Plugin

## The Problem (In My Own Words)

As an admin of a marketing-focused WordPress website, I need to understand how users interact with key above-the-fold content — specifically which links are visible and clicked without scrolling. This allows me to evaluate what gets attention early and whether I need to rework content structure or call-to-actions.

The challenge is capturing this kind of interaction, links seen before scrolling, without relying on server-side logic alone, while ensuring the experience remains performant, secure, and extendable.

---

## My Solution Overview

**FoldSpy** is a custom-built WordPress plugin that tracks above-the-fold hyperlinks visible to a user on the homepage. It sends that data to the backend and logs each visit, allowing the admin to review the behavior via a dedicated WordPress admin screen. The solution is:

- Modular, lazy-loaded, and containerized using `league/container`
- Fully testable with PHPUnit (unit tests)
- Follows WordPress coding standards (PHPCS)
- Uses WP-native systems (hooks, REST API, cron, admin UI, nonce security)
- Easily extendable and developer-friendly

---

## Technical Design & Specification

### Frontend Tracker
- A JavaScript file (`foldspy.js`) observes all anchor (`<a>`) elements within the initial viewport using `IntersectionObserver`.
- When detected, it sends:
  - Viewport dimensions
  - List of above-the-fold links (hrefs)
  - User-Agent
- Debounced to prevent rapid fire, and data is flushed after DOMContentLoaded + layout stability.

### Logger

The logger just writes down important events that happens while the plugin is running, like when something saves, something breaks, or someone tries to do something they shouldn’t. It’s helpful for figuring out what went wrong or just seeing what the plugin’s been up to.

#### How It Works

- The logger is implemented in [`src/Support/Logger.php`](./src/Support/Logger.php).
- It uses the WordPress uploads directory to store log files.
- A new log file is created per day, named `foldspy-YYYY-MM-DD.log`.
- The logger uses `WP_Filesystem()` for abstracted file handling, and yes, it aligns with WordPress best practices.
- Messages are written in the format: `[2025-06-08 10:34:21] [INFO] Visit logged successfully`.
- It gracefully handles failure to create the directory using `wp_mkdir_p()`.

#### 📂 Where to Find the Logs

- All logs are stored in: `wp-content/uploads/foldspy-logs/`.
- Each file is named according to the date: `foldspy-2025-06-08.log`.

This makes it easy to track activity by day and cleanup strategies in the future if needed.


### REST Endpoint
- Registers `/foldspy/v1/log` using WordPress REST API.
- Validates nonce and `current_user_can('read')` to prevent abuse.
- Returns `WP_Error` or `WP_REST_Response` as appropriate.

### Data Storage
- Logs stored in a custom DB table: `wp_foldspy_logs`
- Includes timestamp, user ID, screen size, user agent, hrefs (JSON)
- Insertions and reads use `$wpdb` and are cached with `wp_cache_set` / `wp_cache_get`.

### Log Cleanup
- `LogCleanup` schedules a daily WP-Cron to purge logs older than 7 days.
- Implements logging of success/failure events with severity levels.

### Admin Dashboard
- View logs in a paginated UI.
- Export logs to CSV.
- View top 3 most viewed links in the past 7 days.
- Detailed log inspection for each row.
- UI is built using partial templates to separate logic and layout.

### Testing, PHPCS & CI/CD
- PHPUnit test suite for core classes: `Storage`, `LogCleanup`, `LogSchema`, `RestEndpoint`
- Mocks WP functions using Brain Monkey and Mockery
- CI Workflows for PHPCS + PHPUnit triggered on pull requests

---

## My Technical Decisions & Why

| Decision | Reason |
|---------|--------|
| **Use League\Container for DI** | Ensures testability, decoupling, lazy-loading and scalability as services grow. |
| **Service Providers per Domain** | Enforces separation of concerns: `Tracker`, `Admin`, `Support`. |
| **Frontend JS with IntersectionObserver** | Modern, actually works, avoids polling or manual calculations. |
| **REST API logging** | Cleaner than AJAX, easier to secure, extend, and test. |
| **View templates (partials)** | Keeps business logic (PHP) clean and separates from rendering. |
| **WordPress cron for cleanup** | No manual cleanup needed. Uses WP-native event scheduler. |
| **Caching with `wp_cache_`** | Avoids redundant DB hits, improves performance on admin screens. |
| **Brain Monkey for mocking WP** | Enables proper unit testing without loading WordPress core. |

---

## How This Meets the User Story

> _"As an admin, I want to see which links visitors can see when they land on my homepage so I can improve conversions."_

This plugin captures exactly that:

- It watches links *seen without scrolling*
- It stores and timestamps those logs in the database
- It provides a UI where the admin can:
  - See who visited (screen size, agent)
  - View which links were visible
  - Export all data to CSV
  - Monitor most frequent top links

No extra setup is needed — once activated, FoldSpy starts tracking immediately. Admins have control, visibility, and insights.

---

## Thought Process & Design Philosophy

I approached this challenge the way I would a client project: solve the problem first, then build it cleanly, extendably (just in-case the client gets too happy and wants to scale), and professionally.

- **Clarity first**: I broke the work into **phases**, each isolated to do one thing well (DI, Tracker, REST, Admin, Tests).
- **Native-first**: I avoided building from scratch when WordPress provided better tools (`register_rest_route`, `wp_schedule_event`, etc.).
- **Defensive development**: Every REST interaction is validated (nonce, caps, structure), with logs for failures.
- **Sustainability**: View templates, service providers, and cache layers ensure the plugin can scale or be reused.

---

## Installation & Usage

### Installation
1. Clone this repo or download it as a ZIP.
2. Place it in `wp-content/plugins/foldspy`.
3. Run `composer install` (requires Composer).
4. Activate from WP Admin.

### Developer Setup (Optional)
```bash
composer install
composer test-unit
composer phpcs