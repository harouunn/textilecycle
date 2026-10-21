<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Notifications\Dons\DonPourAssociation;
use App\Notifications\Dons\StatutDonModifie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications du module (cloche du menu) : seules celles des dons sont listées ici.
 */
class NotificationController extends Controller
{
    /** Types de notifications de ce module, aussi utilisés par la cloche (partials/front/modules/dons). */
    public const TYPES = [StatutDonModifie::class, DonPourAssociation::class];

    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()
            ->whereIn('type', self::TYPES)
            ->paginate(15);

        return view('dons.notifications.index', compact('notifications'));
    }

    /**
     * Ouvre une notification : elle est marquée comme lue, puis on suit son lien.
     */
    public function ouvrir(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereIn('type', self::TYPES)->findOrFail($id);
        $notification->markAsRead();

        return redirect()->to($notification->data['url'] ?? route('dons.mes-dons'));
    }

    public function toutLire(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->whereIn('type', self::TYPES)->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }
}
