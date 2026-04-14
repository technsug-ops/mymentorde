        <section class="card">
            <h2>Company Context</h2>
            <div class="meta">API: /api/v1/config/companies (+ /switch)</div>
            <div id="companyList" class="list"></div>
            <div class="row">
                <input id="companyId" placeholder="ID (duzenleme icin)">
                <input id="companyCode" placeholder="Kod (orn: mentorde)">
            </div>
            <div class="row">
                <input id="companyName" placeholder="Firma adi">
                <select id="companyActive">
                    <option value="1">Durum: aktif</option>
                    <option value="0">Durum: pasif</option>
                </select>
            </div>
            <div class="row">
                <button onclick="createCompany()">Firma Ekle</button>
                <button onclick="updateCompany()">Firma Güncelle</button>
                <button onclick="loadCompanies()">Yenile</button>
            </div>
            <div id="companyStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Öğrenci Tipleri</h2>
            <div class="meta">API: /api/v1/config/student-types</div>
            <div id="studentList" class="list"></div>
            <div class="row">
                <input id="studentName" placeholder="Ad (TR) örn: PhD">
                <input id="studentCode" placeholder="Code örn: phd">
                <input id="studentPrefix" placeholder="Prefix örn: PHD">
            </div>
            <button onclick="createStudentType()">Ekle</button>
            <div id="studentStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Role Catalog</h2>
            <div class="meta">API: /api/v1/config/role-catalog</div>
            <div id="roleCatalogList" class="list"></div>
            <div id="roleCatalogStatus" class="status"></div>
        </section>

        <section class="card">
            <h2>Hybrid RBAC</h2>
            <div class="meta">API: /api/v1/config/rbac/*</div>
            <div id="rbacTemplateList" class="list"></div>
            <div class="row">
                <input id="rbacTplCode" placeholder="template code (orn: tpl_finance_staff_custom)">
                <input id="rbacTplName" placeholder="template name">
            </div>
            <div class="row">
                <input id="rbacTplParentRole" placeholder="parent role (orn: finance_admin)">
                <select id="rbacTplActive">
                    <option value="1" selected>Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
            <div class="row row-wrap">
                <button onclick="createRbacTemplate()">Şablon Ekle</button>
                <button onclick="updateRbacTemplate()">Şablon Güncelle</button>
                <button onclick="loadRbacTemplates()">Şablonları Yenile</button>
            </div>
            <div class="row">
                <input id="rbacTplId" placeholder="template id (duzenleme/sync)" readonly>
                <input id="rbacTplPermCodes" placeholder="permission codes (virgulle)">
            </div>
            <div class="row">
                <button onclick="syncRbacTemplatePermissions()">İzin Senkronize</button>
                <button onclick="loadRbacPermissions()">İzinleri Yenile</button>
            </div>
            <div id="rbacPermissionList" class="list"></div>
            <div class="row">
                <input id="rbacPermCode" placeholder="permission code">
                <input id="rbacPermCategory" placeholder="category">
            </div>
            <div class="row">
                <input id="rbacPermDesc" placeholder="description">
                <button onclick="createRbacPermission()">İzin Ekle</button>
            </div>
            <div class="meta">Template Assignment</div>
            <div class="row">
                <input id="rbacAssignUserEmail" list="seniorEmailSuggestions" placeholder="user email">
                <input id="rbacAssignTemplateCode" placeholder="template code">
            </div>
            <div class="row row-wrap">
                <button onclick="assignRbacTemplate()">Şablon Ata</button>
                <button onclick="loadRbacAssignments()">Atamalar Yenile</button>
            </div>
            <div id="rbacAssignmentList" class="list"></div>
            <div id="rbacStatus" class="status"></div>
        </section>
