<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrStudioController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $this->authorize('viewSettings', $vendor);
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $location = ($activeLocationId ? $vendor->locations()->find($activeLocationId) : null) ?? $vendor->locations->first();

        return view('admin.qr.studio', compact('vendor', 'location'));
    }
}
