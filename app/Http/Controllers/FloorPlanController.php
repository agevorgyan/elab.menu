<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\WaiterCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FloorPlanController extends Controller
{
    /**
     * Display the visual interactive restaurant halls and tables floor plan.
     */
    public function index(Request $request): View
    {
        $this->authorize('locations.view');

        $user = Auth::user();
        $vendor = $user->vendor;
        $activeLocationId = $user->location_id ?: session('active_location_id', $vendor->locations->first()?->id);
        $location = ($activeLocationId ? $vendor->locations->firstWhere('id', (int) $activeLocationId) : null) ?? $vendor->locations->first();

        $floorPlanData = $vendor->getFloorPlanData();
        $halls = $floorPlanData['halls'] ?? [];
        $tables = $floorPlanData['tables'] ?? [];

        // Fetch live active orders (pending, preparing, ready)
        $activeOrders = Order::where('vendor_id', $vendor->id)
            ->when($location, fn ($q) => $q->where('location_id', $location->id))
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with('items:id,order_id,quantity')
            ->latest()
            ->get();

        // Index orders by table number for rapid lookup
        $ordersByTable = [];
        foreach ($activeOrders as $order) {
            $cleanedTable = trim(strtolower((string) $order->table_number));
            // Also map "table 4" or "4"
            $digitsOnly = preg_replace('/[^0-9]/', '', $cleanedTable);
            if (! isset($ordersByTable[$cleanedTable])) {
                $ordersByTable[$cleanedTable] = $order;
            }
            if ($digitsOnly && ! isset($ordersByTable[$digitsOnly])) {
                $ordersByTable[$digitsOnly] = $order;
            }
        }

        // Fetch active waiter calls
        $waiterCalls = WaiterCall::where('vendor_id', $vendor->id)
            ->when($location, fn ($q) => $q->where('location_id', $location->id))
            ->where('status', 'pending')
            ->latest()
            ->get();

        $waiterCallsByTable = [];
        foreach ($waiterCalls as $call) {
            $cleanedTable = trim(strtolower((string) $call->table_number));
            $digitsOnly = preg_replace('/[^0-9]/', '', $cleanedTable);
            $waiterCallsByTable[$cleanedTable] = $call;
            if ($digitsOnly) {
                $waiterCallsByTable[$digitsOnly] = $call;
            }
        }

        // Enrich tables with real-time operational status
        $totalTables = count($tables);
        $occupiedCount = 0;
        $waiterCallCount = 0;

        foreach ($tables as &$table) {
            $numKey = trim(strtolower((string) ($table['number'] ?? '')));
            $digitsKey = preg_replace('/[^0-9]/', '', $numKey);

            $matchedOrder = $ordersByTable[$numKey] ?? ($digitsKey ? ($ordersByTable[$digitsKey] ?? null) : null);
            $matchedCall = $waiterCallsByTable[$numKey] ?? ($digitsKey ? ($waiterCallsByTable[$digitsKey] ?? null) : null);

            if ($matchedOrder) {
                $table['status'] = 'occupied';
                $occupiedCount++;
                $table['order'] = [
                    'id' => $matchedOrder->id,
                    'order_number' => $matchedOrder->order_number,
                    'status' => $matchedOrder->status,
                    'total_amount' => (float) $matchedOrder->total_amount,
                    'items_count' => $matchedOrder->items->sum('quantity'),
                    'elapsed_minutes' => $matchedOrder->created_at->diffInMinutes(),
                    'created_at_time' => $matchedOrder->created_at->format('H:i'),
                    'customer_name' => $matchedOrder->customer_name,
                    'payment_status' => $matchedOrder->payment_status,
                ];
            } else {
                $table['status'] = 'available';
                $table['order'] = null;
            }

            if ($matchedCall) {
                $table['has_waiter_call'] = true;
                $table['waiter_call'] = [
                    'id' => $matchedCall->id,
                    'type' => $matchedCall->type,
                    'type_label' => $matchedCall->type_label,
                    'created_at_time' => $matchedCall->created_at->format('H:i'),
                ];
                $waiterCallCount++;
            } else {
                $table['has_waiter_call'] = false;
                $table['waiter_call'] = null;
            }
        }
        unset($table);

        $availableCount = max(0, $totalTables - $occupiedCount);

        return view('admin.floor_plan.index', compact(
            'vendor',
            'location',
            'halls',
            'tables',
            'totalTables',
            'occupiedCount',
            'availableCount',
            'waiterCallCount'
        ));
    }

    /**
     * Save updated visual floor plan coordinates and halls layout.
     */
    public function saveFloorPlan(Request $request): JsonResponse
    {
        $this->authorize('locations.manage');

        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'halls' => 'nullable|array',
            'tables' => 'required|array',
            'tables.*.id' => 'required',
            'tables.*.x' => 'required|numeric',
            'tables.*.y' => 'required|numeric',
        ]);

        $rawHalls = $request->input('halls', []);
        $normalizedHalls = [];
        foreach ($rawHalls as $hall) {
            if (is_array($hall)) {
                $normalizedHalls[] = [
                    'id' => (string) ($hall['id'] ?? 'hall-'.count($normalizedHalls)),
                    'name' => (string) ($hall['name'] ?? 'Hall '.(count($normalizedHalls) + 1)),
                ];
            } elseif (is_string($hall)) {
                $normalizedHalls[] = [
                    'id' => Str::slug($hall),
                    'name' => $hall,
                ];
            }
        }

        $rawTables = $request->input('tables', []);
        $normalizedTables = [];
        foreach ($rawTables as $t) {
            $normalizedTables[] = [
                'id' => $t['id'] ?? (count($normalizedTables) + 1),
                'number' => (string) ($t['number'] ?? $t['id'] ?? count($normalizedTables) + 1),
                'hall_id' => (string) ($t['hall_id'] ?? $t['hall'] ?? 'main'),
                'name' => (string) ($t['name'] ?? 'Table '.($t['number'] ?? $t['id'] ?? '')),
                'capacity' => intval($t['capacity'] ?? 4),
                'shape' => in_array($t['shape'] ?? '', ['round', 'rectangle', 'square']) ? $t['shape'] : 'square',
                'x' => floatval($t['x'] ?? 0),
                'y' => floatval($t['y'] ?? 0),
            ];
        }

        $vendor->update([
            'floor_plan_data' => [
                'halls' => $normalizedHalls,
                'tables' => $normalizedTables,
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Table floor plan layout saved successfully.',
        ]);
    }
}
