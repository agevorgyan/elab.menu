<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Multi-tenant private channel for kitchen orders and waiter calls.
 * Only authenticated users belonging to the specific vendor can listen.
 */
Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    return (int) $user->vendor_id === (int) $vendorId;
});

/**
 * Public channel for real-time customer order tracking (by order number or tracking token).
 */
Broadcast::channel('order.{identifier}', function () {
    return true;
});
