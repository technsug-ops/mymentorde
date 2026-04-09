        <section class="card">
            <h2>Entity Catalog & Smart Suggestion</h2>
            <div class="meta">API: /api/v1/config/entity-catalog + /entity-catalog/suggest</div>
            <div id="entityCatalogList" class="list"></div>
            <div class="row">
                <select id="ecKind">
                    <option value="document" selected>document</option>
                    <option value="field">field</option>
                    <option value="id">id</option>
                    <option value="code">code</option>
                </select>
                <input id="ecQuery" placeholder="İçerik / ihtiyac (orn: pasaport ilk sayfa)">
            </div>
            <div class="row">
                <input id="ecForm" placeholder="form (field icin)" value="student_registration">
                <input id="ecEntity" placeholder="entity (id/code icin)" value="student">
            </div>
            <div class="row">
                <input id="ecSubType" placeholder="sub_type (orn: referrer)">
                <input id="ecYear" placeholder="year" value="">
                <input id="ecMonth" placeholder="month" value="">
                <input id="ecSequence" placeholder="sequence" value="">
            </div>
            <div class="row row-wrap">
                <button onclick="suggestEntityCatalog()">Oneri Uret</button>
                <button onclick="applyEntitySuggestionToForm()">Oneriyi Forma Uygula</button>
                <button onclick="loadEntityCatalog()">Katalog Yenile</button>
            </div>
            <div id="entityCatalogStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Process Outcomes</h2>
            <div class="meta">API: /api/v1/config/process-outcomes</div>
            <div id="processOutcomeList" class="list"></div>
            <div class="row">
                <input id="poStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <input id="poStep" placeholder="Adım (uni_assist/visa)" value="uni_assist">
            </div>
            <div class="row">
                <input id="poType" placeholder="Tür (acceptance/rejection)" value="acceptance">
                <input id="poUniversity" placeholder="Üniversite" value="TU Munich">
            </div>
            <div class="row">
                <input id="poProgram" placeholder="Program" value="Computer Science">
                <input id="poDeadline" placeholder="Son Tarih (YYYY-MM-DD)" value="">
            </div>
            <div class="row">
                <input id="poDetails" placeholder="Detay (TR)" value="Başvuru sonucu geldi.">
            </div>
            <div class="row">
                <button onclick="createProcessOutcome()">Sonuç Ekle</button>
                <button onclick="loadProcessOutcomes()">Yenile</button>
            </div>
            <div id="processOutcomeStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Internal Notes</h2>
            <div class="meta">API: /api/v1/config/internal-notes</div>
            <div id="internalNoteList" class="list"></div>
            <div class="row">
                <input id="inStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <input id="inCategory" placeholder="Kategori" value="general">
                <input id="inPriority" placeholder="Öncelik" value="normal">
            </div>
            <div class="row">
                <input id="inContent" placeholder="Gizli not icerigi">
            </div>
            <div class="row">
                <button onclick="createInternalNote()">Not Ekle</button>
                <button onclick="loadInternalNotes()">Yenile</button>
            </div>
            <div id="internalNoteStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Account Vault</h2>
            <div class="meta">API: /api/v1/config/account-vault</div>
            <div id="accountVaultList" class="list"></div>
            <div class="row">
                <input id="avStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <input id="avServiceName" placeholder="Servis kodu" value="uni_assist">
            </div>
            <div class="row">
                <input id="avServiceLabel" placeholder="Servis başlığı" value="Uni Assist">
                <input id="avEmail" placeholder="Hesap e-postası">
            </div>
            <div class="row">
                <input id="avPassword" placeholder="Şifre">
                <input id="avUrl" placeholder="Hesap URL'si">
            </div>
            <div class="row">
                <button onclick="createAccountVault()">Kasa Kaydet</button>
                <button onclick="loadAccountVault()">Yenile</button>
            </div>
            <div class="meta">API: /api/v1/config/account-vault-logs</div>
            <div id="accountVaultLogList" class="list"></div>
            <div class="row">
                <input id="vaultMaskedPassword" placeholder="Sifre maskeli görüntülenir" readonly>
                <button onclick="copyVaultPassword()">Kopyala</button>
            </div>
            <div id="accountVaultStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Field Rule Engine</h2>
            <div class="meta">API: /api/v1/config/field-rules</div>
            <div id="fieldRuleList" class="list"></div>
            <div class="row">
                <input id="frName" placeholder="Kural adi" value="Acik Lise Uyarısi">
                <input id="frForm" placeholder="target_form" value="student_registration">
            </div>
            <div class="row">
                <input id="frField" list="fieldSuggestions" placeholder="target_field" value="educationInfo.highSchool.type">
                <input id="frOperator" placeholder="operator" value="in">
                <input id="frValue" placeholder="value (in için virgullu)" value="acik_lise,uzaktan_lise">
            </div>
            <div class="row">
                <input id="frFieldSuggestQuery" placeholder="Field onerisi (orn: lise tipi)">
                <button onclick="suggestFieldRuleField()">Alan Öner</button>
            </div>
            <div class="row">
                <input id="frSeverity" placeholder="severity warning/block" value="block">
                <input id="frMessage" placeholder="Mesaj" value="Bu başvuru yetkili onayi gerektirir.">
            </div>
            <div class="row">
                <button onclick="createFieldRule()">Kural Ekle</button>
                <button onclick="loadFieldRules()">Kuralları Yenile</button>
            </div>
            <div class="meta">Değerlendir</div>
            <div class="row">
                <input id="frEvalStudentId" list="studentIdSuggestions" placeholder="student_id" value="BCS100001">
                <input id="frEvalStudentType" placeholder="student_type" value="bachelor">
            </div>
            <div class="row">
                <input id="frEvalFieldValue" placeholder="educationInfo.highSchool.type değeri" value="acik_lise">
            </div>
            <div class="row">
                <button onclick="evaluateFieldRules()">Değerlendir</button>
            </div>
            <div class="meta">Approvals API: /api/v1/config/field-rule-approvals</div>
            <div class="row">
                <select id="frApprovalStatus" style="width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 10px;font-size:var(--tx-sm);">
                    <option value="pending" selected>pending</option>
                    <option value="approved">approved</option>
                    <option value="rejected">rejected</option>
                    <option value="archived">archived</option>
                </select>
                <input id="frApprovalStudentId" list="studentIdSuggestions" placeholder="approval filter student_id (opsiyonel)" value="">
                <button onclick="loadFieldRuleApprovals()">Filtrele</button>
            </div>
            <div class="row">
                <button onclick="selectAllPendingApprovals()">Tumunu Sec</button>
                <button onclick="clearPendingApprovalSelection()">Secimi Temizle</button>
            </div>
            <div class="row">
                <button onclick="archivePendingApprovals()">Listeyi Arsivle</button>
                <button onclick="cleanupPendingApprovals()">Listeyi Temizle</button>
            </div>
            <div id="fieldRuleApprovalList" class="list"></div>
            <div id="fieldRuleStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Message Templates</h2>
            <div class="meta">API: /api/v1/config/message-templates</div>
            <div id="messageTemplateList" class="list"></div>
            <div class="row">
                <input id="mtName" placeholder="Template adi" value="Hos geldin mesaji">
                <input id="mtCategory" placeholder="category" value="welcome">
            </div>
            <div class="row">
                <input id="mtChannel" placeholder="channel (email/whatsapp/inApp)" value="email">
                <input id="mtSubject" placeholder="subject_tr" value="{{ config('brand.name', 'MentorDE') }}'ye hos geldiniz">
            </div>
            <div class="row">
                <input id="mtBody" placeholder="body_tr" value="Merhaba @{{student_name}}, {{ config('brand.name', 'MentorDE') }} ailesine hos geldiniz.">
            </div>
            <div class="row">
                <input id="mtVars" placeholder="variables (virgulle)" value="student_name,senior_name,package_name">
            </div>
            <div class="row">
                <select id="mtVarPick">
                    <option value="">Variable secin</option>
                    <option value="student_name">student_name</option>
                    <option value="senior_name">senior_name</option>
                    <option value="package_name">package_name</option>
                    <option value="student_id">student_id</option>
                    <option value="dealer_id">dealer_id</option>
                    <option value="branch">branch</option>
                </select>
                <button onclick="appendCsvToken('mtVarPick','mtVars')">Ekle</button>
            </div>
            <div class="row">
                <button onclick="createMessageTemplate()">Şablon Ekle</button>
                <button onclick="loadMessageTemplates()">Yenile</button>
            </div>
            <div id="messageTemplateStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Knowledge Base</h2>
            <div class="meta">API: /api/v1/config/knowledge-base</div>
            <div class="row">
                <input id="kbFilterCategory" placeholder="category filtresi (opsiyonel)">
                <select id="kbFilterPublished">
                    <option value="" selected>tum durumlar</option>
                    <option value="true">published</option>
                    <option value="false">draft</option>
                </select>
            </div>
            <div class="row">
                <button onclick="loadKnowledgeBase()">Filtrele</button>
                <button onclick="clearKnowledgeBaseFilters()">Temizle</button>
            </div>
            <div id="knowledgeBaseList" class="list"></div>
            <div class="row">
                <input id="kbTitleTr" placeholder="title_tr" value="Sperrkonto nasil acilir?">
                <input id="kbCategory" placeholder="category" value="faq">
            </div>
            <div class="row">
                <input id="kbSuggestQuery" placeholder="Kategori onerisi (orn: vize evraklari nasil yüklenir)">
                <button onclick="suggestKnowledgeCategory()">Kategori Öner</button>
            </div>
            <div class="row">
                <input id="kbTags" placeholder="tags (virgulle)" value="sperrkonto,bank,faq">
                <input id="kbRoles" placeholder="target_roles (virgulle)" value="senior,manager">
            </div>
            <div class="row">
                <select id="kbTagPick">
                    <option value="">Tag secin</option>
                </select>
                <button onclick="appendCsvToken('kbTagPick','kbTags')">Tag Ekle</button>
            </div>
            <div class="row">
                <select id="kbRolePick">
                    <option value="">Rol secin</option>
                    <option value="manager">manager</option>
                    <option value="senior">senior</option>
                    <option value="marketing_admin">marketing_admin</option>
                    <option value="dealer">dealer</option>
                    <option value="student">student</option>
                    <option value="guest">guest</option>
                </select>
                <button onclick="appendCsvToken('kbRolePick','kbRoles')">Rol Ekle</button>
            </div>
            <div class="row">
                <input id="kbBodyTr" placeholder="body_tr" value="Gerekli evraklari toplayip banka secimi ile ilerleyin.">
            </div>
            <div class="row">
                <select id="kbPublished">
                    <option value="true" selected>published</option>
                    <option value="false">draft</option>
                </select>
                <button onclick="createKnowledgeBase()">Yazi Ekle</button>
            </div>
            <div id="knowledgeBaseStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Notification Queue</h2>
            <div class="meta">API: /api/v1/config/notification-dispatches</div>
            <div class="row">
                <select id="ndFilterStatus">
                    <option value="" selected>Tum durumlar</option>
                    <option value="queued">queued</option>
                    <option value="sent">sent</option>
                    <option value="failed">failed</option>
                </select>
                <select id="ndFilterChannel">
                    <option value="" selected>Tum kanallar</option>
                    <option value="email">email</option>
                    <option value="whatsapp">whatsapp</option>
                    <option value="inApp">inApp</option>
                </select>
            </div>
            <div class="row">
                <input id="ndFilterStudentId" list="studentIdSuggestions" placeholder="student_id (opsiyonel)">
                <button onclick="loadNotificationDispatches()">Filtrele</button>
                <button onclick="clearNotificationDispatchFilters()">Temizle</button>
            </div>
            <div class="row">
                <button onclick="dispatchNotificationsNow()">Şimdi Gönder</button>
                <button onclick="retryFailedNotifications()">Başarısız → Kuyruğa Al</button>
            </div>
            <div id="notificationDispatchList" class="list"></div>
            <div id="notificationDispatchStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Batch Operations</h2>
            <div class="meta">API: /api/v1/config/batch-operations</div>
            <div id="batchOperationList" class="list"></div>
            <div class="row">
                <input id="boStudentIds" list="studentIdSuggestions" placeholder="student_ids (virgulle, opsiyonel)">
                <input id="boBranch" list="branchSuggestions" placeholder="branch (opsiyonel)">
            </div>
            <div class="row">
                <select id="boStudentIdsPick">
                    <option value="">Oneriden student secin</option>
                </select>
                <button onclick="appendPickedStudentId('boStudentIdsPick','boStudentIds')">Ekle</button>
            </div>
            <div class="row">
                <input id="boSeniorEmail" list="seniorEmailSuggestions" placeholder="senior_email (opsiyonel)">
                <input id="boDealerId" list="dealerIdSuggestions" placeholder="dealer_id (opsiyonel)">
            </div>
            <div class="row">
                <select id="boSeniorEmailPick">
                    <option value="">Oneriden senior secin</option>
                </select>
                <button onclick="fillInputFromPick('boSeniorEmailPick','boSeniorEmail')">Doldur</button>
            </div>
            <div class="row">
                <select id="boDealerIdPick">
                    <option value="">Oneriden dealer secin</option>
                </select>
                <button onclick="fillInputFromPick('boDealerIdPick','boDealerId')">Doldur</button>
            </div>
            <div class="row">
                <select id="boChannel">
                    <option value="email" selected>email</option>
                    <option value="whatsapp">whatsapp</option>
                    <option value="inApp">inApp</option>
                </select>
                <input id="boCategory" placeholder="category" value="batch_broadcast">
            </div>
            <div class="row">
                <input id="boSubject" placeholder="subject" value="{{ config('brand.name', 'MentorDE') }} Toplu Bilgilendirme">
            </div>
            <div class="row">
                <input id="boBody" placeholder="body" value="Merhaba, bu toplu bilgilendirme mesajidir.">
            </div>
            <div class="row row-wrap">
                <button onclick="runBatchBroadcast()">Toplu Bildirim Kuyruga Al</button>
                <button onclick="loadBatchOperations()">Yenile</button>
            </div>
            <div id="batchOperationStatus" class="status"></div>
        </section>
