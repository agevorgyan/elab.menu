<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ route("client.sw", ["vendor_slug" => $vendor->slug]) }}');
    }

    function createStorefrontApp(customConfig = {}) {
        return {
            activeCat: customConfig.activeCat || 'cat-{{ $categories->first()?->id ?? 1 }}',
            search: '',
            cart: [],
            showCartModal: false,
            showVariationModal: false,
            showPWA: true,
            selectedDish: null,
            selectedVariation: null,
            variationQty: 1,
            customerName: '',
            customerPhone: '',
            customerEmail: '',
            tableNumber: customConfig.tableNumber !== undefined ? customConfig.tableNumber : '{{ $table ? "Table " . $table : "" }}',
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
                    if (data.whatsapp_url && type === 'whatsapp') {
                        window.location.href = data.whatsapp_url;
                    } else {
                        const successMsg = '{{ __('menu.order_submitted_prefix') }}' + data.order_number + '{{ __('menu.order_submitted_suffix') }}';
                        alert(successMsg);
                        this.cart = [];
                        this.showCartModal = false;
                    }
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
