/**
 * Elab.menu Digital Menu Storefront Engine
 * High-performance, modular, reactive client runtime.
 */
(function(window) {
    'use strict';

    // Register PWA Service Worker if supported
    if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
        window.addEventListener('load', function() {
            const configEl = document.getElementById('storefront-config');
            let swUrl = '/m/sw.js';
            if (configEl) {
                try {
                    const cfg = JSON.parse(configEl.textContent || '{}');
                    if (cfg.routes && cfg.routes.sw) swUrl = cfg.routes.sw;
                } catch(e) {}
            }
            navigator.serviceWorker.register(swUrl).catch(function(err) {
                console.debug('ServiceWorker registration skipped:', err);
            });
        });
    }

    function createStorefrontApp(customConfig = {}) {
        const configEl = document.getElementById('storefront-config');
        let bootConfig = {};
        if (configEl) {
            try {
                bootConfig = JSON.parse(configEl.textContent || '{}');
            } catch(e) {
                console.error('Failed to parse storefront-config JSON:', e);
            }
        }

        const vendor = bootConfig.vendor || {};
        const routes = bootConfig.routes || {};
        const settings = bootConfig.settings || {};
        const translations = bootConfig.translations || {};
        const catalogProducts = bootConfig.catalogProducts || [];

        return {
            catalogProducts: catalogProducts,
            activeCat: customConfig.activeCat || bootConfig.activeCat || 'cat-1',
            search: '',
            cart: [],
            cartBump: false,
            showCartModal: false,
            showVariationModal: false,
            showPWA: true,
            selectedDish: null,
            selectedVariation: null,
            variationQty: 1,
            currentTab: 'menu',
            showInfoModal: false,
            showOrderTracker: false,
            hideTrackerPill: false,
            activeOrder: null,
            orderPollInterval: null,
            orderDismissTimeout: null,
            wifiCopied: false,
            customerName: '',
            customerPhone: '',
            customerBirthdate: '',
            paymentMethod: settings.paymentMethod || 'cash',
            birthdayDiscountPercent: Number(settings.birthdayDiscountPercent || 15),
            birthdayDiscountEnabled: !!settings.birthdayDiscountEnabled,
            birthdayValidityDays: Number(settings.birthdayValidityDays || 3),
            orderType: customConfig.orderType || settings.orderType || 'dine_in',
            deliveryAddress: '',
            serviceFeeEnabled: !!settings.serviceFeeEnabled,
            serviceFeeType: settings.serviceFeeType || 'percent',
            serviceFeeValue: Number(settings.serviceFeeValue || 0),
            serviceFeeMinOrder: Number(settings.serviceFeeMinOrder || 0),
            deliveryEnabled: !!settings.deliveryEnabled,
            deliveryFee: Number(settings.deliveryFee || 0),
            deliveryMinAmount: Number(settings.deliveryMinAmount || 0),
            deliveryFreeFrom: settings.deliveryFreeFrom !== null && settings.deliveryFreeFrom !== undefined ? Number(settings.deliveryFreeFrom) : null,
            takeawayEnabled: !!settings.takeawayEnabled,
            takeawayMinAmount: Number(settings.takeawayMinAmount || 0),
            isTableFixed: !!bootConfig.isTableFixed,
            tableNumber: customConfig.tableNumber !== undefined ? customConfig.tableNumber : (bootConfig.table || ''),
            showWaiterModal: false,
            serviceType: 'call_waiter',
            serviceTable: customConfig.tableNumber !== undefined && customConfig.tableNumber ? customConfig.tableNumber : (bootConfig.table || ''),
            isCallingService: false,
            marketingOptIn: true,
            orderNotes: '',
            selectedLang: bootConfig.lang || 'hy',
            currency: vendor.currency || 'AMD',
            vendorSlug: vendor.slug || '',
            csrfToken: bootConfig.csrfToken || '',
            locationId: bootConfig.locationId || 1,

            // AI Waiter State
            showWelcomeModal: false,
            showAiWaiter: false,
            showAiChatDrawer: false,
            aiStep: 'intro',
            aiAnalysisStep: 1,
            aiSessionId: null,
            aiSessionToken: null,
            aiCurrentQuestion: null,
            aiQuestionHistory: [],
            aiAnswerHistory: [],
            aiPreferences: {
                craving: null,
                occasion: null,
                drink_preference: null,
                dietary: []
            },
            aiFreeTextInput: '',
            aiPromptInput: '',
            aiLoading: false,
            aiCommentary: '',
            aiMainRecommendations: [],
            aiSecondaryRecommendations: [],
            aiPairingDrink: null,
            aiBundle: null,
            aiRecommendations: [],
            aiChatMessages: [
                {
                    sender: 'ai',
                    text: (vendor.ai_waiter_name || 'AI Waiter') + ': Ողջույն! Ես պատրաստ եմ պատասխանել մեր մենյուի վերաբերյալ ցանկացած հարցի:'
                }
            ],
            aiChatInput: '',
            aiChatLoading: false,
            aiAllowedLanguages: vendor.ai_waiter_languages || ['hy', 'en', 'ru'],

            // Toast State
            toast: {
                show: false,
                message: '',
                type: 'success',
                icon: 'fa-solid fa-circle-check',
                timeout: null
            },

            init() {
                this.restoreActiveOrderFromStorage();
                this.initAiWaiterWelcome();
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('payment_success') === '1') {
                    this.triggerToast('🎉 Վճարումը հաջողությամբ կատարվել է:', 'success', 'fa-solid fa-circle-check');
                }
                this.$nextTick(() => {
                    this.initScrollSpy();
                });
            },

            triggerToast(message, type = 'success', icon = null) {
                if (this.toast.timeout) clearTimeout(this.toast.timeout);
                this.toast.message = message;
                this.toast.type = type;
                this.toast.icon = icon || (type === 'success' ? 'fa-solid fa-circle-check' : (type === 'remove' ? 'fa-solid fa-trash-can' : 'fa-solid fa-circle-info'));
                this.toast.show = true;
                if (navigator.vibrate) {
                    try { navigator.vibrate(30); } catch(e) {}
                }
                this.toast.timeout = setTimeout(() => {
                    this.toast.show = false;
                }, 2500);
            },

            scrollToCat(catId) {
                this.activeCat = catId;
                const el = document.getElementById(catId);
                if (el) {
                    const headerOffset = 75;
                    const elementPosition = el.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            },

            initScrollSpy() {
                const sections = document.querySelectorAll('.category-section');
                if (!sections.length) return;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.activeCat = entry.target.id;
                            const activeChip = document.getElementById('chip-' + entry.target.id);
                            if (activeChip) {
                                activeChip.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                            }
                        }
                    });
                }, {
                    rootMargin: '-75px 0px -55% 0px',
                    threshold: 0.1
                });

                sections.forEach(sec => observer.observe(sec));
            },

            matchesSearch(title) {
                if (!this.search || !this.search.trim()) return true;
                return (title || '').toLowerCase().includes(this.search.trim().toLowerCase());
            },

            selectDish(dish) {
                if (!dish.variations || dish.variations.length <= 1) {
                    const v = (dish.variations && dish.variations.length === 1) ? dish.variations[0] : null;
                    this.addToCart(
                        dish.id,
                        dish.name,
                        v ? Number(v.price) : Number(dish.base_price || dish.price || 0),
                        v ? v.name : 'Standard',
                        v ? v.id : null,
                        1
                    );
                    return;
                }
                this.selectedDish = dish;
                this.selectedVariation = dish.variations.find(v => v.is_default) || dish.variations[0];
                this.variationQty = 1;
                this.showVariationModal = true;
            },

            addSelectedVariationToCart() {
                if (!this.selectedDish || !this.selectedVariation) return;
                this.addToCart(
                    this.selectedDish.id,
                    this.selectedDish.name,
                    Number(this.selectedVariation.price),
                    this.selectedVariation.name,
                    this.selectedVariation.id,
                    this.variationQty
                );
                this.showVariationModal = false;
            },

            addToCart(id, name, price, variationName = 'Standard', variationId = null, qty = 1) {
                let existing = this.cart.find(c => c.id === id && (c.variation_id && variationId ? c.variation_id === variationId : c.variation_name === variationName));
                if (existing) {
                    existing.qty += qty;
                } else {
                    this.cart.push({
                        id: id,
                        product_id: id,
                        name: name,
                        price: Number(price),
                        variation_name: variationName,
                        variation_id: variationId,
                        qty: qty
                    });
                }
                const addedText = translations.added_to_cart || 'ավելացվեց զամբյուղում';
                const varLabel = (variationName && variationName !== 'Standard' && variationName !== 'Standard Portion') ? ' (' + variationName + ')' : '';
                this.triggerToast('«' + name + varLabel + '» ' + addedText, 'success', 'fa-solid fa-circle-check');
                this.cartBump = true;
                setTimeout(() => { this.cartBump = false; }, 600);
            },

            changeQty(idx, delta) {
                const item = this.cart[idx];
                if (!item) return;
                this.cart[idx].qty += delta;
                if (this.cart[idx].qty <= 0) {
                    const removedName = item.name + (item.variation_name && item.variation_name !== 'Standard' && item.variation_name !== 'Standard Portion' ? ' (' + item.variation_name + ')' : '');
                    const removedText = translations.removed_from_cart || 'հեռացվեց զամբյուղից';
                    this.cart.splice(idx, 1);
                    this.triggerToast('«' + removedName + '» ' + removedText, 'remove', 'fa-solid fa-trash-can');
                }
            },

            get cartTotalCount() {
                return this.cart.reduce((a, b) => a + b.qty, 0);
            },

            get cartSubtotal() {
                return this.cart.reduce((a, b) => a + (b.price * b.qty), 0);
            },

            get cartTotalPrice() {
                return this.cartSubtotal;
            },

            formatCurrency(val) {
                const num = Number(val || 0);
                return num.toLocaleString() + ' ' + (this.currency || 'AMD');
            },

            getCartItemQty(dishId) {
                if (!this.cart || !this.cart.length) return 0;
                return this.cart
                    .filter(c => Number(c.id) === Number(dishId) || Number(c.product_id) === Number(dishId))
                    .reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            },

            get calculatedServiceFee() {
                if (this.orderType !== 'dine_in' || !this.serviceFeeEnabled) {
                    return 0;
                }
                const sub = this.cartSubtotal;
                if (this.serviceFeeMinOrder > 0 && sub < this.serviceFeeMinOrder) {
                    return 0;
                }
                if (this.serviceFeeType === 'percent') {
                    return Math.round((sub * this.serviceFeeValue) / 100);
                }
                return this.serviceFeeValue;
            },

            get calculatedDeliveryFee() {
                if (this.orderType !== 'delivery' || !this.deliveryEnabled) {
                    return 0;
                }
                const sub = this.cartSubtotal;
                if (this.deliveryFreeFrom !== null && this.deliveryFreeFrom > 0 && sub >= this.deliveryFreeFrom) {
                    return 0;
                }
                return this.deliveryFee;
            },

            get isDeliveryFree() {
                return this.orderType === 'delivery' && this.deliveryFreeFrom !== null && this.deliveryFreeFrom > 0 && this.cartSubtotal >= this.deliveryFreeFrom;
            },

            get freeDeliveryRemaining() {
                if (this.deliveryFreeFrom === null || this.deliveryFreeFrom <= 0) return 0;
                return Math.max(0, this.deliveryFreeFrom - this.cartSubtotal);
            },

            get freeDeliveryProgress() {
                if (this.deliveryFreeFrom === null || this.deliveryFreeFrom <= 0) return 100;
                if (this.cartSubtotal >= this.deliveryFreeFrom) return 100;
                return Math.min(100, Math.round((this.cartSubtotal / this.deliveryFreeFrom) * 100));
            },

            get isBelowDeliveryMin() {
                return this.orderType === 'delivery' && this.deliveryMinAmount > 0 && this.cartSubtotal < this.deliveryMinAmount;
            },

            get deliveryMinRemaining() {
                if (this.deliveryMinAmount <= 0) return 0;
                return Math.max(0, this.deliveryMinAmount - this.cartSubtotal);
            },

            get isBelowTakeawayMin() {
                return this.orderType === 'takeaway' && this.takeawayMinAmount > 0 && this.cartSubtotal < this.takeawayMinAmount;
            },

            get takeawayMinRemaining() {
                if (this.takeawayMinAmount <= 0) return 0;
                return Math.max(0, this.takeawayMinAmount - this.cartSubtotal);
            },

            get isBirthdayEligible() {
                if (!this.birthdayDiscountEnabled || !this.customerBirthdate) return false;
                try {
                    const bdate = new Date(this.customerBirthdate);
                    if (isNaN(bdate.getTime())) return false;
                    const today = new Date();
                    const thisYearBday = new Date(today.getFullYear(), bdate.getMonth(), bdate.getDate());
                    const diffTime = Math.abs(today - thisYearBday);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays <= this.birthdayValidityDays;
                } catch(e) {
                    return false;
                }
            },

            get birthdayDiscountAmount() {
                if (!this.isBirthdayEligible) return 0;
                return Math.round((this.cartSubtotal * this.birthdayDiscountPercent) / 100);
            },

            get cartFinalTotal() {
                const rawTotal = this.cartSubtotal + this.calculatedServiceFee + this.calculatedDeliveryFee - this.birthdayDiscountAmount;
                return Math.max(0, rawTotal);
            },

            get cartRecommendations() {
                if (!this.cart || this.cart.length === 0 || !this.catalogProducts || this.catalogProducts.length === 0) {
                    return [];
                }

                const inCartIds = new Set(this.cart.map(item => Number(item.id || item.product_id)));
                const cartText = this.cart.map(i => (i.name || '').toLowerCase()).join(' ');

                const hasFriesOrPotato = /fri|fries|ֆրի|картоф|potato|chips|տապակած/.test(cartText);
                const hasBurgerOrPizza = /burger|բուրգեր|бургер|pizza|պիցցա|пицца|sandwich|սենդվիչ|shawarma|շաուրմա|wrap|hotdog|նրբերշիկ/.test(cartText);
                const hasCoffeeOrTea = /coffee|սուրճ|кофе|tea|թեյ|чай|espresso|էսպրեսո|cappuccino|կապուչինո|latte|լատե/.test(cartText);
                const hasMeatOrSteak = /steak|սթեյք|стейк|kebab|քյաբաբ|խորոված|шашлык|meat|միս|beef|տավար|chicken|հավ|pork|խոզ/.test(cartText);
                const hasDrinks = /cola|կոլա|pepsi|պեպսի|fanta|ֆանտա|sprite|սպրայտ|juice|հյութ|сок|water|ջուր|вода|beer|գարեջուր|пиво|wine|գինի|вино|lemonade|լիմոնադ|կոկտեյլ|cocktail/.test(cartText);
                const hasSauce = /ketchup|կետչուպ|кетчуп|sauce|սոուս|соус|mayo|մայոնեզ|barbecue|bbq|garlic|սխտոր|dip|դիպ/.test(cartText);

                const recommendations = [];
                const addedRecIds = new Set();

                const findCandidates = (regex, reasonText, maxCount = 2) => {
                    let count = 0;
                    for (const prod of this.catalogProducts) {
                        if (inCartIds.has(Number(prod.id)) || addedRecIds.has(Number(prod.id))) continue;
                        const matchText = (prod.name_lower || '') + ' ' + (prod.category_name || '');
                        if (regex.test(matchText)) {
                            recommendations.push({
                                ...prod,
                                reason: reasonText
                            });
                            addedRecIds.add(Number(prod.id));
                            count++;
                            if (count >= maxCount) break;
                        }
                    }
                };

                if (hasFriesOrPotato && !hasSauce) {
                    findCandidates(/ketchup|կետչուպ|кетчуп|sauce|սոուս|соус|mayo|մայոնեզ|bbq|barbecue|cheese|պանրային|սխտոր|garlic/i, '🍟 Կատարյալ սոուս ֆրիի հետ', 2);
                }
                if ((hasFriesOrPotato || hasBurgerOrPizza || hasMeatOrSteak) && !hasDrinks) {
                    findCandidates(/cola|կոլա|pepsi|լիմոնադ|lemonade|beer|գարեջուր|пиво|drink|ըմպելիք|juice|հյութ/i, '🥤 Համեղ զովացուցիչ ըմպելիք', 2);
                }
                if (hasBurgerOrPizza && !hasFriesOrPotato) {
                    findCandidates(/fri|fries|ֆրի|картоф|potato|rings|օղակներ|snack/i, '🍟 Հաճախ պատվիրում են բուրգերի հետ', 2);
                }
                if (hasCoffeeOrTea) {
                    findCandidates(/croissant|կրուասան|cookie|թխվածք|cake|տորթ|cheesecake|չիզքեյք|dessert|աղանդեր|բրաունի|brownie/i, '☕ Քաղցր համադրություն սուրճի հետ', 2);
                }
                if (hasMeatOrSteak) {
                    findCandidates(/sauce|սոուս|соус|vegetable|բանջարեղեն|wine|գինի|гриль|grill/i, '🥩 Հիանալի լրացում մսային ուտեստին', 2);
                }
                if (recommendations.length < 3) {
                    findCandidates(/sauce|սոուս|соус|կետչուպ|ketchup|լիմոնադ|lemonade|juice|հյութ|cola|կոլա|dessert|աղանդեր/i, '✨ Հաճախորդների սիրելի ընտրություն', 4 - recommendations.length);
                }
                if (recommendations.length < 2) {
                    for (const prod of this.catalogProducts) {
                        if (inCartIds.has(Number(prod.id)) || addedRecIds.has(Number(prod.id))) continue;
                        recommendations.push({
                            ...prod,
                            reason: '✨ Առաջարկվող համեղ հավելում'
                        });
                        addedRecIds.add(Number(prod.id));
                        if (recommendations.length >= 4) break;
                    }
                }

                return recommendations.slice(0, 5);
            },

            quickAddRec(rec) {
                if (!rec.variations || rec.variations.length <= 1) {
                    const v = (rec.variations && rec.variations.length === 1) ? rec.variations[0] : null;
                    this.addToCart(
                        rec.id,
                        rec.name,
                        v ? Number(v.price) : Number(rec.price),
                        v ? v.name : 'Standard',
                        v ? v.id : null,
                        1
                    );
                } else {
                    this.selectDish(rec);
                }
                this.triggerToast('✨ ' + rec.name + ' ավելացվեց զամբյուղում', 'success', 'fa-solid fa-cart-plus');
            },

            async submitOrder(channel) {
                if (this.cart.length === 0) return;

                if (this.orderType === 'dine_in') {
                    if (!this.isTableFixed && (!this.tableNumber || !this.tableNumber.trim())) {
                        this.triggerToast(translations.dine_in_requires_table_qr || 'Խնդրում ենք նշել սեղանի համարը', 'remove', 'fa-solid fa-qrcode');
                        return;
                    }
                }
                if (this.orderType === 'takeaway') {
                    if (!this.takeawayEnabled) {
                        this.triggerToast(translations.takeaway_disabled_notice || 'Առհանման պատվերները անջատված են', 'remove', 'fa-solid fa-ban');
                        return;
                    }
                    if (this.isBelowTakeawayMin) {
                        this.triggerToast((translations.min_takeaway_order_warning || 'Նվազագույն գումար՝') + ' ' + Number(this.takeawayMinAmount).toLocaleString() + ' ' + this.currency, 'remove', 'fa-solid fa-triangle-exclamation');
                        return;
                    }
                    if (!this.customerPhone || !this.customerPhone.trim()) {
                        this.triggerToast(translations.takeaway_phone_required || 'Հեռախոսահամարը պարտադիր է', 'remove', 'fa-solid fa-phone');
                        return;
                    }
                }
                if (this.orderType === 'delivery') {
                    if (!this.deliveryEnabled) {
                        this.triggerToast(translations.delivery_disabled_notice || 'Առաքման պատվերները անջատված են', 'remove', 'fa-solid fa-ban');
                        return;
                    }
                    if (this.isBelowDeliveryMin) {
                        this.triggerToast((translations.min_delivery_order_warning || 'Նվազագույն գումար՝') + ' ' + Number(this.deliveryMinAmount).toLocaleString() + ' ' + this.currency, 'remove', 'fa-solid fa-triangle-exclamation');
                        return;
                    }
                    if (!this.deliveryAddress || !this.deliveryAddress.trim()) {
                        this.triggerToast(translations.delivery_address_required || 'Առաքման հասցեն պարտադիր է', 'remove', 'fa-solid fa-circle-exclamation');
                        return;
                    }
                    if (!this.customerPhone || !this.customerPhone.trim()) {
                        this.triggerToast(translations.delivery_phone_required || 'Հեռախոսահամարը պարտադիր է', 'remove', 'fa-solid fa-phone');
                        return;
                    }
                }

                try {
                    const submitUrl = routes.submitOrder || `/api/m/${this.vendorSlug}/order`;
                    const res = await fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            location_id: this.locationId,
                            table_number: (this.orderType === 'delivery' || this.orderType === 'takeaway') ? null : (this.tableNumber || null),
                            delivery_address: this.orderType === 'delivery' ? this.deliveryAddress.trim() : null,
                            type: this.orderType,
                            customer_name: this.customerName || 'Guest',
                            customer_phone: this.customerPhone || null,
                            customer_email: this.customerEmail || null,
                            customer_birthdate: this.customerBirthdate || null,
                            payment_method: this.paymentMethod || 'cash',
                            marketing_opt_in: this.marketingOptIn,
                            notes: this.orderNotes,
                            active_order_number: (this.activeOrder && !['completed', 'cancelled'].includes(this.activeOrder.status)) ? this.activeOrder.order_number : null,
                            items: this.cart.map(c => ({
                                product_id: c.id || c.product_id,
                                variation_id: c.variation_id || null,
                                variation_name: c.variation_name || null,
                                quantity: c.qty
                            }))
                        })
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        if (data.payment_redirect_url) {
                            this.cart = [];
                            this.saveActiveOrderToStorage();
                            window.location.href = data.payment_redirect_url;
                            return;
                        }

                        const isAppended = !!data.is_appended;
                        const orderItems = (data.items && data.items.length > 0)
                            ? data.items
                            : this.cart.map(c => ({
                                name: c.name,
                                variation_name: c.variation_name,
                                quantity: c.qty,
                                price: c.price,
                                subtotal: c.price * c.qty
                            }));

                        if (isAppended && this.activeOrder) {
                            this.activeOrder = {
                                ...this.activeOrder,
                                id: data.order_id || this.activeOrder.id,
                                tracking_token: data.tracking_token || this.activeOrder.tracking_token,
                                order_number: data.order_number,
                                status: data.status || this.activeOrder.status,
                                status_label: data.status_label || this.activeOrder.status_label,
                                subtotal: data.subtotal !== undefined ? Number(data.subtotal) : this.activeOrder.subtotal,
                                service_fee: data.service_fee !== undefined ? Number(data.service_fee) : this.activeOrder.service_fee,
                                delivery_fee: data.delivery_fee !== undefined ? Number(data.delivery_fee) : this.activeOrder.delivery_fee,
                                total_amount: data.total_amount,
                                items: orderItems
                            };
                        } else {
                            this.activeOrder = {
                                id: data.order_id || null,
                                tracking_token: data.tracking_token || null,
                                order_number: data.order_number,
                                status: data.status || 'pending',
                                status_step: 1,
                                status_percent: 25,
                                status_label: data.status_label || translations.status_pending || 'Ընդունված է',
                                status_desc: translations.status_desc_pending || 'Պատվերը սպասում է հաստատման',
                                status_icon: 'fa-solid fa-clock',
                                subtotal: Number(data.subtotal ?? (this.cartSubtotal || 0)),
                                service_fee: Number(data.service_fee ?? (this.calculatedServiceFee || 0)),
                                delivery_fee: Number(data.delivery_fee ?? (this.calculatedDeliveryFee || 0)),
                                total_amount: data.total_amount,
                                order_type: this.orderType,
                                delivery_address: this.orderType === 'delivery' ? this.deliveryAddress : null,
                                table_number: this.orderType === 'delivery' ? (translations.delivery || 'Առաքում') : (this.orderType === 'takeaway' ? (translations.takeaway || 'Առհանում') : (this.tableNumber || 'Սեղան')),
                                currency: this.currency,
                                created_at_time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                                created_at_human: 'հենց նոր',
                                items: orderItems
                            };
                        }

                        this.hideTrackerPill = false;
                        this.saveActiveOrderToStorage();
                        this.cart = [];
                        this.orderNotes = '';
                        this.showCartModal = false;
                        this.startOrderPolling();

                        if (data.whatsapp_url && channel === 'whatsapp') {
                            window.location.href = data.whatsapp_url;
                        } else {
                            this.showOrderTracker = true;
                            if (isAppended) {
                                this.triggerToast((translations.items_appended_toast || 'Ավելացվեց պատվերին') + ' (' + data.order_number + ')', 'success', 'fa-solid fa-circle-plus');
                            } else {
                                const successMsg = (translations.order_submitted_prefix || 'Պատվեր #') + data.order_number + (translations.order_submitted_suffix || ' գրանցված է');
                                this.triggerToast(successMsg, 'success', 'fa-solid fa-circle-check');
                            }
                        }
                    } else {
                        const errMsg = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Սխալ պատվերն ուղարկելիս');
                        this.triggerToast(errMsg, 'remove', 'fa-solid fa-circle-xmark');
                    }
                } catch (e) {
                    console.error('Order submit error:', e);
                    this.triggerToast('Ցանցային սխալ', 'remove', 'fa-solid fa-triangle-exclamation');
                }
            },

            goToMenu() {
                this.currentTab = 'menu';
                this.showCartModal = false;
                this.showInfoModal = false;
                this.showOrderTracker = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            copyWifiPassword(password) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(password).then(() => {
                        this.wifiCopied = true;
                        this.triggerToast(translations.wifi_copied || 'WiFi գաղտնաբառը պատճենվեց', 'success', 'fa-solid fa-check');
                        setTimeout(() => { this.wifiCopied = false; }, 2500);
                    }).catch(() => {
                        this.fallbackCopy(password);
                    });
                } else {
                    this.fallbackCopy(password);
                }
            },

            fallbackCopy(text) {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    this.wifiCopied = true;
                    this.triggerToast(translations.wifi_copied || 'WiFi գաղտնաբառը պատճենվեց', 'success', 'fa-solid fa-check');
                    setTimeout(() => { this.wifiCopied = false; }, 2500);
                } catch(e) {}
                document.body.removeChild(ta);
            },

            saveActiveOrderToStorage() {
                if (this.activeOrder) {
                    try {
                        localStorage.setItem('active_order_' + this.vendorSlug, JSON.stringify({
                            ...this.activeOrder,
                            savedAt: Date.now()
                        }));
                    } catch(e) {}
                }
            },

            isOrderPillVisible() {
                return !!(this.activeOrder && !this.showOrderTracker && (!['completed', 'cancelled'].includes(this.activeOrder.status) || !this.hideTrackerPill));
            },

            getActiveOrderSubtotal() {
                if (!this.activeOrder) return 0;
                if (this.activeOrder.subtotal !== undefined && Number(this.activeOrder.subtotal) > 0) {
                    return Number(this.activeOrder.subtotal);
                }
                if (this.activeOrder.items && this.activeOrder.items.length > 0) {
                    return this.activeOrder.items.reduce((sum, item) => sum + Number(item.subtotal || (item.price * item.quantity)), 0);
                }
                return Number(this.activeOrder.total_amount || 0);
            },

            getActiveOrderServiceFee() {
                if (!this.activeOrder) return 0;
                if (this.activeOrder.service_fee !== undefined && Number(this.activeOrder.service_fee) > 0) {
                    return Number(this.activeOrder.service_fee);
                }
                if (this.activeOrder.type !== 'delivery' && this.activeOrder.items && this.activeOrder.total_amount) {
                    const itemsSum = this.activeOrder.items.reduce((sum, item) => sum + Number(item.subtotal || (item.price * item.quantity)), 0);
                    if (Number(this.activeOrder.total_amount) > itemsSum) {
                        return Number(this.activeOrder.total_amount) - itemsSum;
                    }
                }
                return 0;
            },

            getActiveOrderDeliveryFee() {
                if (!this.activeOrder) return 0;
                if (this.activeOrder.delivery_fee !== undefined) {
                    return Number(this.activeOrder.delivery_fee);
                }
                if (this.activeOrder.type === 'delivery' && this.activeOrder.items && this.activeOrder.total_amount) {
                    const itemsSum = this.activeOrder.items.reduce((sum, item) => sum + Number(item.subtotal || (item.price * item.quantity)), 0);
                    if (Number(this.activeOrder.total_amount) > itemsSum) {
                        return Number(this.activeOrder.total_amount) - itemsSum;
                    }
                }
                return 0;
            },

            scheduleCompletedOrderDismissal() {
                if (this.orderDismissTimeout) {
                    clearTimeout(this.orderDismissTimeout);
                    this.orderDismissTimeout = null;
                }
                if (!this.activeOrder || !['completed', 'cancelled'].includes(this.activeOrder.status)) {
                    return;
                }

                let completedAt = this.activeOrder.completed_at;
                if (!completedAt) {
                    if (this.activeOrder.updated_at_timestamp) {
                        completedAt = this.activeOrder.updated_at_timestamp * 1000;
                    } else if (this.activeOrder.savedAt) {
                        completedAt = this.activeOrder.savedAt;
                    } else {
                        completedAt = Date.now();
                    }
                    this.activeOrder.completed_at = completedAt;
                    this.saveActiveOrderToStorage();
                }

                const elapsed = Date.now() - completedAt;
                const remaining = (10 * 60 * 1000) - elapsed;

                if (remaining <= 0) {
                    this.activeOrder = null;
                    this.showOrderTracker = false;
                    this.hideTrackerPill = true;
                    try {
                        localStorage.removeItem('active_order_' + this.vendorSlug);
                    } catch(e) {}
                } else {
                    this.orderDismissTimeout = setTimeout(() => {
                        this.activeOrder = null;
                        this.showOrderTracker = false;
                        this.hideTrackerPill = true;
                        try {
                            localStorage.removeItem('active_order_' + this.vendorSlug);
                        } catch(e) {}
                    }, remaining);
                }
            },

            restoreActiveOrderFromStorage() {
                try {
                    const saved = localStorage.getItem('active_order_' + this.vendorSlug);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (parsed && (parsed.order_number || parsed.tracking_token)) {
                            this.activeOrder = parsed;
                            if (['completed', 'cancelled'].includes(this.activeOrder.status)) {
                                this.scheduleCompletedOrderDismissal();
                            } else {
                                this.startOrderPolling();
                            }
                        }
                    }
                } catch(e) {}
            },

            startOrderPolling() {
                this.stopOrderPolling();
                this.pollOrderStatus();
                this.orderPollInterval = setInterval(() => {
                    this.pollOrderStatus();
                }, 5000);
            },

            stopOrderPolling() {
                if (this.orderPollInterval) {
                    clearInterval(this.orderPollInterval);
                    this.orderPollInterval = null;
                }
            },

            async pollOrderStatus() {
                if (!this.activeOrder || (!this.activeOrder.order_number && !this.activeOrder.tracking_token)) return;
                try {
                    const token = this.activeOrder.tracking_token || '';
                    const targetIdentifier = token || this.activeOrder.order_number;
                    let url = (routes.orderStatus || `/api/m/${this.vendorSlug}/order/___NUM___/status`).replace('___NUM___', encodeURIComponent(targetIdentifier));
                    if (token) {
                        url += (url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(token);
                    }
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            ...(token ? { 'X-Tracking-Token': token } : {})
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success && data.order) {
                            const oldStatus = this.activeOrder.status;
                            const newStatus = data.order.status;

                            let completedAt = this.activeOrder.completed_at;
                            if (['completed', 'cancelled'].includes(newStatus)) {
                                if (!completedAt) {
                                completedAt = data.order.updated_at_timestamp ? (data.order.updated_at_timestamp * 1000) : Date.now();
                                }
                            }

                            this.activeOrder = {
                                ...this.activeOrder,
                                ...data.order,
                                completed_at: completedAt
                            };
                            this.saveActiveOrderToStorage();

                            if (oldStatus && oldStatus !== newStatus) {
                                this.triggerToast((translations.order_number_label || 'Պատվեր #') + data.order.order_number + ': ' + data.order.status_label, 'success', data.order.status_icon);
                                if (navigator.vibrate) {
                                    try { navigator.vibrate([100, 50, 100]); } catch(e) {}
                                }
                            }

                            if (['completed', 'cancelled'].includes(newStatus)) {
                                this.stopOrderPolling();
                                this.scheduleCompletedOrderDismissal();
                            }
                        }
                    } else if (res.status === 404) {
                        this.stopOrderPolling();
                        this.activeOrder = null;
                        try { localStorage.removeItem('active_order_' + this.vendorSlug); } catch(e) {}
                    }
                } catch (e) {
                    console.error('Tracker polling error:', e);
                }
            },

            dismissTrackerPill() {
                if (['completed', 'cancelled'].includes(this.activeOrder?.status)) {
                    this.hideTrackerPill = true;
                } else {
                    this.triggerToast((translations.order_number_label || 'Պատվեր #') + (this.activeOrder?.order_number || '') + ' - ' + (this.activeOrder?.status_label || ''), 'info', 'fa-solid fa-clock');
                }
            },

            closeOrderTracker(hidePill = false) {
                this.showOrderTracker = false;
                if (hidePill && ['completed', 'cancelled'].includes(this.activeOrder?.status)) {
                    this.hideTrackerPill = true;
                }
            },

            clearActiveOrder() {
                if (this.orderDismissTimeout) {
                    clearTimeout(this.orderDismissTimeout);
                    this.orderDismissTimeout = null;
                }
                this.stopOrderPolling();
                this.activeOrder = null;
                this.showOrderTracker = false;
                this.hideTrackerPill = true;
                try {
                    localStorage.removeItem('active_order_' + this.vendorSlug);
                } catch(e) {}
                this.triggerToast('Պատվերի հետևումն ավարտված է', 'info', 'fa-solid fa-circle-check');
            },

            openWaiterModal(type = 'call_waiter') {
                if (!this.isTableFixed) {
                    this.triggerToast('Մատուցողի կանչը հասանելի է միայն սեղանի QR կոդը սկանավորելուց հետո', 'remove', 'fa-solid fa-qrcode');
                    return;
                }
                this.serviceType = type;
                if (this.tableNumber) {
                    this.serviceTable = this.tableNumber;
                }
                this.showWaiterModal = true;
            },

            async submitServiceCall() {
                if (!this.isTableFixed || !this.serviceTable || this.serviceTable.toString().trim() === '') {
                    this.triggerToast('Մատուցողի կանչը հասանելի է միայն սեղանի QR կոդը սկանավորելուց հետո', 'remove', 'fa-solid fa-qrcode');
                    return;
                }

                this.isCallingService = true;

                try {
                    let formattedTable = this.serviceTable.toString().trim();
                    if (!formattedTable.toLowerCase().startsWith('table ') && !formattedTable.startsWith('Սեղան ')) {
                        formattedTable = 'Table ' + formattedTable;
                    }

                    const waiterUrl = routes.callWaiter || `/api/m/${this.vendorSlug}/call-waiter`;
                    const res = await fetch(waiterUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            location_id: this.locationId,
                            table_number: formattedTable,
                            type: this.serviceType
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.showWaiterModal = false;
                        const icon = this.serviceType === 'call_waiter' ? 'fa-solid fa-bell' : (this.serviceType === 'bill_cash' ? 'fa-solid fa-money-bill-wave' : 'fa-solid fa-credit-card');
                        this.triggerToast(data.message, 'success', icon);
                    } else {
                        this.triggerToast(data.message || 'Սխալ՝ կրկին փորձեք', 'remove', 'fa-solid fa-triangle-exclamation');
                    }
                } catch (e) {
                    console.error('Service call error:', e);
                    this.triggerToast('Կապի խնդիր, խնդրում ենք կրկին փորձել', 'remove', 'fa-solid fa-triangle-exclamation');
                } finally {
                    this.isCallingService = false;
                }
            },

            quickCallWaiter() {
                if (this.serviceTable && this.serviceTable.toString().trim() !== '') {
                    this.serviceType = 'call_waiter';
                    this.submitServiceCall();
                } else {
                    this.openWaiterModal('call_waiter');
                }
            },

            quickRequestBill(payment = 'cash') {
                const targetType = payment === 'card' ? 'bill_card' : 'bill_cash';
                if (this.serviceTable && this.serviceTable.toString().trim() !== '') {
                    this.serviceType = targetType;
                    this.submitServiceCall();
                } else {
                    this.openWaiterModal(targetType);
                }
            },

            // AI Waiter Advisor Methods
            selectLanguage(locale) {
                this.selectedLang = locale;
                try { localStorage.setItem('qrmenu_locale_' + this.vendorSlug, locale); } catch(e) {}
                const url = new URL(window.location.href);
                if (url.searchParams.get('lang') !== locale) {
                    url.searchParams.set('lang', locale);
                    window.location.href = url.toString();
                }
            },

            initAiWaiterWelcome() {
                if (vendor.ai_waiter_enabled) {
                    try {
                        const welcomed = localStorage.getItem('ai_waiter_welcomed_' + this.vendorSlug);
                        if (!welcomed) {
                            this.openAiWaiter('intro');
                        }
                    } catch(e) {}
                }
            },

            openAiWaiter(step = null) {
                this.showAiWaiter = true;
                if (step) {
                    this.aiStep = step;
                }
                if (!this.aiSessionId) {
                    this.startAiSession();
                }
            },

            closeAiWaiter() {
                this.showAiWaiter = false;
                try { localStorage.setItem('ai_waiter_welcomed_' + this.vendorSlug, 'true'); } catch(e) {}
            },

            skipToMenu() {
                this.closeAiWaiter();
                this.showWelcomeModal = false;
            },

            async startAiSession() {
                try {
                    const sessionUrl = routes.aiSessionStart || `/api/m/${this.vendorSlug}/ai-waiter/session/start`;
                    const res = await fetch(sessionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            lang: this.selectedLang,
                            location_id: this.locationId,
                            table_number: this.tableNumber || null
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.aiSessionId = data.session_id;
                        this.aiSessionToken = data.session_token;
                        if (data.allowed_languages && data.allowed_languages.length) {
                            this.aiAllowedLanguages = data.allowed_languages;
                        }
                        this.aiCurrentQuestion = data.next_question;
                    }
                } catch (e) {
                    console.error('Failed to start AI session:', e);
                }
            },

            async selectAiLanguage(lang) {
                this.selectedLang = lang;
                try { localStorage.setItem('qrmenu_locale_' + this.vendorSlug, lang); } catch(e) {}

                if (this.aiSessionId) {
                    try {
                        const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                        const url = base + '/' + this.aiSessionId + '/language';
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ language: lang })
                        });
                        const data = await res.json();
                        if (data.success && data.next_question) {
                            this.aiCurrentQuestion = data.next_question;
                        }
                    } catch (e) {
                        console.error('Failed to update AI session language:', e);
                    }
                }
                this.aiStep = 'greeting';
            },

            startAiQuestions() {
                if (!this.aiCurrentQuestion && this.aiSessionId) {
                    this.fetchNextQuestion();
                }
                this.aiStep = 'question';
            },

            async fetchNextQuestion() {
                if (!this.aiSessionId) return;
                try {
                    const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                    const url = base + '/' + this.aiSessionId + '/next-question';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ lang: this.selectedLang })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.is_ready_for_recommendations || !data.next_question) {
                            await this.runAiAnalysisAnimation();
                        } else {
                            this.aiCurrentQuestion = data.next_question;
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch next question:', e);
                }
            },

            async submitAiAnswer(questionKey, optionValue = null, freeText = null) {
                if (!questionKey && this.aiCurrentQuestion) {
                    questionKey = this.aiCurrentQuestion.key;
                }
                const answerText = freeText || this.aiFreeTextInput;
                if (!optionValue && (!answerText || !answerText.trim())) {
                    return;
                }

                if (this.aiCurrentQuestion) {
                    this.aiQuestionHistory.push(JSON.parse(JSON.stringify(this.aiCurrentQuestion)));
                }

                this.aiFreeTextInput = '';
                this.aiLoading = true;

                try {
                    if (!this.aiSessionId) {
                        await this.startAiSession();
                    }

                    const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                    const url = base + '/' + this.aiSessionId + '/answer';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            question_key: questionKey,
                            answer_value: optionValue,
                            free_text: answerText || null,
                            lang: this.selectedLang
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.is_ready_for_recommendations || !data.next_question) {
                            await this.runAiAnalysisAnimation();
                        } else {
                            this.aiCurrentQuestion = data.next_question;
                            this.aiStep = 'question';
                        }
                    } else {
                        await this.runAiAnalysisAnimation();
                    }
                } catch (e) {
                    console.error('Error submitting answer:', e);
                    await this.runAiAnalysisAnimation();
                } finally {
                    this.aiLoading = false;
                }
            },

            backAiQuestion() {
                if (this.aiQuestionHistory.length > 0) {
                    this.aiCurrentQuestion = this.aiQuestionHistory.pop();
                    this.aiStep = 'question';
                } else {
                    this.aiStep = 'greeting';
                }
            },

            skipAiQuestion() {
                if (this.aiCurrentQuestion) {
                    this.submitAiAnswer(this.aiCurrentQuestion.key, 'skip', null);
                } else {
                    this.runAiAnalysisAnimation();
                }
            },

            async runAiAnalysisAnimation() {
                this.aiStep = 'analyzing';
                this.aiAnalysisStep = 1;

                setTimeout(() => { this.aiAnalysisStep = 2; }, 350);
                setTimeout(() => { this.aiAnalysisStep = 3; }, 750);
                setTimeout(() => { this.aiAnalysisStep = 4; }, 1150);

                await this.fetchPersonalizedRecommendations();

                setTimeout(() => {
                    this.aiStep = 'recommendations';
                }, 1450);
            },

            async fetchPersonalizedRecommendations() {
                this.aiLoading = true;
                try {
                    if (!this.aiSessionId) {
                        await this.startAiSession();
                    }
                    const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                    const url = base + '/' + this.aiSessionId + '/recommendations';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            lang: this.selectedLang,
                            location_id: this.locationId
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.aiCommentary = data.commentary || '';
                        this.aiMainRecommendations = data.main_recommendations || [];
                        this.aiSecondaryRecommendations = data.secondary_recommendations || [];
                        this.aiPairingDrink = data.pairing_drink || null;
                        this.aiBundle = data.bundle || null;
                        this.aiRecommendations = data.main_recommendations || [];
                    }
                } catch (e) {
                    console.error('Error fetching recommendations:', e);
                } finally {
                    this.aiLoading = false;
                }
            },

            async addAiDishToCart(dish) {
                if (!dish) return;
                const price = Number(dish.price || dish.base_price || 0);
                this.addToCart(dish.id, dish.name, price, 'Standard', null, 1);

                if (this.aiSessionId) {
                    try {
                        const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                        const url = base + '/' + this.aiSessionId + '/add-to-cart';
                        await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ product_id: dish.id })
                        });
                    } catch (e) {}
                }
            },

            async addAiBundleToCart() {
                if (!this.aiBundle || !this.aiBundle.items || this.aiBundle.items.length === 0) return;

                this.aiBundle.items.forEach(item => {
                    const price = Number(item.price || 0);
                    this.addToCart(item.id, item.name, price, 'Standard', null, 1);
                });

                if (this.aiSessionId) {
                    try {
                        const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                        const url = base + '/' + this.aiSessionId + '/add-to-cart';
                        await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ bundle: this.aiBundle })
                        });
                    } catch (e) {}
                }

                const bundleToast = this.selectedLang === 'en' 
                    ? '✨ Complete Smart Bundle added to cart!' 
                    : (this.selectedLang === 'ru' ? '✨ Полный комбо-набор добавлен в корзину!' : '✨ Ամբողջ առաջարկված փաթեթն ավելացվել է զամբյուղ:');
                this.triggerToast(bundleToast, 'success', 'fa-solid fa-wand-magic-sparkles');
            },

            openAiChat() {
                this.showAiChatDrawer = true;
            },

            closeAiChat() {
                this.showAiChatDrawer = false;
            },

            async sendAiChatMessage(customText = null) {
                const message = (customText || this.aiChatInput || '').trim();
                if (!message) return;

                this.aiChatMessages.push({
                    sender: 'user',
                    text: message,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });

                this.aiChatInput = '';
                this.aiChatLoading = true;

                this.$nextTick(() => {
                    const chatBox = document.getElementById('aiChatMessagesBox');
                    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                });

                try {
                    if (!this.aiSessionId) {
                        await this.startAiSession();
                    }
                    const base = routes.aiSessionBase || `/api/m/${this.vendorSlug}/ai-waiter/session`;
                    const url = base + '/' + this.aiSessionId + '/chat';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            message: message,
                            lang: this.selectedLang,
                            location_id: this.locationId
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.aiChatMessages.push({
                            sender: 'ai',
                            text: data.reply,
                            products: data.suggested_products || [],
                            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        });
                    } else {
                        this.aiChatMessages.push({
                            sender: 'ai',
                            text: this.selectedLang === 'en' 
                                ? 'I am here to help you choose delicious dishes from our menu! What would you like to taste?' 
                                : (this.selectedLang === 'ru' ? 'Я с радостью помогу вам с выбором блюд из меню! Что вам подсказать?' : 'Սիրով կօգնեմ ընտրել Ձեր ճաշակին համապատասխան ուտեստ մեր մենյուից:'),
                            products: [],
                            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        });
                    }
                } catch (e) {
                    console.error('Chat query error:', e);
                } finally {
                    this.aiChatLoading = false;
                    this.$nextTick(() => {
                        const chatBox = document.getElementById('aiChatMessagesBox');
                        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                    });
                }
            },

            resetAiQuiz() {
                this.aiQuestionHistory = [];
                this.aiPreferences = {};
                this.aiFreeTextInput = '';
                this.aiStep = 'intro';
                this.startAiSession();
            },

            // Backwards compatibility methods
            selectQuizOption(category, value) {
                if (this.aiPreferences[category] === value) {
                    this.aiPreferences[category] = null;
                } else {
                    this.aiPreferences[category] = value;
                }
                this.fetchAiRecommendations();
            },

            setPromptPreset(text) {
                this.aiPromptInput = text;
                this.submitAiPrompt();
            },

            submitAiPrompt() {
                this.fetchAiRecommendations();
            },

            async fetchAiRecommendations() {
                await this.fetchPersonalizedRecommendations();
            }
        };
    }

    // Expose globally
    window.createStorefrontApp = createStorefrontApp;
    window.modernBistroApp = function(cfg) { return createStorefrontApp(cfg); };
    window.storefrontApp = function(cfg) { return createStorefrontApp(cfg); };
    window.vibrantGlassApp = function(cfg) { return createStorefrontApp(cfg); };

    // Haptic feedback listener
    document.addEventListener('click', function(e) {
        const el = e.target.closest('button, a, [role="button"], input[type="submit"], input[type="button"], .payment-method-tile, .cart-type-btn, .quiz-chip-btn, .lang-pill-btn, .prompt-preset-chip, .ai-question-opt-btn, .ai-action-btn, .category-chip, .order-pill-open-btn');
        if (el && window.navigator && typeof window.navigator.vibrate === 'function') {
            try { window.navigator.vibrate(25); } catch(err) {}
        }
    }, { passive: true });

})(window);
