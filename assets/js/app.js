/* ================================================
   REGISTERS — Application v2
   ================================================ */

const state = {
    results: [],
    activeFilter: null,
    currentPayload: null,
};

/* ---- Metadata ---- */

const TYPE_LABEL = {
    company:       'Entreprise',
    establishment: 'Établissement',
    person:        'Personne',
    address:       'Adresse',
    parcel:        'Parcelle',
    copro:         'Copropriété',
    dpe:           'DPE',
};

const TYPE_CSS = {
    company:       'type-company',
    establishment: 'type-establishment',
    person:        'type-person',
    address:       'type-address',
    parcel:        'type-parcel',
    copro:         'type-copro',
    dpe:           'type-dpe',
};

const TYPE_COLOR = {
    company:       '#1d53d0',
    establishment: '#1d53d0',
    person:        '#6d28d9',
    address:       '#b45309',
    parcel:        '#0e7a52',
    copro:         '#0369a1',
    dpe:           '#c2410c',
};

/* ---- Traductions françaises ---- */

const SECTION_LABELS = {
    // Entreprise
    establishments:          'Établissements',
    representatives:         'Représentants',
    finances:                'Finances',
    bodacc:                  'Annonces BODACC',
    // Immobilier
    nearby_parcels:          'Parcelles proches',
    copros:                  'Copropriétés',
    dpe:                     'DPE',
    sales:                   'Ventes DVF',
    // Noms français (si jamais l'API évolue)
    etablissements:          'Établissements',
    representants:           'Représentants',
    dirigeants:              'Dirigeants',
    beneficiaires_effectifs: 'Bénéficiaires effectifs',
    ventes:                  'Ventes DVF',
    mutations:               'Mutations',
    parcelles:               'Parcelles',
    parcelles_proches:       'Parcelles proches',
    coproprietes:            'Copropriétés',
    annonces:                'Annonces BODACC',
    procedures:              'Procédures collectives',
    sanctions:               'Sanctions',
    observations:            'Observations',
    actes:                   'Actes',
    locaux:                  'Locaux',
    documents:               'Documents',
};

const FIELD_LABELS = {
    // Identifiants
    siren:                    'SIREN',
    siret:                    'SIRET',
    siren_formate:            'SIREN formaté',
    numero_immatriculation:   'N° immatriculation',
    identifiant_dpe:          'Identifiant DPE',
    numero_dpe:               'N° DPE',
    numero:                   'Numéro',
    numero_parution:          'N° parution',
    numero_annonce:           'N° annonce',
    numero_plan:              'N° de plan',
    parcelle_numero:          'Parcelle',
    parcelle_cadastrale:      'Référence cadastrale',
    // Entreprise
    nom_entreprise:           'Nom',
    denomination:             'Dénomination',
    sigle:                    'Sigle',
    nom_commercial:           'Nom commercial',
    enseigne:                 'Enseigne',
    forme_juridique:          'Forme juridique',
    code_naf:                 'Code NAF',
    libelle_naf:              'Activité (NAF)',
    categorie_juridique:      'Catégorie juridique',
    statut_rcs:               'Statut RCS',
    statut_consolide:         'Statut',
    etat_administratif:       'État',
    entreprise_cessee:        'Cessée',
    etablissement_cesse:      'Établissement cessé',
    date_creation:            'Date de création',
    date_de_creation:         'Date de création',
    date_debut_activite:      'Début d\'activité',
    date_cessation:           'Date de cessation',
    date_fermeture:           'Date de fermeture',
    tranche_effectif:         'Tranche effectif',
    annee_effectif:           'Année effectif',
    // Finances
    annee:                    'Année',
    chiffre_affaires:         'Chiffre d\'affaires',
    resultat:                 'Résultat net',
    resultat_net:             'Résultat net',
    // Adresse / lieu
    adresse:                  'Adresse',
    adresse_ligne_1:          'Adresse',
    label:                    'Adresse complète',
    rue:                      'Rue',
    numero_voie:              'N° voie',
    code_postal:              'Code postal',
    ville:                    'Ville',
    commune:                  'Commune',
    code_commune:             'Code commune',
    pays:                     'Pays',
    latitude:                 'Latitude',
    longitude:                'Longitude',
    siege:                    'Siège social',
    score:                    'Score geocodage',
    // BODACC
    date_publication:         'Date de publication',
    type_publication:         'Type',
    greffe:                   'Greffe (tribunal)',
    description:              'Description',
    bodacc:                   'Publication',
    url:                      'Lien',
    // Parcelle / cadastre
    section:                  'Section',
    prefixe:                  'Préfixe',
    contenance:               'Contenance (m²)',
    // Copropriété
    nom:                      'Nom',
    nombre_total_lots:        'Nb. lots total',
    type_syndic:              'Type de syndic',
    periode_construction:     'Période de construction',
    // DPE
    classe_bilan_dpe:         'Classe DPE',
    classe_emission_ges:      'Classe GES',
    type_batiment_dpe:        'Type de bâtiment',
    annee_construction:       'Année de construction',
    surface_habitable:        'Surface habitable (m²)',
    date_reception_dpe:       'Date du DPE',
    // Personne
    prenom:                   'Prénom',
    qualite:                  'Qualité',
    date_naissance:           'Date de naissance',
    nationalite:              'Nationalité',
};

