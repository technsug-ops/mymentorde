<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PromoPopup;
use Illuminate\Http\Request;

class PromoPopupController extends Controller
{
    public function index()
    {
        $popups = PromoPopup::orderByDesc('is_active')->orderBy('priority')->orderByDesc('updated_at')->get();
        return view('manager.promo-popups.index', compact('popups'));
    }

    public function create()
    {
        return view('manager.promo-popups.form', ['popup' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->user()?->email;
        PromoPopup::create($data);
        return redirect()->route('manager.promo-popups.index')->with('status', 'Popup oluşturuldu.');
    }

    public function edit(PromoPopup $popup)
    {
        return view('manager.promo-popups.form', compact('popup'));
    }

    public function update(Request $request, PromoPopup $popup)
    {
        $popup->update($this->validated($request));
        return redirect()->route('manager.promo-popups.index')->with('status', 'Popup güncellendi.');
    }

    public function destroy(PromoPopup $popup)
    {
        $popup->delete();
        return back()->with('status', 'Popup silindi.');
    }

    public function toggle(PromoPopup $popup)
    {
        $popup->update(['is_active' => !$popup->is_active]);
        return back()->with('status', $popup->is_active ? 'Popup aktif edildi.' : 'Popup pasif yapıldı.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:150'],
            'video_url'     => ['nullable', 'url', 'max:500'],
            'video_type'    => ['required', 'in:youtube,vimeo,custom'],
            'description'   => ['nullable', 'string', 'max:2000'],
            'target_pages'  => ['required', 'array', 'min:1'],
            'target_pages.*'=> ['string', 'in:' . implode(',', array_keys(PromoPopup::PAGE_OPTIONS))],
            'target_roles'  => ['required', 'array', 'min:1'],
            'target_roles.*'=> ['string', 'in:' . implode(',', array_keys(PromoPopup::ROLE_OPTIONS))],
            'delay_seconds' => ['required', 'integer', 'min:0', 'max:120'],
            'frequency'     => ['required', 'in:first_login,per_session,always'],
            'priority'      => ['required', 'integer', 'min:1', 'max:100'],
            'is_active'     => ['boolean'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
