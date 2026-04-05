        <section class="card" id="guest-applications">
            <h2>Guest Applications</h2>
            <div class="meta">API: /api/v1/config/guest-applications</div>
            <div class="row">
                <select id="gaFilterConverted">
                    <option value="">Tum kayitlar</option>
                    <option value="false" selected>Donusmemis</option>
                    <option value="true">Donusenler</option>
                </select>
                <input id="gaFilterStatus" placeholder="lead_status (opsiyonel)">
            </div>
            <div class="row">
                <select id="gaFilterArchived">
                    <option value="false" selected>Sadece aktif</option>
                    <option value="true">Sadece arsiv</option>
                    <option value="">Tum kayitlar (arsiv dahil)</option>
                </select>
                <input id="gaArchiveDays" placeholder="Stale gun (default 180)" value="180">
            </div>
            <div class="row">
                <button onclick="loadGuestApplications()">Filtrele</button>
                <button onclick="clearGuestApplicationFilters()">Temizle</button>
                <button onclick="archiveStaleGuestApplications()">Eski Guest Arsivle</button>
            </div>
            <div id="guestApplicationList" class="list"></div>
            <div class="meta">Secilen aday için dönüşüm ayari (opsiyonel)</div>
            <div class="row">
                <input id="gaSelectedId" placeholder="Guest ID" readonly>
                <select id="gaSeniorEmail">
                    <option value="">Senior (oto secim)</option>
                </select>
            </div>
            <div class="row">
                <input id="gaBranch" list="branchSuggestions" placeholder="Şube (opsiyonel)" value="istanbul">
                <select id="gaDealerId">
                    <option value="">Dealer (opsiyonel)</option>
                </select>
            </div>
            <div class="row">
                <button onclick="convertSelectedGuestApplication()">Studenta Donustur</button>
                <button onclick="loadGuestApplications()">Yenile</button>
            </div>
            <div id="guestApplicationStatus" class="status"></div>
        </section>

        <section class="card" id="guest-ops">
            <h2>Guest Ops</h2>
            <div class="meta">API: /api/v1/config/guest-ops/*</div>
            <div class="row">
                <input id="goGuestId" placeholder="Guest ID (opsiyonel)">
                <select id="goTicketStatus">
                    <option value="">Tum ticket durumlari</option>
                    <option value="open">open</option>
                    <option value="in_progress">in_progress</option>
                    <option value="waiting_response">waiting_response</option>
                    <option value="closed">closed</option>
                </select>
            </div>
            <div class="row">
                <select id="goDocStatus">
                    <option value="">Tum belge durumlari</option>
                    <option value="uploaded">uploaded</option>
                    <option value="approved">approved</option>
                    <option value="rejected">rejected</option>
                </select>
                <input id="goDocCategoryCode" placeholder="Belge category code (opsiyonel)">
            </div>
            <div class="row row-wrap">
                <button onclick="loadGuestOps()">Guest Ops Yükle</button>
                <button onclick="clearGuestOpsFilters()">Filtre Temizle</button>
            </div>
            <div class="meta">Ticketlar</div>
            <div id="guestOpsTicketList" class="list"></div>
            <div class="meta">Belgeler</div>
            <div id="guestOpsDocumentList" class="list"></div>
            <div id="guestOpsStatus" class="status"></div>
            <div class="guide" style="margin-top:8px;">
                <h4>Kullanim Kilavuzu</h4>
                <ol class="list">
                    <li>Guest ID ile tek aday ticket/belge kayitlarini filtreleyin.</li>
                    <li>Ticket status'u buradan güncelleyip manager yaniti gönderebilirsiniz.</li>
                    <li>Belge reddinde not zorunludur; onay/reddet karari task akisini etkiler.</li>
                </ol>
            </div>
        </section>

        <section class="card" id="apply-form-settings">
            <h2>Apply Form / KVKK</h2>
            <div class="meta">API: /api/v1/config/apply-form-settings</div>
            <textarea id="applyKvkkText" rows="10" placeholder="KVKK aydinlatma metnini buraya yazin..."></textarea>
            <div class="row" style="margin-top:8px;">
                <button onclick="saveApplyFormSettings()">KVKK Kaydet</button>
                <button onclick="loadApplyFormSettings()">Yenile</button>
            </div>
            <div id="applyFormSettingsStatus" class="status"></div>
            <div class="guide" style="margin-top:8px;">
                <h4>Kullanim Kilavuzu</h4>
                <ol class="list">
                    <li>Bu metin /apply formundaki “KVKK Metnini Oku” popup icinde gosterilir.</li>
                    <li>Aktif firma degistiginde firma bazli KVKK metni yüklenir.</li>
                    <li>Kayıt sonrasi aday formunda hard refresh (Ctrl+F5) ile yeni metni kontrol edin.</li>
                </ol>
            </div>
        </section>

        <section class="card">
            <h2>Event Timeline</h2>
            <div class="meta">API: /api/v1/config/system-event-logs</div>
            <div class="row">
                <input id="evEventType" placeholder="event_type (opsiyonel)">
                <input id="evEntityType" placeholder="entity_type (opsiyonel)">
            </div>
            <div class="row">
                <input id="evEntityId" placeholder="entity_id (opsiyonel)">
                <button onclick="loadEventTimeline()">Yükle</button>
            </div>
            <div id="eventTimelineList" class="list"></div>
            <div id="eventTimelineStatus" class="status"></div>
            <div class="guide" style="margin-top:8px;">
                <h4>Kullanim Kilavuzu</h4>
                <ol class="list">
                    <li>Filtre bosken son 200 olay listelenir.</li>
                    <li>event_type / entity_type / entity_id ile olaylari daraltabilirsiniz.</li>
                    <li>Actor kolonundan islemi yapan kullaniciyi gorursunuz.</li>
                </ol>
            </div>
        </section>
