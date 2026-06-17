/* ================================================
   REGISTERS — Entreprises
   ================================================ */

const state = {
    results: [],
    page: 1,
    total: 0,
    loading: false,
    currentPayload: null,
    lastFilters: {},
};

const PER_PAGE = 15;

/* ---- Helpers DOM ---- */
function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

function esc(v) {
    if (v === null || v === undefined) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmt(v) {
    if (v === null || v === undefined || v === '') return null;
    if (typeof v === 'boolean') return v ? 'Oui' : 'Non';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}

function fmtSiren(s) {
    if (!s) return s;
    const str = String(s).replace(/\s/g, '');
    if (str.length === 9)  return `${str.slice(0,3)} ${str.slice(3,6)} ${str.slice(6)}`;
    if (str.length === 14) return `${str.slice(0,3)} ${str.slice(3,6)} ${str.slice(6,9)} ${str.slice(9)}`;
    return str;
}

function fmtMontant(v) {
    if (!v) return null;
    const n = Number(v);
    if (isNaN(n)) return String(v);
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}

const FIELD_LABELS = {
    siren: 'SIREN', siret: 'SIRET', denomination: 'Dénomination',
    nom_entreprise: 'Nom', nom_commercial: 'Nom commercial', sigle: 'Sigle',
    forme_juridique: 'Forme juridique', code_naf: 'Code NAF',
    libelle_naf: 'Activité (NAF)', categorie_juridique: 'Catégorie juridique',
    statut_rcs: 'Statut RCS', etat_administratif: 'État',
    date_creation: 'Date de création', date_cessation: 'Date de cessation',
    tranche_effectif: 'Tranche effectif', annee_effectif: 'Année effectif',
    adresse_ligne_1: 'Adresse', code_postal: 'Code postal', ville: 'Ville',
    pays: 'Pays', chiffre_affaires: "Chiffre d'affaires", resultat_net: 'Résultat net',
    annee: 'Année', nom: 'Nom', prenom: 'Prénom', qualite: 'Qualité',
    date_naissance: 'Date de naissance', nationalite: 'Nationalité',
    date_publication: 'Date publication', type_publication: 'Type', greffe: 'Greffe',
    description: 'Description', numero_immatriculation: 'N° immatriculation',
};

const SECTION_LABELS = {
    establishments: 'Établissements', representatives: 'Représentants',
    finances: 'Finances', bodacc: 'Annonces BODACC',
    etablissements: 'Établissements', representants: 'Représentants',
    dirigeants: 'Dirigeants', annonces: 'Annonces BODACC',
    beneficiaires_effectifs: 'Bénéficiaires effectifs', procedures: 'Procédures',
};

const HIDDEN = new Set(['raw_json', 'raw', 'source', 'siege_json']);

const PRIMARY_KEYS_CO = [
    'siren', 'denomination', 'nom_entreprise', 'forme_juridique',
    'code_naf', 'libelle_naf', 'statut_rcs', 'etat_administratif',
    'date_creation', 'tranche_effectif', 'categorie_juridique',
];

/* ---- Network ---- */
async function getJson(path, params = {}) {
    const url = new URL(path, window.location.href);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
    });
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `Erreur HTTP ${res.status}`);
    return data;
}

/* ---- Collect filters ---- */
function collectFilters() {
    const f = {};
    const q = qs('#f-q').value.trim();
    if (q) f.q = q;

    const dept = qs('#f-dept').value.trim();
    if (dept) {
        if (dept.length <= 3) f.departement = dept;
        else f.code_postal = dept;
    }

    const naf = qs('#f-naf').value.trim();
    if (naf) f.code_naf = naf;

    const etat = qs('input[name="etat"]:checked')?.value;
    if (etat) f.etat_administratif = etat;

    const effectif = qs('#f-effectif').value;
    if (effectif) f.tranche_effectif_salarie = effectif;

    const catjur = qs('#f-catjur').value;
    if (catjur) f.categorie_juridique = catjur;

    return f;
}

