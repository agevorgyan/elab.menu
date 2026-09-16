@extends('layouts.app')

@section('title', 'Manage Vendors - SuperAdmin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">Vendor Directory</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage cafes, restaurants, and hotels on the platform.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('newVendorModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Create Vendor
    </button>
</div>

<div class="card">
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
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                        <img src="{{ $v->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&q=80' }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                        <div>
                            <div style="font-weight: 700;">{{ $v->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">/m/{{ $v->slug }}</div>
                        </div>
                    </td>
                    <td style="padding: 0.75rem;"><span style="text-transform: capitalize;">{{ $v->type }}</span></td>
                    <td style="padding: 0.75rem;">
                        <div style="font-size: 0.85rem; font-weight: 600;">{{ $v->legal_name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: #f59e0b;">ՀՎՀՀ: {{ $v->tax_id ?? 'N/A' }}</div>
                        @if($v->director_name)
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Տնօրեն: {{ $v->director_name }}</div>
                        @endif
                    </td>
                    <td style="padding: 0.75rem;">{{ $v->locations->count() }} Locations</td>
                    <td style="padding: 0.75rem;">{{ $v->menuTemplate?->name ?? 'Default' }}</td>
                    <td style="padding: 0.75rem;"><span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">{{ $v->subscription_plan }}</span></td>
                    <td style="padding: 0.75rem;">
                        @if($v->is_active)
                            <span style="color: #34d399; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Active</span>
                        @else
                            <span style="color: #f87171; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Suspended</span>
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

<!-- Modal Create Vendor -->
<div id="newVendorModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 550px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem;">Create New Vendor Account</h3>
            <button onclick="document.getElementById('newVendorModal').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('superadmin.vendors.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Business Name</label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Type</label>
                    <select name="type" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                        <option value="restaurant">Restaurant</option>
                        <option value="cafe">Cafe</option>
                        <option value="hotel">Hotel & Lounge</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Owner Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Owner Name</label>
                    <input type="text" name="owner_name" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Default Theme</label>
                    <select name="menu_template_id" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                        @foreach($templates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Subscription Plan</label>
                    <select name="subscription_plan" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                        <option value="basic">Basic (1 Location)</option>
                        <option value="pro" selected>Pro (Multi-Location)</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Initial Owner Password</label>
                <input type="password" name="password" required value="password" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newVendorModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection
