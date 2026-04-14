        <section class="card" id="document-standards">
            <h2>Document Standards</h2>
            <div class="meta">API: /api/v1/config/document-categories + /documents/preview-name</div>
            <div id="documentCategoryList" class="list"></div>
            <div class="meta">Belge Kayıtlari (son 50)</div>
            <div id="documentRecordList" class="list"></div>
            <div class="row">
                <input id="docCategoryCode" placeholder="Code or: acceptance_letter">
                <input id="docCategoryName" placeholder="Ad (TR) or: Kabul Belgesi">
            </div>
            <div class="row">
                <select id="docTopCategoryCode">
                    <option value="kişisel_dokümanlar">Kişisel Dokümanlar</option>
                    <option value="uni_assist_dokümanları">Uni Assist Dokümanları</option>
                    <option value="vize_dokümanları">Vize Dokümanları</option>
                    <option value="dil_okulu_dokümanları">Dil okulu Dokümanları</option>
                    <option value="ikamet_kaydi_dokümanları">Ikamet Kaydi Dokümanları</option>
                    <option value="almanya_burokrasi_dokümanları">Almanya Bürokrasi Dokümanları</option>
                    <option value="diger_dokümanlar" selected>Diğer dokümanlar</option>
                    <option value="partner_dokümanları">Partner Dokümanları</option>
                </select>
            </div>
            <button onclick="createDocumentCategory()">Kategori Ekle</button>
            <div class="row" style="margin-top:8px;">
                <input id="docStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <input id="docCategoryCodeForPreview" placeholder="Kategori kodu" value="acceptance_letter">
            </div>
            <div class="row">
                <input id="docProcessTags" placeholder="Process tags (virgulle) or: application_prep,uni_assist">
                <input id="docOriginalName" placeholder="Original file or: kabul.pdf" value="kabul.pdf">
            </div>
            <div class="row">
                <button onclick="previewDocumentName()">İsim Önizle</button>
                <button onclick="createDocumentRecord()">Belge Oluştur</button>
            </div>
            <div class="row">
                <input id="docApproveId" placeholder="Onay için document ID">
                <button onclick="approveDocumentRecord()">Belge Onayla</button>
                <button onclick="rejectDocumentRecord()">Belge Reddet</button>
            </div>
            <div class="row">
                <input id="docReviewNote" placeholder="Inceleme notu (opsiyonel)">
            </div>
            <div id="documentStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Aday Öğrenci Gerekli Belgeleri</h2>
            <div class="meta">API: /api/v1/config/guest-required-documents</div>
            <div class="row">
                <select id="grdFilterApplicationType" onchange="loadGuestRequiredDocuments()">
                    <option value="">Tum tipler</option>
                    <option value="bachelor">bachelor</option>
                    <option value="master">master</option>
                    <option value="dil_kursu">dil_kursu</option>
                </select>
                <select id="grdFilterActive" onchange="loadGuestRequiredDocuments()">
                    <option value="all" selected>Tum durumlar</option>
                    <option value="active">Sadece aktif</option>
                    <option value="passive">Sadece pasif</option>
                </select>
            </div>
            <div id="guestRequiredDocumentList" class="list"></div>
            <details class="accordion-item">
                <summary>
                    <div class="accordion-title">Belge Kurali Duzenleyici</div>
                    <div class="accordion-meta">Ek birak: sadece filtrele/liste | Ac: ekle-güncelle-sil</div>
                </summary>
                <div class="accordion-body">
                    <div class="row">
                        <input id="grdId" placeholder="ID (duzenleme)" readonly>
                        <select id="grdApplicationType">
                            <option value="bachelor" selected>bachelor</option>
                            <option value="master">master</option>
                            <option value="dil_kursu">dil_kursu</option>
                        </select>
                    </div>
                    <div class="row">
                        <input id="grdDocumentCode" placeholder="document_code (orn: DOC-DIPL)">
                        <input id="grdCategoryCode" list="docCategorySuggestions" placeholder="category_code (orn: DOC-DIPL)">
                    </div>
                    <div class="row">
                        <input id="grdName" placeholder="Belge adi">
                        <input id="grdSortOrder" placeholder="sort_order" value="100">
                    </div>
                    <div class="row">
                        <input id="grdSuggestQuery" placeholder="Akilli oner (orn: pasaport ilk sayfa)">
                        <button onclick="suggestGuestRequiredDocument()">Belge Oner</button>
                    </div>
                    <div class="row">
                        <input id="grdAccepted" placeholder="accepted (orn: pdf,jpg,png)" value="pdf,jpg,png">
                        <input id="grdMaxMb" placeholder="max_mb" value="10">
                    </div>
                    <div class="row">
                        <select id="grdRequired">
                            <option value="1" selected>Zorunlu</option>
                            <option value="0">Opsiyonel</option>
                        </select>
                        <select id="grdActive">
                            <option value="1" selected>Durum: aktif</option>
                            <option value="0">Durum: pasif</option>
                        </select>
                    </div>
                    <div class="row">
                        <input id="grdDescription" placeholder="Aciklama (opsiyonel)">
                    </div>
                    <div class="row row-wrap">
                        <button onclick="createGuestRequiredDocument()">Belge Ekle</button>
                        <button onclick="updateGuestRequiredDocument()">Belge Güncelle</button>
                        <button onclick="deleteGuestRequiredDocument()">Belge Sil</button>
                        <button onclick="publishGuestRequiredDocuments()">Filtreyi Yayinla</button>
                        <button onclick="loadGuestRequiredDocuments()">Yenile</button>
                    </div>
                </div>
            </details>
            <div id="guestRequiredDocumentStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Aday Öğrenci Kayıt Alanları <span style="font-size:var(--tx-xs);color:#0f6bdc;border:1px solid #b9d3f6;border-radius:999px;padding:2px 6px;">accordion v3</span></h2>
            <div class="meta">API: /api/v1/config/guest-registration-fields</div>
            <div class="row">
                <input id="grfFilterSection" placeholder="section_key filtre (opsiyonel)">
                <select id="grfFilterActive" onchange="loadGuestRegistrationFields()">
                    <option value="">Tum durumlar</option>
                    <option value="1" selected>Sadece aktif</option>
                    <option value="0">Sadece pasif</option>
                </select>
            </div>
            <div class="meta">Liste akordiyon tipinde: satira tıklayip ac/kapat.</div>
            <div id="guestRegistrationFieldList" class="list"></div>
            <details class="accordion-item">
                <summary>
                    <div class="accordion-title">Form Alan Duzenleyici</div>
                    <div class="accordion-meta">Yalnizca duzenleme aninda acik tutun</div>
                </summary>
                <div class="accordion-body">
                    <div class="row">
                        <input id="grfId" placeholder="ID (duzenleme)" readonly>
                        <input id="grfSectionKey" placeholder="section_key (orn: language_skills)">
                    </div>
                    <div class="row">
                        <input id="grfSectionTitle" placeholder="section_title (orn: Dil Bilgisi)">
                        <input id="grfSectionOrder" placeholder="section_order" value="40">
                    </div>
                    <div class="row">
                        <input id="grfFieldKey" placeholder="field_key (orn: german_level)">
                        <input id="grfLabel" placeholder="label (orn: Almanca seviyeniz *)">
                    </div>
                    <div class="row">
                        <select id="grfType">
                            <option value="text" selected>text</option>
                            <option value="email">email</option>
                            <option value="date">date</option>
                            <option value="select">select</option>
                            <option value="textarea">textarea</option>
                        </select>
                        <select id="grfRequired">
                            <option value="1">Zorunlu</option>
                            <option value="0" selected>Opsiyonel</option>
                        </select>
                    </div>
                    <div class="row">
                        <input id="grfSortOrder" placeholder="sort_order" value="100">
                        <input id="grfMaxLength" placeholder="max_length" value="255">
                    </div>
                    <div class="row">
                        <input id="grfPlaceholder" placeholder="placeholder (opsiyonel)">
                    </div>
                    <div class="row">
                        <input id="grfHelpText" placeholder="help_text (opsiyonel)">
                    </div>
                    <div class="row">
                        <input id="grfOptionsJson" placeholder='select için options (JSON veya virgullu): [{"value":"a1","label":"A1"}]'>
                    </div>
                    <div class="row">
                        <select id="grfActive">
                            <option value="1" selected>Durum: aktif</option>
                            <option value="0">Durum: pasif</option>
                        </select>
                        <button onclick="loadGuestRegistrationFields()">Yenile</button>
                    </div>
                    <div class="row row-wrap">
                        <button onclick="createGuestRegistrationField()">Alan Ekle</button>
                        <button onclick="updateGuestRegistrationField()">Alan Güncelle</button>
                        <button onclick="deleteGuestRegistrationField()">Alan Sil</button>
                    </div>
                </div>
            </details>
            <div id="guestRegistrationFieldStatus" class="status"></div>
        </section>
