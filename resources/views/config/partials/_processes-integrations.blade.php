        <section class="card">
            <h2>Process Definitions</h2>
            <div class="meta">API: /api/v1/config/process-definitions</div>
            <div id="processList" class="list"></div>
            <div class="row row-wrap">
                <input id="processId" placeholder="External ID örn: PROC-007">
                <input id="processName" placeholder="Ad (TR)">
            </div>
            <div class="row row-wrap">
                <input id="processSuggestQuery" placeholder="Sürec tanimi (orn: belge kontrol adımi)">
                <button onclick="suggestProcessId()">ID Oner</button>
            </div>
            <button onclick="createProcess()">Ekle</button>
            <div id="processStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Firebase Storage</h2>
            <div class="meta">API: /api/v1/config/firebase-storage/status</div>
            <div id="firebaseStatus" class="list"></div>
            <div class="row">
                <input id="firebaseFolder" placeholder="Yükleme klasoru (opsiyonel)" value="mentorde-test">
            </div>
            <div class="row">
                <input id="firebaseFile" type="file">
            </div>
            <button onclick="testFirebaseUpload()">Yükleme Testi</button>
            <div id="firebaseUploadStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Firestore Database</h2>
            <div class="meta">API: /api/v1/config/firestore/status</div>
            <div id="firestoreStatus" class="list"></div>
            <div class="row">
                <input id="firestoreCollection" placeholder="Collection" value="health">
                <input id="firestoreDocument" placeholder="Document" value="ping">
            </div>
            <button onclick="testFirestoreWrite()">Yazma Testi</button>
            <div id="firestoreWriteStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>System Health</h2>
            <div class="meta">API: /api/v1/config/system-health</div>
            <div id="systemHealthList" class="list"></div>
            <div class="row row-wrap">
                <button onclick="loadSystemHealth()">Yenile</button>
                <input id="criticalCheckLimit" placeholder="kritik kontrol limiti" value="100">
                <button onclick="runCriticalCheckNow()">Kritik Kontrol Şimdi</button>
            </div>
            <div id="systemHealthStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Escalation Rules</h2>
            <div class="meta">API: /api/v1/config/escalation-rules</div>
            <div id="escalationRuleList" class="list"></div>
            <div class="row">
                <input id="escName" placeholder="Kural adi" value="Pending Approval 24s+">
                <select id="escEntityType">
                    <option value="field_rule_approval" selected>field_rule_approval</option>
                    <option value="process_outcome">process_outcome</option>
                </select>
            </div>
            <div class="row">
                <input id="escDurationHours" placeholder="duration_hours" value="24">
                <select id="escIsActive">
                    <option value="1" selected>Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
            <div class="row row-wrap">
                <button onclick="createEscalationRule()">Kural Ekle</button>
                <button onclick="runEscalationsNow()">Şimdi İşle</button>
                <button onclick="loadEscalationRules()">Yenile</button>
            </div>
            <div id="escalationRuleStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Integration Configs</h2>
            <div class="meta">API: /api/v1/config/integration-configs (+ /{category}/test)</div>
            <div id="integrationConfigList" class="list"></div>
            <div id="integrationStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>External Provider Connections</h2>
            <div class="meta">API: /api/v1/config/external-provider-connections</div>
            <div class="row">
                <select id="epProvider">
                    <option value="meta_ads">meta_ads</option>
                    <option value="ga4">ga4</option>
                    <option value="google_ads">google_ads</option>
                    <option value="calendly">calendly</option>
                    <option value="mailchimp">mailchimp</option>
                    <option value="clickup">clickup</option>
                </select>
                <select id="epStatus">
                    <option value="draft">draft</option>
                    <option value="connected">connected</option>
                    <option value="error">error</option>
                    <option value="paused">paused</option>
                </select>
            </div>
            <div class="row">
                <input id="epId" placeholder="ID (update icin)">
                <input id="epLabel" placeholder="Hesap etiketi">
            </div>
            <div class="row">
                <input id="epClientId" placeholder="OAuth client id">
                <input id="epScopes" placeholder="Scopes (virgulle)">
            </div>
            <div class="row row-wrap">
                <button onclick="createExternalProviderConnection()">Baglanti Ekle</button>
                <button onclick="updateExternalProviderConnection()">Baglanti Güncelle</button>
                <button onclick="loadExternalProviderConnections()">Yenile</button>
            </div>
            <div id="externalProviderConnectionList" class="list"></div>
            <div id="externalProviderConnectionStatus" class="status"></div>
            <div class="guide" style="margin-top:8px;">
                <h4>Kullanim Kilavuzu</h4>
                <ol class="list">
                    <li>Bu katman OAuth/canli baglanti oncesi konfig envanteri icindir.</li>
                    <li>Provider, account label, client id, scope bilgisini kaydedin.</li>
                    <li>MVP sonrasi canli token/sync bu kayitlar ustunden acilacak.</li>
                </ol>
            </div>
        </section>
