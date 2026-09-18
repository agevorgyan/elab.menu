@extends('layouts.app')

@section('title', 'Manage Vendors - SuperAdmin')

@section('styles')
<style>
    .vendors-desktop-table {
        display: block;
    }
    .vendors-mobile-cards {
        display: none;
    }

    @media (max-width: 768px) {
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
            border-radius: 16px;
            padding: 1.15rem;
            box-shadow: var(--shadow-card);
        }
        .modal-grid {
            grid-template-columns: 1fr !important;
        }
        .header-action-bar {
            flex-direction: column;
            align-items: stretch !important;
            gap: 1rem;
        }
        .header-action-bar button {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="header-action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--text-main);">Vendor Directory</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage cafes, restaurants, and hotels on the platform.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('newVendorModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Create Vendor
    </button>
</div>

<!-- Desktop Table View -->
<div class="card vendors-desktop-table">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                <th style="padding: 0.75rem;">Logo & Name</th>
                <th style="padding: 0.75rem;">Type</th>
                <th style="padding: 0.75rem;">Legal Details (ՀՎՀՀ)</th>
                <th style="padding: 0.75rem;">Locations</th>
                <th style="padding: 0.75rem;">Selected Theme</th>
                <th style="padding: 0.75rem;">Subscription</th>
                <th style="padding: 0.75rem;">Status</th>
                <th style="padding: 0.75rem; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $v)
                <tr style="border-bottom: 1px solid var(--table-row-border);">
                    <td style="padding: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                        <img src="{{ $v->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&q=80' }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                        <div>
                            <div style="font-weight: 700; color: var(--text-main);">{{ $v->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">/m/{{ $v->slug }}</div>
                        </div>
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-main);"><span style="text-transform: capitalize;">{{ $v->type }}</span></td>
                    <td style="padding: 0.75rem;">
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">{{ $v->legal_name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--primary);">ՀՎՀՀ: {{ $v->tax_id ?? 'N/A' }}</div>
                        @if($v->director_name)
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Տնօրեն: {{ $v->director_name }}</div>
                        @endif
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-main);">{{ $v->locations->count() }} Locations</td>
                    <td style="padding: 0.75rem; color: var(--text-main);">{{ $v->menuTemplate?->name ?? 'Default' }}</td>
                    <td style="padding: 0.75rem;"><span style="background: var(--badge-bg); color: var(--badge-text); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">{{ $v->plan?->name ?? strtoupper($v->subscription_plan) }}</span></td>
                    <td style="padding: 0.75rem;">
                        @if($v->is_active)
                            <span style="color: #10b981; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Active</span>
                        @else
                            <span style="color: #ef4444; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Suspended</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem; text-align: right;">
                        <form action="{{ route('superadmin.vendors.toggle', $v->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                {{ $v->is_active ? 'Suspend' : 'Activate' }}
                            </button>
                        </form>
                        <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                            <i class="fa-solid fa-eye"></i> View Menu
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Mobile Cards View -->
<div class="vendors-mobile-cards">
    @foreach($vendors as $v)
        <div class="vendor-mobile-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="{{ $v->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&q=80' }}" style="width: 46px; height: 46px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-main);">{{ $v->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">/m/{{ $v->slug }}</div>
                    </div>
                </div>
                <div>
                    @if($v->is_active)
                        <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Active
                        </span>
                    @else
                        <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Suspended
                        </span>
                    @endif
                </div>
            </div>

            <!-- Badges Row -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                <span style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize;">
                    <i class="fa-solid fa-utensils" style="color: var(--primary); font-size: 0.7rem; margin-right: 0.25rem;"></i> {{ $v->type }}
                </span>
                <span style="background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--border-color); padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> {{ $v->plan?->name ?? strtoupper($v->subscription_plan) }}
                </span>
                <span style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-muted); padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                    <i class="fa-solid fa-location-dot" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> {{ $v->locations->count() }} Loc
                </span>
                <span style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-muted); padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                    <i class="fa-solid fa-palette" style="font-size: 0.7rem; margin-right: 0.25rem;"></i> {{ $v->menuTemplate?->name ?? 'Default' }}
                </span>
            </div>

            <!-- Legal Info Box -->
            @if($v->legal_name || $v->tax_id || $v->director_name)
                <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.65rem 0.85rem; margin-bottom: 0.85rem; font-size: 0.8rem;">
                    @if($v->legal_name)
                        <div style="font-weight: 600; color: var(--text-main);">{{ $v->legal_name }}</div>
                    @endif
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.2rem; color: var(--text-muted); font-size: 0.75rem;">
                        @if($v->tax_id)
                            <span>ՀՎՀՀ: <strong style="color: var(--primary);">{{ $v->tax_id }}</strong></span>
                        @endif
                        @if($v->director_name)
                            <span>Տնօրեն: <strong style="color: var(--text-main);">{{ $v->director_name }}</strong></span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Mobile Actions -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" class="btn btn-primary" style="justify-content: center; font-size: 0.82rem; padding: 0.55rem 0.75rem;">
                    <i class="fa-solid fa-eye"></i> View Menu
                </a>
                <form action="{{ route('superadmin.vendors.toggle', $v->id) }}" method="POST" style="display: block; margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.82rem; padding: 0.55rem 0.75rem;">
                        {{ $v->is_active ? 'Suspend' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal Create Vendor -->
<div id="newVendorModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Create New Vendor Account</h3>
            <button onclick="document.getElementById('newVendorModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('superadmin.vendors.store') }}" method="POST">
            @csrf
            <div class="modal-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Business Name</label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Type</label>
                    <select name="type" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                        <option value="restaurant">Restaurant</option>
                        <option value="cafe">Cafe</option>
                        <option value="hotel">Hotel & Lounge</option>
                    </select>
                </div>
            </div>

            <div class="modal-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Owner Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Owner Name</label>
                    <input type="text" name="owner_name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
            </div>

            <div class="modal-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Default Theme</label>
                    <select name="menu_template_id" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                        @foreach($templates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Subscription Plan</label>
                    <select name="subscription_plan" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->slug }}" {{ $plan->slug === 'pro' ? 'selected' : '' }}>
                                {{ $plan->name }} ({{ number_format($plan->price) }} AMD)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Initial Owner Password</label>
                <input type="password" name="password" required value="password" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newVendorModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection

