<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DocumentUploadToken;
use App\Models\GuestTicket;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;

/**
 * Manager taraflı ticket detail sayfası.
 *
 * D3: doc_request entegrasyonu — sayfa içinde "Belge Talep Et" butonu ile
 * polymorphic TARGET_TICKET üzerinden tek-kullanımlık yükleme linki oluşturulabilir.
 *
 * Liste için mevcut `/manager/ticket-analytics` recent tablosu kullanılır;
 * bu controller sadece detay + ek belge ekleme akışını sağlar.
 */
class ManagerTicketController extends Controller
{
    public function show(Request $request, GuestTicket $ticket)
    {
        // Mevcut doc_request token'larını çek (varsa)
        $docTokens = collect();
        try {
            $docTokens = DocumentUploadToken::query()
                ->where('target_type', DocumentUploadToken::TARGET_TICKET)
                ->where('target_id', (string) $ticket->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            // Modul kapali / DB issue — sessiz fallback
        }

        return view('manager.tickets.show', [
            'ticket'       => $ticket,
            'replies'      => $ticket->replies()->get(),
            'docTokens'    => $docTokens,
            'docModuleOn'  => ModuleAccess::enabled('doc_request'),
            'targetLabel'  => $this->buildTargetLabel($ticket),
        ]);
    }

    private function buildTargetLabel(GuestTicket $ticket): string
    {
        $subject = mb_substr((string) $ticket->subject, 0, 50);
        return 'Ticket #' . $ticket->id . ' — ' . $subject;
    }
}
