# Public Endpoint Security Architecture (Phase 8)

## 1. Overview & Public Endpoint Audit

The **QR Menu Platform** provides high-speed, unauthenticated customer-facing web interfaces accessible by scanning QR codes located on dining tables, takeaway bags, or online storefronts. Because these endpoints are publicly reachable without traditional user login, they are primary targets for automated scraping, identifier enumeration, parameter brute-forcing, denial-of-service, and resource abuse.

### Public Endpoint Audit Matrix

| Public Route | Method | Exposed Information | Threat Vector | Defense Architecture Implemented |
|---|---|---|---|---|
| `/m/{vendor_slug}/{location?}` | `GET` | Menu categories, dishes, variations, prices, allergens | High-volume scraping, analytics visit flooding | Route throttle (`120/min`), IP hourly visit deduplication cache, minimal database queries |
| `/api/m/{vendor_slug}/order` | `POST` | Order placement, active order totals | Fake order spam, DDoS, internal ID leakage | Route throttle (`15/min`), opaque `tracking_token`, session authorization persistence, internal item IDs hidden |
| `/api/m/{vendor_slug}/order/{identifier}/status` | `GET` | Dish status, preparation milestone, table number, total | Sequential ID enumeration (`ORD-1001`, `ORD-1002`), customer PII snooping | Cryptographic `tracking_token` (`trk_[48 hex]`), token proof-of-possession required for `order_number`, customer PII scrubbed, automated 15-min IP lockout after 5 failed lookups |
| `/api/m/{vendor_slug}/call-waiter` | `POST` | Waiter notifications, service requests | Automated waiter call spam, kitchen flooding | 60-second table cooldown, route throttle (`15/min`), opaque `call_token` (`wcl_[48 hex]`) |
| `/payment/callback/{vendor_slug}/{reference}` | `GET` | Payment status redirect | Numeric order ID parameter tampering | Disallow raw sequential order IDs, resolve only via `merchant_reference` or `uuid`, server verification strictly enforced |
| `/api/webhooks/payment/{gateway}` | `GET/POST` | Payment gateway asynchronous webhooks | Webhook forgery, replay attacks, stack trace leak | Gateway HMAC/provider verification, route throttle (`60/min`), generic exception formatting |
| `/api/m/{vendor_slug}/ai-waiter/*` | `GET/POST` | AI conversational advisor, dish recommendations | Session hijack, LLM budget drain, model extraction | Cryptographic `session_token` (`ais_[48 hex]`), IP/session/vendor rate limits, quota caps, group throttle (`60/min`) |
| `/login`, `/register`, `/forgot-password`, `/login/2fa` | `POST` | User authentication & password reset | Credential stuffing, brute-force attacks | Strict route throttles (`10/min` on login/register/password update/2FA; `5/min` on reset email/code resend) |

---

## 2. Opaque Cryptographic Tokens

To completely eliminate sequential ID enumeration across public services, all predictable and auto-increment identifiers used as public secrets have been replaced with high-entropy cryptographic tokens.

### 2.1 Order Tracking Tokens (`tracking_token`)
- **Generation**: `trk_` prefix followed by 48 hexadecimal characters from `bin2hex(random_bytes(24))` (192 bits of cryptographic entropy).
- **Auto-Provisioning**: Created via Eloquent `creating` model hook in `App\Models\Order`.
- **Database Schema**: `orders.tracking_token` (`VARCHAR(64)`, `UNIQUE`, indexed).
- **Public Reference**: The public order status endpoint only reveals order progress to holders of this opaque token.

### 2.2 Waiter Call Tokens (`call_token`)
- **Generation**: `wcl_` prefix followed by 48 hexadecimal characters from `bin2hex(random_bytes(24))`.
- **Auto-Provisioning**: Created via Eloquent `creating` model hook in `App\Models\WaiterCall`.
- **Database Schema**: `waiter_calls.call_token` (`VARCHAR(64)`, `UNIQUE`, indexed).
- **Public Reference**: Response payloads return `'id' => $call->call_token` and `'call_token' => $call->call_token`, preventing disclosure of internal auto-increment primary keys.

