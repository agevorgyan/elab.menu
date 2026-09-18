<!-- Waiter Call & Bill Request Service Modal -->
<div x-show="showWaiterModal" 
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1.25rem; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px);">
    
    <div @click.away="showWaiterModal = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translateY(10px)"
         x-transition:enter-end="opacity-100 scale-100 translateY(0)"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translateY(0)"
         x-transition:leave-end="opacity-0 scale-95 translateY(10px)"
         style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; width: 100%; max-width: 420px; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; color: var(--text-main);">
        
        <!-- Close Button -->
        <button type="button" @click="showWaiterModal = false" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 1.25rem;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(225, 29, 72, 0.15); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">
                <i class="fa-solid fa-bell-concierge"></i>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 800; font-family: 'Outfit', sans-serif;">
                {{ __('menu.service_modal_title') }}
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                {{ __('menu.service_modal_desc') }}
            </p>
        </div>

        <!-- Service Choice Cards -->
        <div style="display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1.25rem;">
            <!-- Call Waiter -->
            <button type="button" 
                    @click="serviceType = 'call_waiter'" 
                    :style="serviceType === 'call_waiter' ? 'border-color: var(--primary); background: rgba(225, 29, 72, 0.08);' : 'border-color: var(--border-color); background: var(--bg-main);'"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-radius: 12px; border-width: 2px; border-style: solid; text-align: left; cursor: pointer; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.2); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">
                            {{ __('menu.call_waiter') }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            Մոտենալ սեղանին
                        </div>
                    </div>
                </div>
                <i class="fa-solid fa-circle-check" :style="serviceType === 'call_waiter' ? 'color: var(--primary); opacity: 1;' : 'opacity: 0;'"></i>
            </button>

            <!-- Bill: Cash -->
            <button type="button" 
                    @click="serviceType = 'bill_cash'" 
                    :style="serviceType === 'bill_cash' ? 'border-color: var(--primary); background: rgba(225, 29, 72, 0.08);' : 'border-color: var(--border-color); background: var(--bg-main);'"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-radius: 12px; border-width: 2px; border-style: solid; text-align: left; cursor: pointer; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.2); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">
                            {{ __('menu.request_bill_cash') }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            Վճարում կանխիկ դրամով
                        </div>
                    </div>
                </div>
                <i class="fa-solid fa-circle-check" :style="serviceType === 'bill_cash' ? 'color: var(--primary); opacity: 1;' : 'opacity: 0;'"></i>
            </button>

            <!-- Bill: Card -->
            <button type="button" 
                    @click="serviceType = 'bill_card'" 
                    :style="serviceType === 'bill_card' ? 'border-color: var(--primary); background: rgba(225, 29, 72, 0.08);' : 'border-color: var(--border-color); background: var(--bg-main);'"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-radius: 12px; border-width: 2px; border-style: solid; text-align: left; cursor: pointer; transition: all 0.2s ease;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.2); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">
                            {{ __('menu.request_bill_card') }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            Վճարում POS տերմինալով (Քարտով)
                        </div>
                    </div>
                </div>
                <i class="fa-solid fa-circle-check" :style="serviceType === 'bill_card' ? 'color: var(--primary); opacity: 1;' : 'opacity: 0;'"></i>
            </button>
        </div>

        <!-- Table Number Input Field -->
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                <i class="fa-solid fa-chair" style="color: var(--primary); margin-right: 0.3rem;"></i> {{ __('menu.specify_table') }}
            </label>
            <input type="text" 
                   x-model="serviceTable" 
                   placeholder="օր. 4 կամ Սեղան 4"
                   style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); font-size: 0.95rem; outline: none; transition: border-color 0.2s ease;"
                   onfocus="this.style.borderColor='var(--primary)'"
                   onblur="this.style.borderColor='var(--border-color)'">
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" 
                    @click="showWaiterModal = false" 
                    style="flex: 1; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-muted); font-weight: 600; cursor: pointer;">
                {{ __('menu.cancel') }}
            </button>

            <button type="button" 
                    @click="submitServiceCall()" 
                    :disabled="isCallingService"
                    style="flex: 2; padding: 0.75rem; border-radius: 10px; border: none; background: var(--primary); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);">
                <i x-show="!isCallingService" class="fa-solid fa-paper-plane"></i>
                <i x-show="isCallingService" class="fa-solid fa-spinner fa-spin"></i>
                <span>{{ __('menu.send') }}</span>
            </button>
        </div>
    </div>
</div>
