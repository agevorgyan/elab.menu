<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrStudioController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $location = Location::find($activeLocationId);

        return view('admin.qr.studio', compact('vendor', 'location'));
    }
}
