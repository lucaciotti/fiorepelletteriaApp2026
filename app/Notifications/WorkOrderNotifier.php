<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Collection;

/**
 * Invia una notifica sia nel pannello Filament (campanella) sia come push di sistema.
 */
class WorkOrderNotifier
{
    /**
     * @param  Collection<int, User>  $users
     */
    public static function notify(Collection $users, string $title, string $body, ?string $actionUrl = null): void
    {
        foreach ($users as $user) {
            FilamentNotification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clock')
                ->warning()
                ->persistent()
                ->sendToDatabase($user);

            $user->notify(new WorkOrderWebPushNotification($title, $body, $actionUrl));
        }
    }

    public static function notifyAdmins(string $title, string $body, ?string $actionUrl = null): void
    {
        $admins = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))
            ->get();

        static::notify($admins, $title, $body, $actionUrl);
    }
}
