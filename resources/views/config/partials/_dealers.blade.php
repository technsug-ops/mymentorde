        <section class="card">
            <h2>Dealer Types</h2>
            <div class="meta">API: /api/v1/config/dealer-types</div>
            <div id="dealerList" class="list"></div>
            <div class="row">
                <input id="dealerName" placeholder="Ad (TR)">
                <input id="dealerCode" placeholder="Code">
            </div>
            <button onclick="createDealerType()">Ekle</button>
            <div id="dealerStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Lead Source Options</h2>
            <div class="meta">API: /api/v1/config/lead-source-options</div>
            <div id="leadSourceOptionList" class="list"></div>
            <div class="row">
                <input id="lsOptId" placeholder="ID (duzenleme)" readonly>
                <input id="lsOptCode" placeholder="code (orn: tiktok)">
            </div>
            <div class="row">
                <input id="lsOptLabel" placeholder="label (orn: TikTok)">
                <input id="lsOptSort" placeholder="sort_order" value="100">
            </div>
            <div class="row">
                <select id="lsOptActive">
                    <option value="1" selected>Durum: aktif</option>
                    <option value="0">Durum: pasif</option>
                </select>
            </div>
            <div class="row">
                <button onclick="createLeadSourceOption()">Ekle</button>
                <button onclick="updateLeadSourceOption()">Güncelle</button>
                <button onclick="loadLeadSourceOptions()">Yenile</button>
            </div>
            <div id="leadSourceOptionStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Dealers</h2>
            <div class="meta">API: /api/v1/config/dealers</div>
            <div class="row">
                <select id="dealerListStatusFilter" onchange="loadDealers()">
                    <option value="all" selected>Tum durumlar</option>
                    <option value="active">Sadece aktif</option>
                    <option value="passive">Sadece pasif</option>
                    <option value="archived">Sadece arsivli</option>
                </select>
            </div>
            <div id="dealerMasterList" class="list"></div>
            <div class="row">
                <input id="dealerEntryId" placeholder="Dealer ID (duzenleme)" readonly>
                <input id="dealerEntryName" placeholder="Dealer Name">
                <select id="dealerEntryTypeCode">
                    <option value="">Dealer Type seçiniz</option>
                </select>
            </div>
            <div class="row">
                <select id="dealerEntryActive">
                    <option value="1" selected>Durum: aktif</option>
                    <option value="0">Durum: pasif</option>
                </select>
            </div>
            <div class="row">
                <button onclick="createDealer()">Dealer Ekle</button>
                <button onclick="updateDealer()">Dealer Güncelle</button>
                <button onclick="loadDealers()">Yenile</button>
            </div>
            <div class="meta">Dealer Type Geçmişi</div>
            <div class="row">
                <input id="dealerHistoryCode" list="dealerIdSuggestions" placeholder="Dealer Code (opsiyonel)">
                <button onclick="loadDealerTypeHistory()">Geçmiş Yükle</button>
            </div>
            <div id="dealerTypeHistoryList" class="list"></div>
            <div id="dealerMasterStatus" class="status"></div>
        </section>