### 2.3 AI Session Tokens (`session_token`)
- **Generation**: `ais_` prefix followed by 48 hexadecimal characters from `bin2hex(random_bytes(24))`.
- **Enforcement**: Public AI chat, language selection, preference questions, and recommendation endpoints resolve sessions solely by `session_token`.

---

## 3. Order Tracking Security & Anti-Enumeration

```
+----------------------------------------------------------------------------------------------------+
|                                    ORDER TRACKING RESOLUTION FLOW                                  |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|   Incoming GET /api/m/{slug}/order/{identifier}/status                                             |
|                             |                                                                      |
|                             v                                                                      |
|                Is client IP locked out? ---------------------> YES: Return 429 Too Many Requests   |
|                             | NO                                                                   |
|                             v                                                                      |
|                Is {identifier} a tracking_token? (starts with 'trk_')                              |
|                             |                                                                      |
|             +---------------+---------------+                                                      |
|             | YES                           | NO (querying via order_number like 'ORD-1001')       |
|             v                               v                                                      |
|   Find order by tracking_token    Find order by order_number                                       |
|             |                               |                                                      |
|             |                               +-> Found? -> NO -> Record Failed Attempt -> 404       |
|             |                               | YES                                                  |
|             |                               v                                                      |
|             |                     Verify Proof of Possession:                                      |
|             |                     - Query param ?token=...                                         |
|             |                     - OR Header X-Tracking-Token: ...                                |
|             |                     - OR Browser Session placed_order_ORD-...                        |
|             |                     - OR Authenticated staff member                                  |
|             |                               |                                                      |
|             |                               +-> Match? -> NO -> Record Failed Attempt -> 403       |
|             |                               | YES                                                  |
|             +---------------+---------------+                                                      |
|                             |                                                                      |
|                             v                                                                      |
|               Return Sanitized Status Payload:                                                     |
|               - Replace internal ID with tracking_token                                            |
|               - Mask customer phone, email, allergic/private notes                                 |
|               - Exclude vendor_id and internal product database keys                               |
+----------------------------------------------------------------------------------------------------+
```

### 3.1 Anti-Enumeration with Proof-of-Possession
When customers submit an order via `submitOrder`, the server:
1. Generates and associates the `tracking_token`.
2. Stores the authorization in the customer's HTTP session: `session(['placed_order_' . $order->order_number => $order->tracking_token])`.
3. Returns `tracking_token` in the response payload.

When polling or querying order status:
- If requested by `tracking_token`, access is authorized immediately.
- If requested by human-readable `order_number` (`ORD-XXXXXX`), proof of possession is verified against:
  - Query parameter: `?token=trk_...`
  - HTTP header: `X-Tracking-Token: trk_...`
  - Current browser session cookie
  - Authenticated staff role for that vendor
- Unauthorized attempts to probe sequential or random order numbers return `403 Forbidden`.

### 3.2 Failed Lookup Lockout Defense
Any non-existent lookup (`404`) or unauthorized lookup (`403`) records a failure against the requester's IP address:
- Cache key: `order_lookup_failures:{ip}` (TTL: 10 minutes).
- Threshold: Upon reaching **5 failed attempts**, the IP is locked out for **15 minutes**.
- Lockout response: `429 Too Many Requests` with a valid `Retry-After: 900` HTTP header.

---

## 4. Automated Waiter-Call Spam Defense

To prevent malicious scripts, pranksters, or automated tools from spamming waitstaff and kitchen displays:

### 4.1 Table-Level Cooldown
- When a call is submitted for a table, `callWaiter` queries for any pending call for that table created within the last **60 seconds**.
- If a recent pending call exists, the endpoint returns `429 Too Many Requests` with user-friendly feedback (`Խնդրում ենք սպասել, Ձեր նախորդ կանչն արդեն փոխանցվել է մատուցողին։`) and a dynamic `Retry-After` header indicating remaining seconds.

### 4.2 Route-Level Rate Limiting
- Waiter call submissions are protected under route middleware `throttle:15,1` (maximum 15 calls per minute per client).
- The returned payload only references the opaque `call_token`, never exposing internal database IDs.

---

## 5. Public Response Sanitization

Public APIs strictly adhere to zero-information-leakage principles:

1. **No Internal Database Primary Keys**: Order objects in public JSON return `'id' => $order->tracking_token`. Items return index-based identifiers (`item_1`, `item_2`). Waiter calls return `'id' => $call->call_token`.
2. **Customer PII Stripped**: Public status endpoints do not include `customer_phone`, `customer_email`, or private order `notes` (which may contain personal or medical dietary disclosures).
3. **No Vendor IDs Exposed**: Multi-tenant database primary keys (`vendor_id`) are excluded from public responses.
4. **No Payment Credentials**: Online payment redirection URLs contain gateway-signed tokens; raw API keys, merchant passwords, and secret tokens are never output.
5. **No Stack Traces or Exception Dumps**: In `bootstrap/app.php`, unhandled exceptions on `/api/m/*` return sanitized generic error JSON:
   ```json
   {
       "success": false,
       "message": "Սերվերի ժամանակավոր սխալ։ Խնդրում ենք փորձել մի փոքր ուշ։"
   }
   ```
   Full stack traces and exception details are logged internally with application context.

---

## 6. Security Headers Middleware

The `App\Http\Middleware\SecurityHeaders` middleware is registered globally in `bootstrap/app.php` across all HTTP endpoints:

```php
// App\Http\Middleware\SecurityHeaders
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-XSS-Protection', '1; mode=block');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
$response->headers->remove('X-Powered-By');
```

- **`X-Frame-Options: SAMEORIGIN`**: Prevents clickjacking attacks and framing inside unauthorized third-party iframes.
- **`X-Content-Type-Options: nosniff`**: Prevents browser MIME-type sniffing of static assets.
- **`X-XSS-Protection: 1; mode=block`**: Activates legacy XSS filtering.
- **`Referrer-Policy: strict-origin-when-cross-origin`**: Protects tracking tokens and path parameters from leaking on outbound links.
- **`Permissions-Policy`**: Restricts browser device sensors (microphone, geolocation) while allowing camera access only for self-hosted QR scanners.
- **Server Disclosure Removal**: `X-Powered-By` headers are stripped from all responses.

---

## 7. Verification & Automated Test Matrix

All features are tested and verified via `tests/Feature/PublicEndpointSecurityTest.php` and existing regression suites:

| Test Case | Method Verified | Result |
|---|---|---|
| `test_public_endpoints_include_security_headers` | HTTP GET `/m/{vendor_slug}` | **Passed** (All 5 security headers verified, `X-Powered-By` absent) |
| `test_order_submission_generates_cryptographic_tracking_token_and_omits_internal_ids` | POST `/api/m/{vendor_slug}/order` | **Passed** (`tracking_token` present, session initialized, items sanitized) |
| `test_order_status_accessible_directly_via_opaque_tracking_token` | GET `/api/m/{vendor_slug}/order/{token}/status` | **Passed** (200 OK, `id` is token, PII masked) |
| `test_order_status_prevents_sequential_id_enumeration_without_tracking_token` | GET `/api/m/{vendor_slug}/order/{order_number}/status` | **Passed** (403 Forbidden without token) |
| `test_order_status_allows_order_number_with_valid_tracking_token_proof` | GET status with query, header, or session | **Passed** (200 OK across all three proof channels) |
| `test_order_status_enforces_ip_lockout_after_multiple_failed_enumeration_attempts` | 5 failed guesses from same IP | **Passed** (6th attempt returns 429 with `Retry-After: 900`) |
| `test_waiter_call_generates_opaque_call_token_and_omits_database_ids` | POST `/api/m/{vendor_slug}/call-waiter` | **Passed** (`call_token` generated, DB IDs masked) |
| `test_waiter_call_prevents_automated_spam_on_same_table` | Duplicate call within 60s | **Passed** (429 Too Many Requests with `Retry-After`) |
| `test_waiter_call_prevents_ip_flooding` | 16 calls across tables | **Passed** (Throttled after 15/min limit) |
| `test_payment_callback_rejects_numeric_order_id_tampering` | GET `/payment/callback/{slug}/12345` | **Passed** (Redirects with error, no database probe) |
| `test_analytics_visits_are_deduplicated_per_ip` | Multiple visits from same IP | **Passed** (`RecordAnalyticsVisitJob` queued once per hour) |

### Full Test Suite Status
- **Total Tests**: 361 passed
- **Total Assertions**: 1,908 assertions
- **Failures**: 0
- **Laravel Pint**: 100% clean formatting
