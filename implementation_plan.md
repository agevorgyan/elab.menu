# Implementation Plan: QR Menu & Ordering SaaS Platform (Laravel + MySQL)

Այս ծրագրային պլանը նախատեսված է Ռեստորանների, Կաֆեների և Հյուրանոցների համար նախատեսված ամբողջական **SAAS Web Application**-ի ստեղծման համար՝ Laravel (PHP), MySQL, HTML5, CSS3, JavaScript տեխնոլոգիաներով։

---

## 1. Ճարտարապետություն և Համակարգի Մոդուլներ (System Architecture)

### A. Super Admin Panel (`/superadmin`)
- **Vendor-ների Կառավարում**: Ստեղծում, խմբագրում, արգելափակում, փաթեթների/սահմանափակումների վերագրում։
- **Գլոբալ Վիճակագրություն**: Ընդհանուր վենդորներ, ակտիվ մասնաճյուղեր, պատվերների քանակ, այցելություններ։
- **Դիզայնի Թեմաների Catalog**: Գլոբալ storefront թեմաների ցանկ և կարգավորումներ։
- **AI Կարգավորումներ**: OpenAI / Gemini API Key-երի կառավարում (մենյուի ավտոմատ թարգմանության և PDF/Word ֆայլերից մենյուի իդենտիֆիկացման համար)։

### B. Vendor Admin Panel (`/admin`)
- **Multi-location Hub**: Բազմաթիվ մասնաճյուղերի կառավարում մեկ հաշվից, գների override, ապրանքների թաքցնում/ցուցադրում ըստ մասնաճյուղի։
- **Menu Builder (Կատեգորիաներ և Ապրանքներ)**: Drag-and-drop դասավորում, նկարների գալերեա, չափսեր/վարիացիաներ, ալերգեններ (EU regulated), սննդային արժեքներ (կալորիաներ, մակրոներ)։
- **AI Menu Import**: PDF/Word կամ տեքստային մենյուի բեռնում -> AI-ը ինքնաշխատ քաղում է կատեգորիաները, ուտեստները, գները և նկարագրությունները։
- **AI Translation**: Մեկ հպումով ամբողջ մենյուի ավտոմատ թարգմանություն ցանկացած լեզվով (հայերեն, անգլերեն, ռուսերեն և այլն)։
- **Orders & Kitchen Live Panel**: Իրական ժամանակում պատվերների ստացում, կարգավիճակի փոփոխություն (Pending, Preparing, Ready, Delivered), WhatsApp հաղորդագրության գեներացում։
- **Branding & Templates**: Client Menu-ի դիզայնի ընտրություն 3+ պրեմիում թեմաներից (Modern Bistro, Luxury Dark, Neo Glass Cafe), գույների, լոգոյի, banner-ի անհատականացում live preview-ով։
- **QR Code Studio**: Սեղանի QR կոդերի գեներատոր (լոգոյով, անհատական գույներով, printable flyer template-ներով), Dedicated Dine-in & Ordering QR codes:
- **PWA & Push Notifications**: Application icon/branding, PWA manifest settings, հաճախորդներին Push promotions ուղարկելու հնարավորություն։
- **Team & Roles**: Մենեջերների և աշխատակիցների հրավեր ըստ մասնաճյուղի permissions-ների։
- **Analytics & Reports**: Օրական այցելությունների վիճակագրություն (Dine-In vs Online Order), ամենապահանջված ուտեստներ, մասնաճյուղերի համեմատություն։

### C. Client Storefront / Digital Menu (`/m/{vendor_slug}/{location_slug?}`)
- **Մուլտի-դիզայն Թեմաներ**: Vendor-ի ընտրած թեման (լոգո, գույներ, font-եր, layout)։
- **PWA (Progressive Web App)**: Անմիջապես հեռախոսի էկրանին ավելացնելու հնարավորություն (Install to Home Screen, Native App feel, custom splash screen, smooth animations):
- **Table Detection**: QR կոդի միջոցով սեղանի համարի ավտոմատ որոշում (`?table=4`)։
- **Լեզուների Փոխարկիչ**: Ակնթարթային թարգմանված տեքստերի ցուցադրում։
- **Ֆիլտրեր և Որոնում**: Ըստ կատեգորիաների, դիետիկ tag-երի (Vegan, Gluten-Free, Halal) և որոնում։
- **Ուտեստի Մանրամասն Մոդալ**: Նկարներ, չափսի ընտրություն, ալերգենների պատկերակներ (icons), calories & macros:
- **Զամբյուղ / Պատվեր (Cart & Ordering Flow)**:
  - **Dine-In Mode**: «Ցույց տալ մատուցողին» (Show to server) ցուցակ կամ ուղիղ սեղանի պատվեր։
  - **Takeaway / Delivery Mode**: Պատվերի ուղարկում WhatsApp-ով կամ զանգով (zero commission):

---

## 2. Տվյալների Բազայի Սխեմա (Database Schema)

