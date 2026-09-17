@extends('layouts.app')

@section('title', 'Team & Staff Management - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-users" style="color: var(--primary);"></i> Team & Roles
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Invite managers & staff with fine-grained per-location access permissions.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('newMemberModal').style.display='flex'">
        <i class="fa-solid fa-user-plus"></i> Invite Team Member
    </button>
</div>

<div class="card">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                <th style="padding: 0.75rem;">Name & Email</th>
                <th style="padding: 0.75rem;">Role</th>
                <th style="padding: 0.75rem;">Assigned Location</th>
                <th style="padding: 0.75rem;">Phone</th>
                <th style="padding: 0.75rem;">Joined</th>
            </tr>
        </thead>
        <tbody>
            @foreach($team as $member)
                <tr style="border-bottom: 1px solid var(--table-row-border);">
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 700; color: var(--text-main);">{{ $member->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $member->email }}</div>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="badge-role {{ $member->role == 'vendor_owner' ? 'badge-owner' : 'badge-manager' }}">
                            {{ str_replace('_', ' ', $member->role) }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-main);">
                        {{ $member->location?->name ?? 'All Locations' }}
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-main);">{{ $member->phone ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; color: var(--text-muted); font-size: 0.8rem;">
                        {{ $member->created_at->format('M d, Y') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Invite Member -->
<div id="newMemberModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Invite Team Member</h3>
            <button onclick="document.getElementById('newMemberModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.team.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Full Name</label>
                <input type="text" name="name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Role</label>
                    <select name="role" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                        <option value="manager">Location Manager</option>
                        <option value="staff">Kitchen / Waitstaff</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Assigned Location</label>
                    <select name="location_id" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                        <option value="">All Locations</option>
                        @foreach($locations as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Initial Password</label>
                <input type="password" name="password" required value="password" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newMemberModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Invite Member</button>
            </div>
        </form>
    </div>
</div>
@endsection
