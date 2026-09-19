@extends('layouts.app')

@section('title', 'Vendor Directory - SuperAdmin')

@section('styles')
<style>
    .vendors-desktop-table {
        display: block;
    }
    .vendors-mobile-cards {
        display: none;
    }

    .search-filter-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-card);
    }

    .filter-pill {
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--border-color);
        background: var(--input-bg);
        color: var(--text-muted);
        transition: all 0.2s ease;
    }
    .filter-pill:hover {
        color: var(--text-main);
        background: var(--nav-hover);
    }
    .filter-pill.active {
        background: var(--nav-active);
        border-color: var(--nav-active-border);
        color: var(--primary);
        font-weight: 700;
    }

    .vendor-avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--border-color);
        flex-shrink: 0;
        background: var(--input-bg);
    }

    .legal-card-chip {
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.6rem 0.75rem;
        font-size: 0.78rem;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    @media (max-width: 992px) {
        .vendors-desktop-table {
            display: none !important;
        }
        .vendors-mobile-cards {
            display: flex !important;
            flex-direction: column;
            gap: 1rem;
        }
        .vendor-mobile-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 1.25rem;
            box-shadow: var(--shadow-card);
            transition: all 0.2s ease;
        }
    }
</style>
@endsection

@section('content')
<div x-data="{
    search: '',
    typeFilter: 'all',
    statusFilter: 'all',
    planFilter: 'all',
    showModal: false,
    matches(name, slug, taxId, legalName, director, manager, type, isActive, plan) {
        const q = this.search.toLowerCase().trim();
        const matchesSearch = !q || 
            (name && name.toLowerCase().includes(q)) || 
            (slug && slug.toLowerCase().includes(q)) || 
            (taxId && taxId.toLowerCase().includes(q)) || 
            (legalName && legalName.toLowerCase().includes(q)) || 
            (director && director.toLowerCase().includes(q)) || 
            (manager && manager.toLowerCase().includes(q));
        const matchesType = this.typeFilter === 'all' || type === this.typeFilter;
        const matchesStatus = this.statusFilter === 'all' || (this.statusFilter === 'active' ? isActive : !isActive);
        const matchesPlan = this.planFilter === 'all' || plan.toLowerCase() === this.planFilter.toLowerCase();
        return matchesSearch && matchesType && matchesStatus && matchesPlan;
    }
}">
    <!-- Header Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-indigo">
                    <i class="fa-solid fa-store"></i> Վենդորների Ցուցակ
                </span>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Ընդհանուր՝ {{ $vendors->count() }} վենդոր</span>
            </div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0;">
                Վենդորների Կատալոգ և Կառավարում
            </h1>
        </div>
        <button class="btn btn-primary" @click="showModal = true" style="border-radius: 12px; font-size: 0.88rem; padding: 0.65rem 1.25rem;">
            <i class="fa-solid fa-plus"></i> Ստեղծել Նոր Վենդոր
        </button>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid-4" style="gap: 1rem; margin-bottom: 1.5rem;">
        <div class="stat-kpi-card" style="padding: 1.15rem 1.25rem;">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Գրանցված Վենդորներ</div>
            <div style="font-size: 1.85rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin-top: 0.2rem;">
                {{ $vendors->count() }}
            </div>
        </div>
        <div class="stat-kpi-card" style="padding: 1.15rem 1.25rem;">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Ակտիվ Վիճակում</div>
            <div style="font-size: 1.85rem; font-weight: 800; font-family: 'Outfit'; color: #10b981; margin-top: 0.2rem;">
                {{ $vendors->where('is_active', true)->count() }}
            </div>
        </div>
        <div class="stat-kpi-card" style="padding: 1.15rem 1.25rem;">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Կասեցված</div>
            <div style="font-size: 1.85rem; font-weight: 800; font-family: 'Outfit'; color: #ef4444; margin-top: 0.2rem;">
                {{ $vendors->where('is_active', false)->count() }}
            </div>
        </div>
        <div class="stat-kpi-card" style="padding: 1.15rem 1.25rem;">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Ընդհանուր Մասնաճյուղեր</div>
            <div style="font-size: 1.85rem; font-weight: 800; font-family: 'Outfit'; color: var(--primary); margin-top: 0.2rem;">
                {{ $vendors->sum(fn($v) => $v->locations->count()) }}
            </div>
        </div>
    </div>

    <!-- Live Search & Interactive Filter Bar -->
    <div class="search-filter-panel">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <!-- Search input -->
            <div style="flex: 1; min-width: 260px; position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                <input type="text" x-model="search" placeholder="Որոնել ըստ անվանման, հասցեի, ՀՎՀՀ-ի, տնօրենի..." class="form-input" style="padding-left: 2.5rem; padding-right: 2rem; border-radius: 12px;">
                <button x-show="search.length > 0" @click="search = ''" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Type Filter Pills -->
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;">
                <span class="filter-pill" :class="{ 'active': typeFilter === 'all' }" @click="typeFilter = 'all'">Բոլորը</span>
                <span class="filter-pill" :class="{ 'active': typeFilter === 'restaurant' }" @click="typeFilter = 'restaurant'">🍽️ Ռեստորան</span>
                <span class="filter-pill" :class="{ 'active': typeFilter === 'cafe' }" @click="typeFilter = 'cafe'">☕ Սրճարան</span>
                <span class="filter-pill" :class="{ 'active': typeFilter === 'hotel' }" @click="typeFilter = 'hotel'">🏨 Հյուրանոց</span>
            </div>

            <!-- Status & Plan Dropdowns -->
            <div style="display: flex; gap: 0.65rem; align-items: center;">
                <select x-model="statusFilter" class="form-select" style="width: auto; padding: 0.5rem 0.85rem; border-radius: 10px; font-size: 0.8rem;">
                    <option value="all">Բոլոր կարգավիճակները</option>
                    <option value="active">🟢 Միայն Ակտիվները</option>
                    <option value="suspended">🔴 Կասեցվածները</option>
                </select>

                <select x-model="planFilter" class="form-select" style="width: auto; padding: 0.5rem 0.85rem; border-radius: 10px; font-size: 0.8rem;">
                    <option value="all">Բոլոր փաթեթները</option>
                    @foreach($plans as $pl)
                        <option value="{{ $pl->slug }}">{{ $pl->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="glass-card vendors-desktop-table" style="overflow: hidden; margin-bottom: 2rem;">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Վենդոր և Մենյու</th>
                        <th>Տեսակ</th>
                        <th style="min-width: 240px;">Իրավաբանական Տվյալներ (ՀՎՀՀ)</th>
                        <th>Մասնաճյուղ</th>
                        <th>Թեմա</th>
                        <th>Փաթեթ</th>
                        <th>Կարգավիճակ</th>
                        <th style="text-align: right; min-width: 140px;">Գործողություններ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $v)
                        @php
                            $opAddress = $v->operating_address ?? ($v->locations->first()?->address ?? '');
                            $taxId = $v->tax_id ?? '';
                            $legalName = $v->legal_name ?? '';
                            $dirName = $v->director_name ?? '';
                            $dirPhone = $v->director_phone ?? '';
                            $mgrName = $v->contact_person_name ?? '';
                            $mgrPhone = $v->contact_person_phone ?? '';
                            $planSlug = strtolower($v->plan?->slug ?? ($v->subscription_plan ?? 'pro'));
                        @endphp
                        <tr x-show="matches('{{ addslashes($v->name) }}', '{{ addslashes($v->slug) }}', '{{ addslashes($taxId) }}', '{{ addslashes($legalName) }}', '{{ addslashes($dirName.' '.$dirPhone) }}', '{{ addslashes($mgrName.' '.$mgrPhone) }}', '{{ $v->type }}', {{ $v->is_active ? 'true' : 'false' }}, '{{ $planSlug }}')">
                            <!-- Vendor & Menu -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.85rem;">
                                    <div class="vendor-avatar" style="display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem; color: var(--text-main); overflow: hidden;">
                                        @if($v->logo)
                                            <img src="{{ $v->logo }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            {{ mb_substr($v->name, 0, 2) }}
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" style="font-weight: 700; color: var(--text-main); text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                                            <span>{{ $v->name }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.68rem; color: var(--primary);"></i>
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            /m/{{ $v->slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td>
                                <span style="font-size: 0.8rem; font-weight: 600; text-transform: capitalize; color: var(--text-main);">
                                    @if($v->type === 'restaurant') 🍽️ Ռեստորան
                                    @elseif($v->type === 'cafe') ☕ Սրճարան
                                    @else 🏨 Հյուրանոց
                                    @endif
                                </span>
                            </td>

                            <!-- Legal Details (ՀՎՀՀ, Address, Contacts) -->
                            <td>
                                <div class="legal-card-chip">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem;">
                                        <span style="font-weight: 700; color: var(--text-main);">{{ $v->legal_name ?? $v->name }}</span>
                                        @if($v->tax_id)
                                            <span style="font-size: 0.72rem; color: var(--primary); font-weight: 800; background: var(--badge-bg); padding: 0.1rem 0.4rem; border-radius: 4px;">
                                                ՀՎՀՀ: {{ $v->tax_id }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($opAddress)
                                        <div style="color: var(--text-muted); font-size: 0.72rem; display: flex; align-items: center; gap: 0.35rem;">
                                            <i class="fa-solid fa-map-pin" style="color: #ef4444; font-size: 0.65rem;"></i>
                                            <span>{{ Str::limit($opAddress, 38) }}</span>
                                        </div>
                                    @endif
                                    @if($v->director_name)
                                        <div style="color: var(--text-muted); font-size: 0.72rem;">
                                            <strong>Տնօրեն՝</strong> {{ $v->director_name }} @if($v->director_phone) <span style="color: var(--text-main); font-weight: 600;">({{ $v->director_phone }})</span> @endif
                                        </div>
                                    @endif
                                    @if($v->contact_person_name)
                                        <div style="color: var(--text-muted); font-size: 0.72rem;">
                                            <strong>Մենեջեր՝</strong> {{ $v->contact_person_name }} @if($v->contact_person_phone) <span style="color: var(--text-main); font-weight: 600;">({{ $v->contact_person_phone }})</span> @endif
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Locations -->
                            <td>
                                <span class="badge badge-indigo">
                                    <i class="fa-solid fa-location-dot"></i> {{ $v->locations->count() }} տեղ
                                </span>
                            </td>

                            <!-- Selected Theme -->
                            <td>
                                <span class="badge badge-cyan">
                                    <i class="fa-solid fa-palette"></i> {{ $v->menuTemplate?->name ?? 'Default' }}
                                </span>
                            </td>

                            <!-- Subscription Plan -->
                            <td>
                                <span class="plan-pill plan-{{ $planSlug }}">
                                    {{ $v->plan?->name ?? strtoupper($v->subscription_plan ?? 'pro') }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td>
                                @if($v->is_active)
                                    <span class="badge badge-emerald">
                                        <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Ակտիվ
                                    </span>
                                @else
                                    <span class="badge badge-rose">
                                        <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Կասեցված
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                    <form action="{{ route('superadmin.vendors.toggle', $v->id) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-radius: 8px;" title="{{ $v->is_active ? 'Կասեցնել' : 'Ակտիվացնել' }}">
                                            @if($v->is_active)
                                                <i class="fa-solid fa-pause" style="color: #ef4444;"></i>
                                            @else
                                                <i class="fa-solid fa-play" style="color: #10b981;"></i>
                                            @endif
                                        </button>
                                    </form>

                                    <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-radius: 8px;" title="Բացել Մենյուն">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards View -->
    <div class="vendors-mobile-cards">
        @foreach($vendors as $v)
            @php
                $mobileOpAddress = $v->operating_address ?? ($v->locations->first()?->address ?? '');
                $taxId = $v->tax_id ?? '';
                $legalName = $v->legal_name ?? '';
                $dirName = $v->director_name ?? '';
                $dirPhone = $v->director_phone ?? '';
                $mgrName = $v->contact_person_name ?? '';
                $mgrPhone = $v->contact_person_phone ?? '';
                $planSlug = strtolower($v->plan?->slug ?? ($v->subscription_plan ?? 'pro'));
            @endphp
            <div class="vendor-mobile-card" x-show="matches('{{ addslashes($v->name) }}', '{{ addslashes($v->slug) }}', '{{ addslashes($taxId) }}', '{{ addslashes($legalName) }}', '{{ addslashes($dirName.' '.$dirPhone) }}', '{{ addslashes($mgrName.' '.$mgrPhone) }}', '{{ $v->type }}', {{ $v->is_active ? 'true' : 'false' }}, '{{ $planSlug }}')">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.85rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="vendor-avatar" style="display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem; color: var(--text-main); overflow: hidden;">
                            @if($v->logo)
                                <img src="{{ $v->logo }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                {{ mb_substr($v->name, 0, 2) }}
                            @endif
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">{{ $v->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">/m/{{ $v->slug }}</div>
                        </div>
                    </div>
                    <div>
                        @if($v->is_active)
                            <span class="badge badge-emerald">
                                <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Ակտիվ
                            </span>
                        @else
                            <span class="badge badge-rose">
                                <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Կասեցված
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Badges Row -->
                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                    <span class="badge badge-indigo">
                        @if($v->type === 'restaurant') 🍽️ Ռեստորան
                        @elseif($v->type === 'cafe') ☕ Սրճարան
                        @else 🏨 Հյուրանոց
                        @endif
                    </span>
                    <span class="plan-pill plan-{{ $planSlug }}">
                        {{ $v->plan?->name ?? strtoupper($v->subscription_plan ?? 'pro') }}
                    </span>
                    <span class="badge badge-cyan">
                        <i class="fa-solid fa-location-dot"></i> {{ $v->locations->count() }} տեղ
                    </span>
                    <span class="badge badge-purple">
                        <i class="fa-solid fa-palette"></i> {{ $v->menuTemplate?->name ?? 'Default' }}
                    </span>
                </div>

                <!-- Legal Details Mobile Card -->
                <div class="legal-card-chip" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <span style="font-weight: 700; color: var(--text-main);">{{ $v->legal_name ?? $v->name }}</span>
                        @if($v->tax_id)
                            <span style="font-size: 0.72rem; color: var(--primary); font-weight: 800; background: var(--badge-bg); padding: 0.1rem 0.4rem; border-radius: 4px;">
                                ՀՎՀՀ: {{ $v->tax_id }}
                            </span>
                        @endif
                    </div>
                    @if($mobileOpAddress)
                        <div style="color: var(--text-muted); font-size: 0.72rem; display: flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-solid fa-map-pin" style="color: #ef4444; font-size: 0.65rem;"></i>
                            <span>{{ $mobileOpAddress }}</span>
                        </div>
                    @endif
                    @if($v->director_name)
                        <div style="color: var(--text-muted); font-size: 0.72rem;">
                            <strong>Տնօրեն՝</strong> {{ $v->director_name }} @if($v->director_phone) ({{ $v->director_phone }}) @endif
                        </div>
                    @endif
                    @if($v->contact_person_name)
                        <div style="color: var(--text-muted); font-size: 0.72rem;">
                            <strong>Մենեջեր՝</strong> {{ $v->contact_person_name }} @if($v->contact_person_phone) ({{ $v->contact_person_phone }}) @endif
                        </div>
                    @endif
                </div>

                <!-- Action buttons -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                    <form action="{{ route('superadmin.vendors.toggle', $v->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.8rem; padding: 0.55rem;">
                            {{ $v->is_active ? 'Կասեցնել' : 'Ակտիվացնել' }}
                        </button>
                    </form>
                    <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" class="btn btn-primary" style="justify-content: center; font-size: 0.8rem; padding: 0.55rem;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Մենյու
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modern Create Vendor Modal -->
    <div x-show="showModal" x-transition.opacity class="modern-modal-overlay" style="display: none;">
        <div class="modern-modal-box" @click.outside="showModal = false">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(236, 72, 153, 0.2)); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div>
                        <h3 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            Ստեղծել Նոր Վենդոր
                        </h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Ռեստորանի / սրճարանի հաշվի ստեղծում</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" style="background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('superadmin.vendors.store') }}" method="POST">
                @csrf
                
                <!-- Section 1: Business Details -->
                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        🏢 Բիզնես Տվյալներ
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 0.85rem;">
                        <div>
                            <label class="form-label">Ֆիրմային Անվանում *</label>
                            <input type="text" name="name" required class="form-input" placeholder="օր․ Havana Lounge">
                        </div>
                        <div>
                            <label class="form-label">Բիզնեսի Տեսակ *</label>
                            <select name="type" class="form-select">
                                <option value="restaurant">🍽️ Ռեստորան</option>
                                <option value="cafe">☕ Սրճարան</option>
                                <option value="hotel">🏨 Հյուրանոց և Լաունջ</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Հեռախոսահամար</label>
                        <input type="text" name="phone" class="form-input" placeholder="+374 10 123456">
                    </div>
                </div>

                <!-- Section 2: Owner Account -->
                <div style="margin-bottom: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        👤 Սեփականատիրոջ Մուտքանուն
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 0.85rem;">
                        <div>
                            <label class="form-label">Սեփականատիրոջ Անուն *</label>
                            <input type="text" name="owner_name" required class="form-input" placeholder="Անուն Ազգանուն">
                        </div>
                        <div>
                            <label class="form-label">Էլ․ Փոստ (Login Email) *</label>
                            <input type="email" name="email" required class="form-input" placeholder="owner@restaurant.am">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Նախնական Գաղտնաբառ *</label>
                        <input type="password" name="password" required value="password" class="form-input">
                    </div>
                </div>

                <!-- Section 3: Theme & Plan -->
                <div style="margin-bottom: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        🎨 Թեմա և Բաժանորդագրություն
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
                        <div>
                            <label class="form-label">Մենյուի Թեմա *</label>
                            <select name="menu_template_id" class="form-select">
                                @foreach($templates as $tmpl)
                                    <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Բաժանորդագրության Փաթեթ *</label>
                            <select name="subscription_plan" class="form-select">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->slug }}" {{ $plan->slug === 'pro' ? 'selected' : '' }}>
                                        {{ $plan->name }} ({{ number_format($plan->price) }} ֏)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" @click="showModal = false" style="border-radius: 10px;">
                        Չեղարկել
                    </button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 10px;">
                        <i class="fa-solid fa-plus"></i> Ստեղծել Վենդոր
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
