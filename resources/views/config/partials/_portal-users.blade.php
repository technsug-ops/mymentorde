        <section class="card">
            <h2>Portal Users</h2>
            <div class="meta">API: /api/v1/config/portal-users</div>
            <div class="row">
                <select id="puFilterRole" onchange="loadPortalUsers()">
                    <option value="" selected>Tum roller</option>
                    <option value="student">student</option>
                    <option value="dealer">dealer</option>
                </select>
            </div>
            <div id="portalUserList" class="list"></div>
            <div class="row">
                <input id="puId" placeholder="User ID (duzenleme)" readonly>
                <input id="puName" placeholder="Ad Soyad">
            </div>
            <div class="row">
                <input id="puEmail" placeholder="Email">
                <select id="puRole" onchange="onPortalRoleChanged()">
                    <option value="student" selected>student</option>
                    <option value="dealer">dealer</option>
                </select>
            </div>
            <div class="row">
                <input id="puStudentId" list="studentIdSuggestions" placeholder="student_id">
                <input id="puDealerCode" list="dealerIdSuggestions" placeholder="dealer_code">
            </div>
            <div class="row">
                <input id="puPassword" placeholder="Sifre (bos birak = random)">
                <select id="puActive">
                    <option value="1" selected>Durum: aktif</option>
                    <option value="0">Durum: pasif</option>
                </select>
            </div>
            <div class="row row-wrap">
                <button onclick="createPortalUser()">Kullanıcı Ekle</button>
                <button onclick="updatePortalUser()">Kullanıcı Güncelle</button>
                <button onclick="resetPortalUserPassword()">Şifre Sıfırla</button>
                <button onclick="openPortalUserPreview()">Önizleme Aç</button>
                <button onclick="loadPortalUsers()">Yenile</button>
            </div>
            <div class="row">
                <input id="puGeneratedPassword" placeholder="Olusan sifre" readonly>
            </div>
            <div id="portalUserStatus" class="status"></div>
        </section>
