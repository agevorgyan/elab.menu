<?php

namespace App\Policies;

use App\Models\ContentTranslation;
use App\Models\User;
use App\Security\Permission;

class ContentTranslationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return ! empty($user->vendor_id) && (
            $user->hasPermission(Permission::TRANSLATIONS_VIEW)
            || $user->hasPermission(Permission::MENU_VIEW)
        );
    }

    public function view(User $user, ContentTranslation $translation): bool
    {
        return (int) $user->vendor_id === (int) $translation->vendor_id
            && (
                $user->hasPermission(Permission::TRANSLATIONS_VIEW)
                || $user->hasPermission(Permission::MENU_VIEW)
            );
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && (
            $user->hasPermission(Permission::TRANSLATIONS_MANAGE)
            || $user->hasPermission(Permission::MENU_UPDATE)
        );
    }

    public function update(User $user, ContentTranslation $translation): bool
    {
        return (int) $user->vendor_id === (int) $translation->vendor_id
            && (
                $user->hasPermission(Permission::TRANSLATIONS_MANAGE)
                || $user->hasPermission(Permission::MENU_UPDATE)
            );
    }

    public function approve(User $user, ContentTranslation $translation): bool
    {
        return (int) $user->vendor_id === (int) $translation->vendor_id
            && (
                $user->hasPermission(Permission::TRANSLATIONS_MANAGE)
                || $user->hasPermission(Permission::MENU_UPDATE)
            );
    }

    public function publish(User $user, ContentTranslation $translation): bool
    {
        return (int) $user->vendor_id === (int) $translation->vendor_id
            && (
                $user->hasPermission(Permission::TRANSLATIONS_PUBLISH)
                || $user->hasPermission(Permission::MENU_UPDATE)
            );
    }

    public function delete(User $user, ContentTranslation $translation): bool
    {
        return (int) $user->vendor_id === (int) $translation->vendor_id
            && (
                $user->hasPermission(Permission::TRANSLATIONS_MANAGE)
                || $user->hasPermission(Permission::MENU_DELETE)
            );
    }
}