/* ---- Render result list ---- */
function renderResults(items, append = false) {
    const container = qs('#results');
    if (!append) {
        state.results = items;
        if (!items.length) {
            qs('#result-meta').innerHTML = '<span>Aucun résultat</span>';
            container.innerHTML = '<div class="empty-msg">Aucun résultat pour ces filtres.</div>';
            qs('#load-more-wrap').classList.add('hidden');
            return;
        }
        container.innerHTML = '';
    } else {
        state.results = state.results.concat(items);
    }

    const total = state.total;
    qs('#result-meta').innerHTML =
        `<span>${total.toLocaleString('fr-FR')} entreprise${total > 1 ? 's' : ''}</span>`;

    items.forEach((co, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rc-lg';
        btn.dataset.siren = co.siren || '';

        const nom = co.nom_entreprise || co.denomination || co.siren || '—';
        const siren = co.siren ? fmtSiren(co.siren) : '';
        const naf = [co.code_naf, co.libelle_naf].filter(Boolean).join(' · ');
        const ville = co.siege?.ville || co.ville || '';
        const sub = [naf, ville].filter(Boolean).join(' — ');
        const statut = co.statut_rcs || co.etat_administratif || '';
        const isActif = ['A', 'actif', 'Actif'].includes(statut);
        const badgeCls = isActif ? 'status-actif' : statut ? 'status-ferme' : 'status-unknown';
        const badgeTxt = isActif ? 'Actif' : statut === 'C' ? 'Cessé' : (statut || '?');

        btn.innerHTML = `
            <div class="rc-lg-top">
                <span class="badge type-company">Entreprise</span>
                <span class="badge ${badgeCls}">${esc(badgeTxt)}</span>
            </div>
            <div class="rc-lg-name">${esc(nom)}</div>
            <div class="rc-lg-sub">${siren ? `SIREN ${esc(siren)}` : ''}${sub ? (siren ? ' · ' : '') + esc(sub) : ''}</div>
        `;

        btn.addEventListener('click', () => {
            qsa('.rc-lg').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadDetail(co.siren);
        });

        container.appendChild(btn);
    });

    /* Load more */
    const shown = state.results.length;
    if (shown < total) {
        qs('#load-more-wrap').classList.remove('hidden');
        qs('#load-more-btn').disabled = false;
        qs('#load-more-btn').textContent = `Charger plus (${shown}/${total})`;
    } else {
        qs('#load-more-wrap').classList.add('hidden');
    }
}

/* ---- Search ---- */
async function runSearch(append = false) {
    if (state.loading) return;
    state.loading = true;

    if (!append) {
        state.page = 1;
        state.results = [];
        state.lastFilters = collectFilters();
        qs('#results').innerHTML = `
            <div class="empty-msg" style="display:flex;align-items:center;gap:10px">
                <div class="spinner" style="width:16px;height:16px;border-width:2.5px"></div>
                Recherche en cours…
            </div>`;
        qs('#result-meta').innerHTML = '<span>Recherche…</span>';
        qs('#detail').innerHTML = `<div class="empty-centered"><p>Sélectionnez un résultat.</p></div>`;
        qs('#load-more-wrap').classList.add('hidden');
    } else {
        state.page++;
        qs('#load-more-btn').disabled = true;
        qs('#load-more-btn').textContent = 'Chargement…';
    }

    try {
        const params = { ...state.lastFilters, page: state.page, par_page: PER_PAGE };
        const data = await getJson('api/live_entreprises.php', params);
        state.total = data.total || 0;
        renderResults(data.resultats || [], append);
    } catch (err) {
        if (!append) {
            qs('#results').innerHTML = `<div class="empty-msg" style="color:var(--s-ferme)">${esc(err.message)}</div>`;
        }
    } finally {
        state.loading = false;
    }
}

/* ---- Detail ---- */
async function loadDetail(siren) {
    qs('#detail').innerHTML = `
        <div class="empty-centered">
            <div class="spinner"></div>
            <p>Chargement…</p>
        </div>`;
    try {
        const res = await getJson('api/live_detail.php', { type: 'company', id: siren });
        state.currentPayload = res.data;
        renderDetail(res.data);
    } catch (err) {
        qs('#detail').innerHTML = `
            <div class="empty-centered">
                <p style="color:var(--s-ferme)">${esc(err.message)}</p>
            </div>`;
    }
}

