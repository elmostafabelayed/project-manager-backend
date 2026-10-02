# Jobsy audit and repairs — 2 October 2026

## Implemented

- Proposal lists now require authentication and project ownership.
- Project updates accept only validated editable fields, preventing ownership transfer through `client_id`.
- Client/freelancer roles are enforced for project creation, proposals and invitations. Copying another client's proposal into a project is forbidden.
- Proposal creation, invitation creation, rejection and invitation response use transactions and project locks. Acceptance locks the project, prevents repeated contracts, creates/reuses the conversation, updates project status and rejects competing pending offers.
- Reviews require a contract and the correct counterpart. Completion and review creation are transactional; project completion now persists and updates the contract. Internal exception details are no longer returned.
- Conversation creation verifies complementary roles and ownership of the requested project. Project filtering no longer leaks across an ungrouped OR query. Latest messages are eager loaded, removing one database query per conversation.
- Public project/client, freelancer, review-author and message-sender serialization excludes email addresses.
- Login and registration are rate limited, as is message sending. Text, money, duration and upload validation now has bounds. Registration creates user and profile atomically.
- Notification conversation filtering uses Laravel's portable JSON query syntax.
- Mail notifications are queued after transaction commit. Production must run a queue worker and use a persistent queue connection, rather than `sync`.
- CORS uses the configured frontend origin, with localhost defaults only in local development. The rate limiter uses its own configurable store.
- Frontend CSRF initialization now shares the same backend URL fallback as the API. Malformed stored user JSON no longer crashes startup. Logout/401 clear only authentication storage.
- Duplicate page navbars were removed. Dropdown actions use buttons, role comparisons are normalized, hook dependencies are stable, and chat ignores late responses for a different selected conversation. Failed sends remain retryable instead of appearing delivered.
- Vite replaces the obsolete react-scripts build chain. Route pages load separately. Tailwind content configuration was added. Existing `REACT_APP_BACKEND_URL`, port 3000 and `build/` output are preserved.
- PHP and npm dependencies were updated; PHP dependency resolution targets the declared PHP 8.2 minimum.

- MySQL now uses a generated application account scoped to the existing database. The initially empty local database was migrated and initialized with 3 roles and 60 skills; no demo users were installed. PDO MySQL connectivity and 22 tables were verified on MariaDB 11.8.8.
- Unique constraints protect profiles, project proposals/contracts/conversations and reviews. Listing and unread-message indexes were added.
- Public projects/freelancers, owned projects, proposal lists, admin lists and notifications have bounded pagination with frontend controls. Dashboard totals are calculated over the full collection. Conversation lists use a private 50-row cursor, with direct selection and load-more support; message history uses 50-row backward and 100-row forward cursors.
- Chat polls for new messages, prevents duplicate sends and supports earlier history. Conversation rows support keyboard selection. Mobile navigation has accessible controls and hidden content is inert.
- Contact submissions and newsletter subscriptions are now saved, with rate limits and validation. Admins can read contact messages. Duplicate newsletter subscriptions are idempotent. Unsupported payment/escrow promises were removed.
- Accepting an offer now updates the existing project rather than trying to create another project. Explicit web-guard logout fixes an actual session error.
- Default admin credentials were removed. Admin creation requires privately configured credentials; demo seeding is blocked in production.
- Added a production environment template, read-only `app:production-check` command and deployment instructions.

## Verification

- Final npm audit and Composer locked dependency audit: zero reported vulnerabilities/advisories.
- Frontend production build passes; 3 frontend regression tests pass.
- Backend: 17 tests pass with 123 assertions on isolated SQLite. Coverage includes cookie authentication, project/proposal acceptance, messaging, reviews, authorization, pagination/cursors, contact/newsletter and notification counts.
- PHP formatting and both repositories' diff whitespace checks pass.
- Browser tests on an isolated SQLite database: client and freelancer registration, login/logout, project creation, proposal submission/acceptance and message delivery succeeded. Acceptance kept one project. Mobile chat and navigation were checked at 390×844, with no horizontal document overflow.
- The main entry JavaScript is about 77KB gzip. Shared and page-specific chunks are additional; this is not a production latency measurement.
- The production configuration check correctly reports that the local HTTP/debug environment is not production-ready.

## Deployment requirements and limits

The application changes are implemented and locally verified. No remote production deployment was performed.

- Set real HTTPS domains, secure cookies, `APP_ENV=production`, `APP_DEBUG=false`, SMTP and persistent queue workers. Use `DEPLOYMENT.md` and run `php artisan app:production-check` on the deployment host.
- Configure and test backups/restore and actual hosting. Configure a private admin account if needed. Newsletter signup stores subscriptions; outbound campaigns are not configured.
- Supply the owner's actual legal policies; none were fabricated.
- Production-engine concurrency and realistic load benchmarks remain deployment validation tasks. SQLite tests do not prove MySQL row-lock behavior under competing processes. Local MySQL connection, migrations and indexes were verified, but no production load test was performed.
- The browser check covers the principal workflow and a mobile chat/navigation check; it is not an exhaustive device/accessibility audit.

Zero advisories is a dependency audit result, not a guarantee that every possible vulnerability has been eliminated. The older `code_audit_report.md` predates these repairs and is not the current status report.