/* Champs à masquer dans les tableaux et grilles KV */
const HIDDEN_FIELDS = new Set(['raw_json', 'siege_json', 'raw', 'source']);

const PRIMARY_KEYS = {
    company: [
        'siren', 'denomination', 'nom_entreprise', 'forme_juridique',
        'code_naf', 'libelle_naf', 'statut_rcs', 'etat_administratif',
        'date_creation', 'tranche_effectif', 'categorie_juridique',
    ],
    establishment: [
        'siret', 'siren', 'nom_commercial', 'enseigne',
        'adresse_ligne_1', 'code_postal', 'ville', 'siege',
        'etat_administratif', 'date_creation',
    ],
    person: [
        'nom', 'prenom', 'qualite', 'date_naissance', 'nationalite',
    ],
    parcel: [
        'numero', 'prefixe', 'section', 'adresse', 'commune',
        'code_commune', 'contenance', 'latitude', 'longitude',
    ],
    copro: [
        'numero_immatriculation', 'nom', 'adresse',
        'nombre_total_lots', 'type_syndic', 'parcelle_numero',
        'code_commune', 'periode_construction',
    ],
    dpe: [
        'identifiant_dpe', 'classe_bilan_dpe', 'classe_emission_ges',
        'type_batiment_dpe', 'annee_construction', 'surface_habitable',
        'date_reception_dpe', 'numero_dpe',
    ],
    address: [
        'label', 'numero', 'rue', 'code_postal', 'ville',
        'code_commune', 'latitude', 'longitude', 'score',
    ],
};

/* ---- DOM helpers ---- */

function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

function esc(v) {
    if (v === null || v === undefined) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
    if (str.length === 9) return `${str.slice(0,3)} ${str.slice(3,6)} ${str.slice(6)}`;
    if (str.length === 14) return `${str.slice(0,3)} ${str.slice(3,6)} ${str.slice(6,9)} ${str.slice(9)}`;
    return str;
}

function fmtKey(k) {
    return FIELD_LABELS[k] || k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

/* ---- Network ---- */

function endpoint(path, params = {}) {
    const url = new URL(path, window.location.href);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
    });
    return url;
}

async function getJson(path, params = {}) {
    const res = await fetch(endpoint(path, params), { headers: { Accept: 'application/json' } });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `Erreur HTTP ${res.status}`);
    return data;
}

/* ---- State / view transitions ---- */

function showLanding() {
    qs('#landing').classList.remove('hidden');
    qs('#workspace').classList.add('hidden');
    qs('#search-form').classList.add('hidden');
}

function showWorkspace() {
    qs('#landing').classList.add('hidden');
    qs('#workspace').classList.remove('hidden');
    qs('#search-form').classList.remove('hidden');
}

function openOverlay(id) {
    qs(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOverlay(id) {
    qs(id).classList.add('hidden');
    document.body.style.overflow = '';
}

/* ================================================
   RESULT LIST RENDERING
   ================================================ */

function renderResults(items) {
    state.results = items;
    const container = qs('#results');

    if (!items.length) {
        qs('#result-meta').textContent = 'Aucun résultat';
        qs('#type-filters').innerHTML = '';
        container.innerHTML = '<div class="empty-msg">Aucun résultat pour cette recherche.</div>';
        return;
    }

    /* Count per type */
    const counts = {};
    items.forEach(it => { counts[it.type] = (counts[it.type] || 0) + 1; });
    const types = Object.keys(counts);

    qs('#result-meta').textContent = `${items.length} résultat${items.length > 1 ? 's' : ''}`;

    /* Type filter pills (only if multiple types) */
    if (types.length > 1) {
        qs('#type-filters').innerHTML = types.map(t => `
            <button class="type-pill${state.activeFilter === t ? ' active' : ''}" data-type="${t}">
                ${esc(TYPE_LABEL[t] || t)}
                <span class="count-badge">${counts[t]}</span>
            </button>
        `).join('');
        qsa('.type-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                state.activeFilter = state.activeFilter === pill.dataset.type ? null : pill.dataset.type;
                renderResults(state.results);
            });
        });
    } else {
        qs('#type-filters').innerHTML = '';
        state.activeFilter = null;
    }

    const visible = state.activeFilter ? items.filter(i => i.type === state.activeFilter) : items;

    container.innerHTML = visible.map(item => {
        const color = TYPE_COLOR[item.type] || '#334155';
        const idx   = items.indexOf(item);
        return `
            <button class="result-card" type="button" data-index="${idx}">
                <span class="rc-type" style="color:${color}">${esc(TYPE_LABEL[item.type] || item.type)}</span>
                <span class="rc-name">${esc(item.title || item.id)}</span>
                <span class="rc-sub">${esc(item.subtitle || item.id)}</span>
            </button>
        `;
    }).join('');

    qsa('.result-card').forEach(btn => {
        btn.addEventListener('click', () => {
            qsa('.result-card').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadEntity(items[Number(btn.dataset.index)]);
        });
    });
}

/* ================================================
   DETAIL RENDERING
   ================================================ */

function firstObject(payload) {
    const order = ['company', 'establishment', 'person', 'parcel', 'address', 'copro', 'dpe'];
    const key = order.find(n => payload[n] && typeof payload[n] === 'object' && !Array.isArray(payload[n]))
        || Object.keys(payload).find(n =>
            !['type', 'source', 'raw'].includes(n) &&
            payload[n] && typeof payload[n] === 'object' && !Array.isArray(payload[n])
        );
    return key ? payload[key] : {};
}

function entityName(type, obj) {
    if (type === 'company') return obj.nom_entreprise || obj.denomination || fmtSiren(obj.siren) || '—';
    if (type === 'establishment') return obj.nom_commercial || obj.enseigne || obj.siret || '—';
    if (type === 'person') return [obj.prenom, obj.nom].filter(Boolean).join(' ') || obj.id || '—';
    if (type === 'parcel') return obj.adresse || obj.numero || '—';
    if (type === 'address') return obj.label || obj.id || '—';
    if (type === 'copro') return obj.nom || obj.numero_immatriculation || '—';
    if (type === 'dpe') return obj.identifiant_dpe || 'DPE';
    return obj.id || '—';
}

function entityId(type, obj) {
    if (type === 'company') return obj.siren ? fmtSiren(obj.siren) : null;
    if (type === 'establishment') return obj.siret ? fmtSiren(obj.siret) : null;
    if (type === 'parcel') return obj.numero || null;
    if (type === 'address') return obj.code_postal ? `${obj.code_postal} ${obj.ville || ''}`.trim() : null;
    if (type === 'copro') return obj.numero_immatriculation || null;
    if (type === 'dpe') return obj.identifiant_dpe || null;
    return null;
}

function statusBadge(statut) {
    if (!statut) return '';
    const lower = String(statut).toLowerCase();
    const cls = (lower.includes('actif') || lower === 'a')
        ? 'status-actif'
        : (lower.includes('ferm') || lower === 'f' || lower === 'c' || lower === 'r')
            ? 'status-ferme'
            : 'status-unknown';
    return `<span class="badge ${cls}">${esc(statut)}</span>`;
}

function dpeBadge(cls) {
    if (!cls) return '';
    const letter = String(cls).trim().charAt(0).toUpperCase();
    return `<span class="dpe-badge dpe-${letter}" title="Classe DPE ${letter}">${letter}</span>`;
}

