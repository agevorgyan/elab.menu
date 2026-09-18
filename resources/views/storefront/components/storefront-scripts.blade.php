<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ route("client.sw", ["vendor_slug" => $vendor->slug]) }}');
    }

    function createStorefrontApp(customConfig = {}) {
        return {
            activeCat: customConfig.activeCat || 'cat-{{ $categories->first()?->id ?? 1 }}',
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
            wifiCopied: false,
            customerName: '',
            customerPhone: '',
            isTableFixed: {{ !empty($table) ? 'true' : 'false' }},
            tableNumber: customConfig.tableNumber !== undefined ? customConfig.tableNumber : '{{ $table ? "Table " . $table : "" }}',
            showWaiterModal: false,
            serviceType: 'call_waiter',
            serviceTable: customConfig.tableNumber !== undefined && customConfig.tableNumber ? customConfig.tableNumber : '{{ $table ? "Table " . $table : "" }}',
            isCallingService: false,
            marketingOptIn: true,
            orderNotes: '',
            toast: {
                show: false,
                message: '',
                type: 'success',
                icon: 'fa-solid fa-circle-check',
                timeout: null
            },
            
            init() {
                this.restoreActiveOrderFromStorage();
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
                if (!this.search) return true;
                return title.includes(this.search.toLowerCase());
            },

            selectDish(dish) {
                if (!dish.variations || dish.variations.length <= 1) {
                    const v = (dish.variations && dish.variations.length === 1) ? dish.variations[0] : null;
                    this.addToCart(
                        dish.id,
                        dish.name,
                        v ? Number(v.price) : Number(dish.base_price),
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
                        name: name,
                        price: Number(price),
                        variation_name: variationName,
                        variation_id: variationId,
                        qty: qty
                    });
                }
                const addedText = '{{ __('menu.added_to_cart') }}';
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
                    const removedText = '{{ __('menu.removed_from_cart') }}';
                    this.cart.splice(idx, 1);
                    this.triggerToast('«' + removedName + '» ' + removedText, 'remove', 'fa-solid fa-trash-can');
                }
            },

            get cartTotalCount() {
                return this.cart.reduce((a, b) => a + b.qty, 0);
            },

            get cartTotalPrice() {
                return this.cart.reduce((a, b) => a + (b.price * b.qty), 0);
            },

            async submitOrder(type) {
                if (this.cart.length === 0) return;
                const res = await fetch('{{ route("client.order.submit", ["vendor_slug" => $vendor->slug]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        location_id: {{ $location?->id ?? 1 }},
                        table_number: this.tableNumber || '{{ $table ?? "Table 4" }}',
                        type: type,
                        customer_name: this.customerName || 'Guest',
                        customer_phone: this.customerPhone || null,
                        customer_email: this.customerEmail || null,
                        marketing_opt_in: this.marketingOptIn,
                        notes: this.orderNotes,
                        items: this.cart.map(c => ({
                            product_id: c.id,
                            variation_id: c.variation_id || null,
                            variation_name: c.variation_name || null,
                            quantity: c.qty
                        }))
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const orderItems = this.cart.map(c => ({
                        name: c.name,
                        variation_name: c.variation_name,
                        quantity: c.qty,
                        price: c.price,
                        subtotal: c.price * c.qty
                    }));

                    this.activeOrder = {
                        id: data.order_id || null,
                        order_number: data.order_number,
                        status: data.status || 'pending',
                        status_step: 1,
                        status_percent: 25,
                        status_label: data.status_label || '{{ __('menu.status_pending') }}',
                        status_desc: '{{ __('menu.status_desc_pending') }}',
                        status_icon: 'fa-solid fa-clock',
                        total_amount: data.total_amount,
                        table_number: this.tableNumber || '{{ $table ? "Table " . $table : "" }}',
                        currency: '{{ $vendor->currency }}',
                        created_at_time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        created_at_human: 'հենց նոր',
                        items: orderItems
                    };
                    this.hideTrackerPill = false;
                    this.saveActiveOrderToStorage();
                    this.cart = [];
                    this.showCartModal = false;
                    this.startOrderPolling();

                    if (data.whatsapp_url && type === 'whatsapp') {
                        window.location.href = data.whatsapp_url;
                    } else {
                        this.showOrderTracker = true;
                        const successMsg = '{{ __('menu.order_submitted_prefix') }}' + data.order_number + '{{ __('menu.order_submitted_suffix') }}';
                        this.triggerToast(successMsg, 'success', 'fa-solid fa-circle-check');
                    }
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
                        this.triggerToast('{{ __('menu.wifi_copied') }}', 'success', 'fa-solid fa-check');
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
                    this.triggerToast('{{ __('menu.wifi_copied') }}', 'success', 'fa-solid fa-check');
                    setTimeout(() => { this.wifiCopied = false; }, 2500);
                } catch(e) {}
                document.body.removeChild(ta);
            },

            saveActiveOrderToStorage() {
                if (this.activeOrder) {
                    try {
                        localStorage.setItem('active_order_{{ $vendor->slug }}', JSON.stringify({
                            ...this.activeOrder,
                            savedAt: Date.now()
                        }));
                    } catch(e) {}
                }
            },

            restoreActiveOrderFromStorage() {
                try {
                    const saved = localStorage.getItem('active_order_{{ $vendor->slug }}');
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (Date.now() - (parsed.savedAt || 0) < 12 * 3600 * 1000) {
                            this.activeOrder = parsed;
                            if (!['completed', 'cancelled'].includes(parsed.status)) {
                                this.startOrderPolling();
                            }
                        } else {
                            localStorage.removeItem('active_order_{{ $vendor->slug }}');
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
                if (!this.activeOrder || !this.activeOrder.order_number) return;
                try {
                    const url = '{{ route("client.order.status", ["vendor_slug" => $vendor->slug, "order_number" => "___NUM___"]) }}'.replace('___NUM___', encodeURIComponent(this.activeOrder.order_number));
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success && data.order) {
                            const oldStatus = this.activeOrder.status;
                            this.activeOrder = {
                                ...this.activeOrder,
                                ...data.order
                            };
                            this.saveActiveOrderToStorage();

                            if (oldStatus && oldStatus !== data.order.status) {
                                this.triggerToast('{{ __('menu.order_number_label') }}' + data.order.order_number + ': ' + data.order.status_label, 'success', data.order.status_icon);
                                if (navigator.vibrate) {
                                    try { navigator.vibrate([100, 50, 100]); } catch(e) {}
                                }
                            }

                            if (['completed', 'cancelled'].includes(data.order.status)) {
                                this.stopOrderPolling();
                            }
                        }
                    } else if (res.status === 404) {
                        this.stopOrderPolling();
                        this.activeOrder = null;
                        try { localStorage.removeItem('active_order_{{ $vendor->slug }}'); } catch(e) {}
                    }
                } catch (e) {
                    console.error('Tracker polling error:', e);
                }
            },

            openWaiterModal(type = 'call_waiter') {
                this.serviceType = type;
                if (!this.serviceTable && this.tableNumber) {
                    this.serviceTable = this.tableNumber;
                }
                this.showWaiterModal = true;
            },

            async submitServiceCall() {
                if (!this.serviceTable || this.serviceTable.toString().trim() === '') {
                    this.triggerToast('{{ __('menu.table_number_prompt') }}', 'remove', 'fa-solid fa-chair');
                    return;
                }

                this.isCallingService = true;

                try {
                    let formattedTable = this.serviceTable.toString().trim();
                    if (!formattedTable.toLowerCase().startsWith('table ') && !formattedTable.startsWith('Սեղան ')) {
                        formattedTable = 'Table ' + formattedTable;
                    }

                    const res = await fetch('{{ route("client.waiter.call", ["vendor_slug" => $vendor->slug]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            location_id: {{ $location?->id ?? 1 }},
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
            }
        };
    }

    // Backwards-compatible aliases for all theme directives
    function modernBistroApp() {
        return createStorefrontApp();
    }
    function storefrontApp() {
        return createStorefrontApp();
    }
    function vibrantGlassApp() {
        return createStorefrontApp();
    }
</script>