1. `users`: System users (super_admin, vendor_owner, manager, staff).
2. `vendors`: Business profiles (name, slug, logo, phone, settings, active theme_id, primary_color, secondary_color, custom_domain).
3. `locations`: Vendor locations (name, slug, address, phone, whatsapp_number, opening_hours, table_count, is_active).
4. `categories`: Menu categories per location/vendor (name, translations json, image, sort_order, is_active).
5. `products`: Dishes/items (category_id, name, translations json, description, price, image_url, gallery json, calories, protein, carbs, fat, is_available, sort_order).
6. `product_variations`: Sizes & options (product_id, name, price, translations json).
7. `allergens`: EU allergen list (code, name, icon).
8. `product_allergens`: Pivot table for products and allergens.
9. `dietary_tags`: Vegetarian, Vegan, Gluten-Free, Halal, etc.
10. `orders`: Orders table (vendor_id, location_id, order_number, table_number, type: dine_in/takeaway, total_amount, status, customer_name, customer_phone, notes).
11. `order_items`: Order items details (order_id, product_id, variation_name, price, quantity, notes).
12. `menu_templates`: Available frontend storefront designs/templates.
13. `push_subscriptions`: Web Push notification endpoints for client PWA subscribers.
14. `analytics_logs`: Daily pageviews/scans breakdown per vendor/location/channel.

---

## 3. Իրականացման Քայլեր (Proposed Execution Steps)

### Phase 1: Laravel Project Setup & Core Models/Migrations
- Initialize Laravel 11/12 application in `/Users/apple/Projects/qrmenu`.
- Set up SQLite / MySQL database configuration.
- Create migrations, models, factories, and seeders with complete sample data (Vendors, Locations, Categories, Products, Variations, Allergens, Themes).

### Phase 2: Design System & Styling (Vanilla CSS + Modern UI/UX)
- Build a responsive, high-end CSS framework with design tokens (variables, glassmorphism, mobile dark/light modes, animations).
- Prepare 3 Distinct Client Storefront Templates:
  1. **Modern Bistro**: Sleek modern grid layout with rich food cards.
  2. **Luxury Dark & Gold**: Premium dark mode for high-end dining and cocktail bars.
  3. **Vibrant Glassmorphic**: Trendy glassmorphism effect for cafes & boutique places.

### Phase 3: Super Admin & Vendor Admin Dashboards
- Super Admin routes, controllers, and Blade/JS interfaces.
- Vendor Admin multi-location dashboard, drag-and-drop category & dish manager, image uploader, variations editor, allergen tagger.
- AI Import & AI Translation simulation / service layer (with OpenAI/Gemini integration and fallbacks).
- Interactive Live Theme & Color Customizer with real-time preview iframe.
- QR Code Studio (Client SVG/Canvas QR generator with downloadable PDF/PNG table stands).

### Phase 4: Client Menu PWA Storefront
- Client routing `/m/{vendor_slug}/{location_slug?}` with dynamic template renderer.
- Mobile PWA Service Worker (`sw.js`) and manifest generator for home-screen installation.
- Multilingual toggle, dietary filters, item modal with macro details & allergens.
- Shopping cart, Dine-In table order list ("Show to Server"), WhatsApp order link generator.
- Push Notifications subscription prompt & vendor push broadcast system.

### Phase 5: Verification & Automated Tests
- Run Laravel feature tests and browser checks.
- Verify multi-location overrides, AI translation API handlers, PWA manifest validity, and QR code generation.

### Phase 6: Light Mode Fixes & Custom Color Controls (Current Focus)
- Fix Light Mode rendering across all 3 client storefront theme templates (`luxury-dark`, `modern-bistro`, `vibrant-glass`).
- Enhance Theme Customizer (`admin/branding/index.blade.php`) with real-time live preview query parameters (`theme_mode`, `primary_color`, `accent_color`, `secondary_color`, `bg_color`, `text_color`, `menu_template_id`).
- Ensure `ClientStorefrontController.php` dynamically overrides vendor parameters for real-time live preview iframe.
- Verify end-to-end functionality across all themes in both Light and Dark modes.

---

## User Review Required

> [!IMPORTANT]
> **Database & Environment Choice**:
> 1. Laravel project will be initialized with SQLite locally for zero-configuration instant preview, while fully supporting standard MySQL schema migrations for production deployment.
> 2. AI Menu Import & AI Translation will be powered by a dedicated OpenAI / Gemini Laravel service integration with graceful mock fallbacks when API keys are not provided.

---

## Verification Plan

### Automated Tests
- `php artisan test`: Test authentication, vendor creation, location overrides, order creation, API endpoints.
- `php artisan migrate:fresh --seed`: Verify database migrations and mock seeders.

### Manual Verification
- Test client storefront on multiple viewports (Mobile, Tablet, Desktop) across all 3 visual themes in both Light and Dark modes.
- Verify Theme Customizer color pickers (Primary, Accent, Secondary, Background, Text colors) dynamically update iframe preview.
- Test QR code generation and Table parameter detection (`?table=12`).
- Test WhatsApp ordering flow formatting.
