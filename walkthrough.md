# Walkthrough: QR Menu & Ordering SaaS Application

Ավարտվել է Ռեստորանների, Կաֆեների և Հյուրանոցների համար նախատեսված **SAAS Web Application**-ի մշակումը **Laravel (PHP)**, **MySQL**, **HTML5/CSS3/JS** տեխնոլոգիաներով։

---

## 🎬 Տեսաձայնագրություն (Browser Interactive Demo)

![QR Menu SaaS Platform Walkthrough](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/qrmenu_saas_demo_1789574809833.webp)

---

## 🚀 Իրականացված Մոդուլները (Implemented Features)

### 1. Super Admin Panel (`/superadmin`)
- **Vendor-ների Կառավարում**: Ստեղծել նոր Վենդոր (Կաֆե/Ռեստորան/Հյուրանոց) և ավտոմատ գեներացնել Owner-ի հաշիվը, ընտրել default թեման և բաժանորդագրության պլանը։
- **Գլոբալ Dashboard**: Ընդհանուր վենդորների, մասնաճյուղերի, պատվերների և platform-ի շրջանառության ցուցանիշներ։
- **Թեմաների Catalog**: Գլոբալ storefront թեմաների ցանկ (Modern Bistro Grid, Luxury Dark & Gold, Vibrant Glassmorphic Cafe)։

### 2. Vendor Admin Panel (`/admin`)
- **Multi-Location Hub**: Բազմաթիվ մասնաճյուղերի կառավարում մեկ հաշվից, active location-ի ակնթարթային փոփոխում top navbar-ից։
- **Menu Builder**:
  - Կատեգորիաներ (Armenian/English/Russian թարգմանություններով)։
  - Ուտեստներ (նկար, գին, ալերգեններ, դիետիկ tag-եր, calories & macros՝ սպիտակուցներ, ածխաջրեր, ճարպեր)։
  - Գների և stock-ի override ըստ մասնաճյուղի։
- **AI Menu Import & AI One-Click Translation**:
  - PDF/Word/Text ֆայլերից մենյուի ավտոմատ քաղում (Categories, Dish names, Prices, Descriptions):
  - 1-Click Ավտոմատ թարգմանություն ցանկացած լեզվով (հայերեն, անգլերեն, ռուսերեն, ֆրանսերեն, գերմաներեն, իսպաներեն)։
- **Live Kitchen Orders Board**:
  - Իրական ժամանակում ստացվող պատվերներ (Dine-In, Takeaway, WhatsApp):
  - Կարգավիճակների փոփոխություն (`Pending` -> `Preparing` -> `Ready` -> `Completed`)։
- **Theme & Branding Customizer**:
  - 3+ luxury թեմաների ընտրություն։
  - Brand color palette (Primary/Secondary color pickers, Dark/Light mode):
  - 📱 Live Mobile Storefront Preview iframe:
- **Table QR Code Studio**:
  - Յուրաքանչյուր սեղանի համար QR կոդի գեներացում (`Table 1`, `Table 2`, ...)։
  - Տպագրվող Printable Table Stand Flyer preview & print trigger (`window.print()`):
- **Analytics & Traffic Split**:
  - Օրական այցելությունների վիճակագրություն՝ բաժանված ըստ Dine-In QR scans-ի և Online Ordering traffic-ի։
  - Ամենաշատ պատվիրված ուտեստների վարկանիշ (Top Dishes Ranking)։

### 3. Customer Digital Storefront & PWA (`/m/{vendor_slug}/{location_slug?}`)
- **Progressive Web App (PWA)**: Manifest.json & Service Worker integration, "Add to Home Screen" prompt, native app feel.
- **Multilingual Switcher**: 🇦🇲 Armenian, 🇬🇧 English, 🇷🇺 Russian ակնթարթային լեզվի փոփոխություն։
- **Table Detection**: QR կոդի միջոցով սեղանի համարի ավտոմատ ճանաչում (`?table=4`)։
- **Interactive Order Tray / Cart Drawer**:
  - **Dine-In Mode**: «Ցույց տալ / ուղարկել մատուցողին» (Show to server) ցուցակ։
  - **WhatsApp Ordering**: Անմիջապես WhatsApp-ով պատվերի ուղարկում (Zero commission):

---

## 📸 Էկրանի Նկարներ (Screenshots)

````carousel
![Vendor Dashboard](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/vendor_dashboard_1789574998167.png)
<!-- slide -->
![Menu Builder](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/menu_builder_1789575013537.png)
<!-- slide -->
![AI Menu Import](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/ai_menu_import_1789575029513.png)
<!-- slide -->
![Live Kitchen Orders](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/live_kitchen_orders_1789575055469.png)
<!-- slide -->
![Theme Customizer](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/theme_customizer_1789575080260.png)
<!-- slide -->
![Table QR Studio](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/table_qr_studio_1789575110221.png)
<!-- slide -->
![Digital Menu Storefront](/Users/apple/.gemini/antigravity-ide/brain/4849ef08-5ea2-4d19-b821-c237765ac0f9/digital_storefront_1789575144396.png)
````

---

## 🔐 Դեմո Մուտքի Տվյալներ (Demo Credentials)

| Role | Email | Password | Access URL |
|---|---|---|---|
| **Super Admin** | `admin@qrmenu.local` | `password` | `http://127.0.0.1:8000/login` |
| **Vendor Owner (Bistro Yerevan)** | `owner@bistro.am` | `password` | `http://127.0.0.1:8000/login` |
| **Cascades Branch Manager** | `manager@bistro.am` | `password` | `http://127.0.0.1:8000/login` |
| **Customer Storefront** | *(Public)* | *(No auth)* | `http://127.0.0.1:8000/m/bistro-yerevan` |
