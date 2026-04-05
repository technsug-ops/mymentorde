        <section class="card">
            <h2>Revenue Milestones</h2>
            <div class="meta">API: /api/v1/config/revenue-milestones</div>
            <div id="revenueMilestoneList" class="list"></div>
            <div class="meta">Dealer API: /api/v1/config/dealer-revenue-milestones</div>
            <div id="dealerRevenueMilestoneList" class="list"></div>
            <div class="row">
                <input id="revStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <input id="revPackageTotal" placeholder="Paket Toplamı" value="5000">
            </div>
            <div class="row">
                <button onclick="initStudentRevenue()">Geliri Başlat</button>
                <button onclick="triggerRevenueManual()">Manuel Tetikle</button>
            </div>
            <div class="row">
                <input id="revMilestoneId" placeholder="Dönüm ID" value="REV-001">
                <button onclick="confirmRevenue()">Onayla</button>
                <button onclick="payRevenue()">Ödendi İşaretle</button>
            </div>
            <div id="studentRevenueProgress" class="list"></div>
            <div id="revenueStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Student Risk Scores</h2>
            <div class="meta">API: /api/v1/config/student-risk-scores</div>
            <div id="studentRiskList" class="list"></div>
            <div class="row">
                <input id="riskFilterStudentId" list="studentIdSuggestions" placeholder="student_id (opsiyonel)">
                <select id="riskFilterLevel">
                    <option value="" selected>tum seviyeler</option>
                    <option value="low">low</option>
                    <option value="medium">medium</option>
                    <option value="high">high</option>
                    <option value="critical">critical</option>
                </select>
            </div>
            <div class="row">
                <input id="riskCalcStudentId" list="studentIdSuggestions" placeholder="Hesapla: Öğrenci ID (boş ise toplu)">
                <input id="riskCalcLimit" placeholder="Toplu limit" value="200">
            </div>
            <div class="row row-wrap">
                <button onclick="loadStudentRiskScores()">Filtrele</button>
                <button onclick="calculateRiskScoresNow()">Hesapla Simdi</button>
                <button onclick="clearRiskFilters()">Temizle</button>
            </div>
            <div id="studentRiskStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Student Ownership</h2>
            <div class="meta">API: /api/v1/config/student-assignments</div>
            <div class="row">
                <input id="saFilterStudentId" list="studentIdSuggestions" placeholder="Filtre: Öğrenci ID">
                <select id="saFilterSeniorEmail">
                    <option value="">Tum seniorlar</option>
                </select>
            </div>
            <div class="row">
                <select id="saFilterBranch">
                    <option value="">Tum branchler</option>
                </select>
                <select id="saFilterArchived">
                    <option value="false" selected>Sadece aktif</option>
                    <option value="true">Sadece arsiv</option>
                    <option value="">Tum kayitlar</option>
                </select>
            </div>
            <div class="row">
                <select id="saFilterDealerId">
                    <option value="">Tum dealerlar</option>
                </select>
            </div>
            <div class="row">
                <button onclick="loadStudentAssignments()">Filtrele</button>
                <button onclick="clearStudentAssignmentFilters()">Filtre Temizle</button>
            </div>
            <div class="row">
                <input id="saQuickSearch" placeholder="Listede hızlı ara (student/senior/dealer/branch)" oninput="applyStudentQuickSearch()">
            </div>
            <div id="saListMeta" class="meta">Gorunen: 0 / Toplam: 0</div>
            <div id="studentAssignmentList" class="list"></div>
            <div class="row">
                <input id="saStudentId" list="studentIdSuggestions" placeholder="Öğrenci ID" value="BCS100001">
                <select id="saSeniorEmail">
                    <option value="">Danışman seçiniz</option>
                </select>
            </div>
            <div class="row">
                <select id="saStudentType">
                    <option value="">Öğrenci Tipi seçiniz</option>
                </select>
                <button onclick="generateStudentId()">ID Uret</button>
            </div>
            <div class="row">
                <input id="saBranch" list="branchSuggestions" placeholder="Şube" value="istanbul">
                <select id="saDealerId">
                    <option value="">Dealer seçiniz</option>
                </select>
            </div>
            <div class="row">
                <input id="saRisk" placeholder="Risk seviyesi" value="normal">
                <input id="saPayment" placeholder="Ödeme durumu" value="ok">
            </div>
            <div class="row">
                <button onclick="upsertStudentAssignment()">Atama Kaydet</button>
                <button onclick="loadStudentAssignments()">Yenile</button>
            </div>
            <div class="meta">Toplu Atama</div>
            <div class="row">
                <div id="saBulkChips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:34px;padding:6px 8px;border:1px solid #cbd9ea;border-radius:8px;background:#f9fbfd;width:100%;align-items:center;">
                    <span id="saBulkChipsEmpty" style="font-size:var(--tx-xs);color:#a0b4c8;line-height:20px;">Henuz student eklenmedi.</span>
                </div>
                <input type="hidden" id="saBulkStudentIds">
            </div>
            <div class="row">
                <select id="saBulkStudentIdsPick">
                    <option value="">Listeden student secin</option>
                </select>
                <input id="saBulkManualId" placeholder="Manuel Öğrenci ID (örn: BCS100001)" style="min-width:160px;">
                <button onclick="appendBulkStudentId()">Ekle</button>
                <button onclick="clearBulkStudentIds()" style="background:#f0f4f8;">Temizle</button>
            </div>
            <div class="row">
                <select id="saBulkSeniorEmail">
                    <option value="">Danışman seçiniz</option>
                </select>
                <input id="saBulkBranch" list="branchSuggestions" placeholder="Şube" value="istanbul">
            </div>
            <div class="row">
                <select id="saBulkDealerId">
                    <option value="">Dealer seçiniz (opsiyonel)</option>
                </select>
            </div>
            <div class="row">
                <button onclick="bulkAssignStudents()">Toplu Ata</button>
                <button onclick="autoAssignStudents()">Toplu Oto Ata</button>
            </div>
            <div id="studentAssignmentStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Senior Management</h2>
            <div class="meta">API: /api/v1/config/seniors</div>
            <div id="seniorList" class="list"></div>
            <div class="row">
                <input id="seniorId" list="seniorIdSuggestions" placeholder="Danışman ID (düzenleme için)" value="">
                <input id="seniorName" placeholder="Ad Soyad" value="Senior Demo">
                <input id="seniorEmail" placeholder="Email" value="senior@mentorde.local">
            </div>
            <div class="row">
                <input id="seniorPassword" placeholder="Sifre (bos birak = random)">
            </div>
            <div class="row">
                <select id="seniorRole">
                    <option value="senior" selected>Rol: senior</option>
                    <option value="mentor">Rol: mentor</option>
                </select>
            </div>
            <div class="row">
                <input id="seniorType" placeholder="Senior tipi (lisans/master)" value="lisans">
                <input id="seniorMaxCapacity" placeholder="Max kapasite" value="20">
            </div>
            <div class="row row-wrap">
                <select id="seniorAutoAssign">
                    <option value="1" selected>Oto atama: acik</option>
                    <option value="0">Oto atama: kapali</option>
                </select>
                <select id="seniorGuestPool">
                    <option value="0" selected>Guest havuzu: kapali</option>
                    <option value="1">Guest havuzu: acik</option>
                </select>
                <select id="seniorActive">
                    <option value="1" selected>Durum: aktif</option>
                    <option value="0">Durum: pasif</option>
                </select>
            </div>
            <div class="row row-wrap">
                <button onclick="createSenior()">Senior Ekle</button>
                <button onclick="updateSenior()">Senior Güncelle</button>
                <button onclick="saveSeniorSettings()">Ayarlari Kaydet</button>
                <button onclick="loadSeniors()">Yenile</button>
            </div>
            <div class="meta">Devretme: Listeden "Devret" tıkla, hedef senior/mentoru sec, onayla.</div>
            <div class="row">
                <input id="seniorTransferSource" placeholder="Kaynak senior" readonly>
                <select id="seniorTransferTarget">
                    <option value="">Hedef senior seçiniz</option>
                </select>
            </div>
            <div class="row">
                <button onclick="confirmSeniorTransfer()">Devri Onayla</button>
                <button onclick="clearSeniorTransfer()">Devri Temizle</button>
            </div>
            <div class="row">
                <input id="seniorGeneratedPassword" placeholder="Olusan sifre" readonly>
            </div>
            <div id="seniorStatus" class="status"></div>
        </section>