function renderDetail(payload) {
    const co = payload.company || payload;
    const nom = co.nom_entreprise || co.denomination || co.siren || '—';
    const siren = co.siren ? fmtSiren(co.siren) : '';
    const statut = co.statut_rcs || co.etat_administratif || '';
    const isActif = ['A', 'actif', 'Actif'].includes(statut);
    const badgeCls = isActif ? 'status-actif' : statut ? 'status-ferme' : 'status-unknown';
    const badgeTxt = isActif ? 'Actif' : statut === 'C' ? 'Cessé' : (statut || '?');

    const kvKeys = PRIMARY_KEYS_CO;
    const kvHtml = kvKeys
        .filter(k => k in co && !HIDDEN.has(k))
        .map(k => {
            const v = fmt(co[k]);
            const label = FIELD_LABELS[k] || k;
            return `
                <div class="kv-card">
                    <div class="kv-label">${esc(label)}</div>
                    <div class="kv-value${v === null ? ' is-null' : ''}">
                        ${k === 'siren' || k === 'siret' ? esc(fmtSiren(v || '')) : v === null ? '—' : esc(v)}
                    </div>
                </div>`;
        }).join('');

    const sections = Object.entries(payload)
        .filter(([k, v]) => k !== 'type' && Array.isArray(v) && v.length > 0)
        .map(([k, rows]) => renderSection(k, rows))
        .join('');

    qs('#detail').innerHTML = `
        <div class="entity-head">
            <div class="entity-meta">
                <div class="entity-badges">
                    <span class="badge type-company">Entreprise</span>
                    ${statut ? `<span class="badge ${badgeCls}">${esc(badgeTxt)}</span>` : ''}
                </div>
                <h1 class="entity-name">${esc(nom)}</h1>
                ${siren ? `<div class="entity-id">SIREN ${esc(siren)}</div>` : ''}
            </div>
            <div class="entity-actions">
                <button type="button" class="btn-sm primary" id="btn-show-json">JSON</button>
            </div>
        </div>
        ${kvHtml ? `<div class="kv-grid">${kvHtml}</div>` : ''}
        ${sections}
    `;

    qs('#btn-show-json').addEventListener('click', () => showJson(payload, `company_${co.siren}`));
}

function renderSection(name, rows) {
    const title = SECTION_LABELS[name] || name.replace(/_/g, ' ');
    const cols = [...new Set(rows.flatMap(r => Object.keys(r)))]
        .filter(c => !HIDDEN.has(c)).slice(0, 8);

    return `
        <div class="entity-section">
            <div class="section-row">
                <span class="section-title">${esc(title)}</span>
                <span class="count-badge">${rows.length}</span>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr>${cols.map(c => `<th>${esc(FIELD_LABELS[c] || c)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${rows.slice(0, 20).map(row => `
                            <tr>${cols.map(c => {
                                const v = fmt(row[c]);
                                return `<td>${v !== null ? esc(v) : '<span style="color:var(--muted)">—</span>'}</td>`;
                            }).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            ${rows.length > 20 ? `<p style="font-size:12px;color:var(--muted);margin-top:6px">… et ${rows.length - 20} ligne(s) supplémentaire(s) dans le JSON.</p>` : ''}
        </div>`;
}

/* ---- JSON overlay ---- */
function showJson(payload, name) {
    const json = JSON.stringify(payload, null, 2);
    qs('#json-output').textContent = json;
    const blob = new Blob([json], { type: 'application/json' });
    const dl = qs('#download-json');
    dl.href = URL.createObjectURL(blob);
    dl.download = `registers_${name}_${Date.now()}.json`;
    qs('#overlay-json').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

/* ---- Event wiring ---- */
qs('#filter-form').addEventListener('submit', e => {
    e.preventDefault();
    runSearch(false);
});

qs('#load-more-btn').addEventListener('click', () => runSearch(true));

qs('#btn-reset').addEventListener('click', () => {
    qs('#filter-form').reset();
    qs('input[name="etat"][value="A"]').checked = true;
    state.results = [];
    state.total = 0;
    qs('#results').innerHTML = '<div class="empty-msg">Lancez une recherche.</div>';
    qs('#result-meta').innerHTML = '<span>Résultats</span>';
    qs('#detail').innerHTML = `<div class="empty-centered"><p>Sélectionnez une entreprise pour afficher la fiche.</p></div>`;
    qs('#load-more-wrap').classList.add('hidden');
});

qs('#btn-sources').addEventListener('click', () => {
    qs('#overlay-sources').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
});

qsa('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
        qs('#' + btn.dataset.close).classList.add('hidden');
        document.body.style.overflow = '';
    });
});

qsa('.overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
});

qs('#copy-json').addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(qs('#json-output').textContent);
        const btn = qs('#copy-json');
        const orig = btn.textContent;
        btn.textContent = 'Copié ✓';
        setTimeout(() => { btn.textContent = orig; }, 1600);
    } catch (_) {}
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        qsa('.overlay').forEach(o => {
            if (!o.classList.contains('hidden')) {
                o.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    }
    if ((e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k'))
        && document.activeElement !== qs('#f-q')) {
        e.preventDefault();
        qs('#f-q').focus();
    }
});

/* Lancer une recherche si paramètre URL présent */
(function () {
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q) {
        qs('#f-q').value = q;
        runSearch(false);
    }
})();