function renderKV(type, obj) {
    const keys = PRIMARY_KEYS[type] || Object.keys(obj).filter(k => !HIDDEN_FIELDS.has(k)).slice(0, 12);
    const items = keys
        .filter(k => k in obj && !HIDDEN_FIELDS.has(k))
        .map(k => {
            const raw = obj[k];
            const v   = fmt(raw);
            const isNull = v === null;
            return `
                <div class="kv-card">
                    <div class="kv-label">${esc(fmtKey(k))}</div>
                    <div class="kv-value${isNull ? ' is-null' : ''}">
                        ${k === 'siren' || k === 'siret' ? esc(fmtSiren(v)) : isNull ? '—' : esc(v)}
                    </div>
                </div>
            `;
        });
    return items.length
        ? `<div class="kv-grid">${items.join('')}</div>`
        : '';
}

function renderSection(name, rows) {
    if (!Array.isArray(rows)) return '';
    const title = SECTION_LABELS[name] || fmtKey(name);
    const count = rows.length;

    const header = `
        <div class="entity-section">
            <div class="section-row">
                <span class="section-title">${esc(title)}</span>
                <span class="count-badge">${count}</span>
            </div>
    `;

    if (count === 0) return header + '</div>';

    const cols = [...new Set(rows.flatMap(r => Object.keys(r)))]
        .filter(c => !HIDDEN_FIELDS.has(c))
        .slice(0, 9);
    const body = `
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>${cols.map(c => `<th>${esc(fmtKey(c))}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${rows.slice(0, 12).map(row => `
                        <tr>${cols.map(c => {
                            const v = fmt(row[c]);
                            return `<td>${v !== null ? esc(v) : '<span style="color:var(--muted)">—</span>'}</td>`;
                        }).join('')}</tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ${rows.length > 12 ? `<p style="font-size:12px;color:var(--muted);margin-top:6px">… et ${rows.length - 12} ligne(s) supplémentaire(s) dans le JSON complet.</p>` : ''}
    `;

    return header + body + '</div>';
}

function renderDetail(payload) {
    const type = payload.type;
    const obj  = firstObject(payload);
    const name = entityName(type, obj);
    const id   = entityId(type, obj);
    const typeCss = TYPE_CSS[type] || '';

    const statut = obj.statut_rcs || obj.etat_administratif || obj.statut || null;
    const dpeClass = type === 'dpe' ? (obj.classe_bilan_dpe || null) : null;

    const sections = Object.entries(payload)
        .filter(([k, v]) => k !== 'type' && Array.isArray(v))
        .map(([k, rows]) => renderSection(k, rows))
        .join('');

    qs('#detail').innerHTML = `
        <div class="entity-head">
            <div class="entity-meta">
                <div class="entity-badges">
                    <span class="badge ${typeCss}">${esc(TYPE_LABEL[type] || type)}</span>
                    ${statut ? statusBadge(statut) : ''}
                    ${dpeClass ? dpeBadge(dpeClass) : ''}
                </div>
                <h1 class="entity-name">${esc(name)}</h1>
                ${id ? `<div class="entity-id">${esc(id)}</div>` : ''}
            </div>
            <div class="entity-actions">
                <button type="button" class="btn-sm primary" id="btn-show-json">JSON</button>
            </div>
        </div>

        ${renderKV(type, obj)}
        ${sections}
    `;

    qs('#btn-show-json').addEventListener('click', () => {
        const json = JSON.stringify(payload, null, 2);
        qs('#json-output').textContent = json;
        /* download link */
        const blob = new Blob([json], { type: 'application/json' });
        const dl = qs('#download-json');
        dl.href = URL.createObjectURL(blob);
        dl.download = `registers_${type}_${Date.now()}.json`;
        openOverlay('#overlay-json');
    });
}

/* ================================================
   ENTITY LOADING
   ================================================ */

async function loadEntity(item) {
    qs('#detail').innerHTML = `
        <div class="empty-centered">
            <div class="spinner"></div>
            <p>Chargement…</p>
        </div>
    `;
    try {
        const res = await getJson('api/live_detail.php', { type: item.type, id: item.detail_id || item.id });
        state.currentPayload = res.data;
        renderDetail(res.data);
    } catch (err) {
        qs('#detail').innerHTML = `
            <div class="empty-centered">
                <p style="color:var(--s-ferme)">${esc(err.message)}</p>
            </div>
        `;
    }
}

/* ================================================
   SEARCH
   ================================================ */

async function runSearch(event) {
    if (event) event.preventDefault();

    /* Lire depuis le hero ou depuis la navbar selon lequel est visible */
    const fromHero = qs('#landing') && !qs('#landing').classList.contains('hidden');
    const q    = fromHero ? qs('#hero-query').value.trim() : qs('#search-query').value.trim();
    const type = fromHero ? qs('#hero-type').value        : qs('#search-type').value;
    if (!q) return;

    /* Synchroniser la barre navbar avec la valeur hero */
    qs('#search-query').value = q;
    qs('#search-type').value  = type;

    state.activeFilter = null;
    showWorkspace();

    qs('#results').innerHTML = `
        <div class="empty-msg" style="display:flex;align-items:center;gap:10px">
            <div class="spinner" style="width:16px;height:16px;border-width:2.5px"></div>
            Recherche en cours…
        </div>
    `;
    qs('#detail').innerHTML = `
        <div class="empty-centered">
            <p>Sélectionne un résultat.</p>
        </div>
    `;
    qs('#result-meta').textContent = '';
    qs('#type-filters').innerHTML = '';

    try {
        const payload = await getJson('api/live_search.php', { q, type, limit: 20 });
        renderResults(payload.results || []);
    } catch (err) {
        qs('#results').innerHTML = `<div class="empty-msg" style="color:var(--s-ferme)">${esc(err.message)}</div>`;
    }
}

/* ================================================
   SCHEMA
   ================================================ */

async function loadSchema() {
    const target = qs('#schema-table');
    target.innerHTML = '<div class="empty-msg">Chargement…</div>';
    try {
        const payload = await getJson('api/schema.php');
        const rows = payload.objects || [];
        if (!rows.length) {
            target.innerHTML = '<div class="empty-msg">Aucun objet trouvé.</div>';
            return;
        }
        target.innerHTML = `
            <table>
                <thead>
                    <tr><th>Objet</th><th>Type</th><th>Lignes</th></tr>
                </thead>
                <tbody>
                    ${rows.map(r => `
                        <tr>
                            <td>${esc(r.name)}</td>
                            <td>${esc(r.type)}</td>
                            <td style="color:var(--muted);font-variant-numeric:tabular-nums">${esc(r.rows ?? '—')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } catch (err) {
        target.innerHTML = `<div class="empty-msg" style="color:var(--s-ferme)">${esc(err.message)}</div>`;
    }
}

/* ================================================
   EVENT WIRING
   ================================================ */

/* Formulaires de recherche (hero landing + navbar workspace) */
qs('#hero-form').addEventListener('submit', runSearch);
qs('#search-form').addEventListener('submit', runSearch);

/* Domain cards → préfiltrer le type et mettre le focus sur le hero */
qsa('.domain-card').forEach(card => {
    card.addEventListener('click', () => {
        const domain = card.dataset.domain;
        const type = (domain === 'address') ? 'address' : 'company';
        qs('#hero-type').value = type;
        qs('#search-type').value = type;
        qs('#hero-query').focus();
    });
});

/* Brand → back to landing */
qs('#brand-link').addEventListener('click', e => {
    e.preventDefault();
    showLanding();
    qs('#search-query').value = '';
});

/* Sources overlay */
qs('#btn-sources').addEventListener('click', () => openOverlay('#overlay-sources'));

/* Schema overlay */
qs('#btn-schema').addEventListener('click', () => {
    openOverlay('#overlay-schema');
    loadSchema();
});
qs('#refresh-schema').addEventListener('click', loadSchema);

/* Close buttons (data-close attribute) */
qsa('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => closeOverlay('#' + btn.dataset.close));
});

/* Backdrop click closes overlay */
qsa('.overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeOverlay('#' + overlay.id);
    });
});

/* Copy JSON */
qs('#copy-json').addEventListener('click', async () => {
    const text = qs('#json-output').textContent;
    try {
        await navigator.clipboard.writeText(text);
        const btn = qs('#copy-json');
        const orig = btn.textContent;
        btn.textContent = 'Copié ✓';
        setTimeout(() => { btn.textContent = orig; }, 1600);
    } catch (_) {
        /* silent fail */
    }
});

/* Keyboard shortcuts */
document.addEventListener('keydown', e => {
    /* / or Ctrl/Cmd+K → focus search */
    if ((e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k'))
        && document.activeElement !== qs('#search-query')) {
        e.preventDefault();
        qs('#search-query').focus();
        qs('#search-query').select();
    }
    /* Escape → close all overlays */
    if (e.key === 'Escape') {
        qsa('.overlay').forEach(o => {
            if (!o.classList.contains('hidden')) closeOverlay('#' + o.id);
        });
    }
});
