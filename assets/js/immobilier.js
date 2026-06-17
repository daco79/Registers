/* ================================================
   REGISTERS — Immobilier
   ================================================ */

const state = {
    source: 'dvf',
    results: [],
    page: 1,
    total: 0,
    loading: false,
    currentPayload: null,
};

const PER_PAGE = 20;

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

function fmtEuro(v) {
    if (!v) return null;
    const n = Number(v);
    if (isNaN(n)) return String(v);
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
}

function fmtSurface(v) {
    if (!v) return null;
    const n = Number(v);
    if (isNaN(n)) return String(v);
    return `${new Intl.NumberFormat('fr-FR').format(n)} m²`;
}

function fmtPrixM2(prix, surface) {
    const p = Number(prix), s = Number(surface);
    if (!p || !s || isNaN(p) || isNaN(s)) return null;
    return `${new Intl.NumberFormat('fr-FR').format(Math.round(p / s))} €/m²`;
}

const FIELD_LABELS = {
    adresse_nom_voie: 'Voie', adresse_numero: 'N°', adresse_code_postal: 'Code postal',
    adresse_nom_commune: 'Commune', code_commune: 'Code commune',
    valeur_fonciere: 'Valeur foncière', date_mutation: 'Date de mutation',
    type_local: 'Type', surface_reelle_bati: 'Surface bâtie (m²)',
    surface_terrain: 'Surface terrain (m²)', nombre_pieces_principales: 'Nb. pièces',
    nature_mutation: 'Nature', section: 'Section', numero_plan: 'N° plan',
    /* DPE */
    identifiant_dpe: 'Identifiant DPE', numero_dpe: 'N° DPE',
    classe_bilan_dpe: 'Classe DPE', classe_emission_ges: 'Classe GES',
    type_batiment_dpe: 'Type bâtiment', annee_construction: 'Année construction',
    surface_habitable: 'Surface (m²)', date_reception_dpe: 'Date DPE',
    /* RNIC */
    numero_immatriculation: 'N° immatriculation', nom: 'Nom',
    adresse: 'Adresse', nombre_total_lots: 'Nb. lots', type_syndic: 'Syndic',
    periode_construction: 'Période construction',
    /* geo */
    geo_adresse: 'Adresse géo', id: 'Identifiant',
    classe_consommation_energie: 'Classe énergie', classe_estimation_ges: 'Classe GES',
    surface_utile: 'Surface utile (m²)',
};

const HIDDEN = new Set(['raw_json', 'raw', 'source']);

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
function collectFilters(source) {
    const form = qs(`#form-${source}`);
    if (!form) return {};
    const fd = new FormData(form);
    const f = { source };
    for (const [k, v] of fd.entries()) {
        if (v && v.trim()) f[k] = v.trim();
    }
    /* Déterminer département depuis CP */
    if (f.code_postal && f.code_postal.length >= 2) {
        const cp = f.code_postal;
        if (/^\d{5}$/.test(cp)) {
            if (!f.departement) f.departement = cp.startsWith('97') ? cp.slice(0, 3) : cp.slice(0, 2);
        }
    }
    return f;
}

/* ---- Search ---- */
async function runSearch(append = false) {
    if (state.loading) return;
    state.loading = true;

    const source = state.source;

    if (!append) {
        state.page = 1;
        state.results = [];
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
        const filters = collectFilters(source);
        const params = { ...filters, page: state.page, par_page: PER_PAGE };
        const data = await getJson('api/live_immobilier.php', params);
        state.total = data.total || 0;
        const items = data.resultats || [];

        if (!append) {
            state.results = items;
        } else {
            state.results = state.results.concat(items);
        }

        renderResults(source, items, append);
    } catch (err) {
        if (!append) {
            qs('#results').innerHTML = `<div class="empty-msg" style="color:var(--s-ferme)">${esc(err.message)}</div>`;
            qs('#result-meta').innerHTML = '<span>Erreur</span>';
        }
    } finally {
        state.loading = false;
    }
}

/* ---- Render results ---- */
function renderResults(source, items, append) {
    const container = qs('#results');
    if (!append) container.innerHTML = '';

    const total = state.total;
    const sourceLabel = source === 'dvf' ? 'vente' : source === 'dpe' ? 'DPE' : 'copropriété';
    qs('#result-meta').innerHTML =
        `<span>${total.toLocaleString('fr-FR')} ${sourceLabel}${total > 1 ? 's' : ''}</span>`;

    if (!append && !items.length) {
        container.innerHTML = '<div class="empty-msg">Aucun résultat pour ces filtres.</div>';
        qs('#load-more-wrap').classList.add('hidden');
        return;
    }

    items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rc-lg';

        if (source === 'dvf') renderDvfCard(btn, item);
        else if (source === 'dpe') renderDpeCard(btn, item);
        else renderRnicCard(btn, item);

        btn.addEventListener('click', () => {
            qsa('.rc-lg').forEach(b => { b.classList.remove('active', 'active-re'); });
            btn.classList.add('active-re');
            renderDetail(source, item);
        });

        container.appendChild(btn);
    });

    const shown = state.results.length;
    if (shown < total) {
        qs('#load-more-wrap').classList.remove('hidden');
        qs('#load-more-btn').disabled = false;
        qs('#load-more-btn').textContent = `Charger plus (${shown} / ${total})`;
    } else {
        qs('#load-more-wrap').classList.add('hidden');
    }
}

function renderDvfCard(btn, item) {
    const adresse = [
        item.adresse_numero, item.adresse_nom_voie,
        item.adresse_code_postal, item.adresse_nom_commune
    ].filter(Boolean).join(' ');
    const prix = item.valeur_fonciere ? fmtEuro(item.valeur_fonciere) : null;
    const surf = item.surface_reelle_bati ? fmtSurface(item.surface_reelle_bati) : null;
    const pm2  = fmtPrixM2(item.valeur_fonciere, item.surface_reelle_bati);
    const date = item.date_mutation ? item.date_mutation.slice(0, 7) : null;
    const type = item.type_local || item.nature_mutation || '';

    btn.innerHTML = `
        <div class="rc-lg-top">
            <span class="badge type-address">DVF</span>
            ${type ? `<span style="font-size:11px;color:var(--muted)">${esc(type)}</span>` : ''}
        </div>
        <div class="rc-lg-name">${esc(adresse || '—')}</div>
        ${prix ? `<div class="rc-lg-price">${esc(prix)}</div>` : ''}
        <div class="rc-lg-meta">
            <span>${surf ? esc(surf) : ''}${surf && pm2 ? ' · ' : ''}${pm2 ? esc(pm2) : ''}</span>
            <span>${date ? esc(date) : ''}</span>
        </div>`;
}

function renderDpeCard(btn, item) {
    const adresse = item.adresse || item.geo_adresse || item.label || '—';
    const classe = item.classe_bilan_dpe || item.classe_consommation_energie || '';
    const surf = item.surface_habitable || item.surface_utile;
    const date = item.date_reception_dpe ? item.date_reception_dpe.slice(0, 7) : null;

    btn.innerHTML = `
        <div class="rc-lg-top">
            <span class="badge type-dpe">DPE</span>
            ${classe ? `<span class="dpe-badge dpe-${classe.trim().charAt(0).toUpperCase()}">${esc(classe.trim().charAt(0).toUpperCase())}</span>` : ''}
        </div>
        <div class="rc-lg-name">${esc(adresse)}</div>
        <div class="rc-lg-meta">
            <span>${surf ? fmtSurface(surf) : ''}</span>
            <span>${date ? esc(date) : ''}</span>
        </div>`;
}

function renderRnicCard(btn, item) {
    const nom = item.nom || item.numero_immatriculation || '—';
    const adresse = item.adresse || '';
    const lots = item.nombre_total_lots;
    const syndic = item.type_syndic || '';

    btn.innerHTML = `
        <div class="rc-lg-top">
            <span class="badge type-copro">Copropriété</span>
        </div>
        <div class="rc-lg-name">${esc(nom)}</div>
        <div class="rc-lg-sub">${esc(adresse)}</div>
        <div class="rc-lg-meta">
            <span>${lots ? `${lots} lots` : ''}${lots && syndic ? ' · ' : ''}${syndic ? esc(syndic) : ''}</span>
        </div>`;
}

/* ---- Detail panel ---- */
function renderDetail(source, item) {
    if (source === 'dvf') renderDvfDetail(item);
    else if (source === 'dpe') renderDpeDetail(item);
    else renderRnicDetail(item);
}

function kvCard(label, value) {
    if (!value && value !== 0) return '';
    return `
        <div class="kv-card">
            <div class="kv-label">${esc(label)}</div>
            <div class="kv-value">${esc(String(value))}</div>
        </div>`;
}

function detailWrapper(badge, title, subtitle, kvHtml, extraHtml) {
    return `
        <div class="entity-head">
            <div class="entity-meta">
                <div class="entity-badges">${badge}</div>
                <h1 class="entity-name">${esc(title)}</h1>
                ${subtitle ? `<div class="entity-id">${esc(subtitle)}</div>` : ''}
            </div>
            <div class="entity-actions">
                <button type="button" class="btn-sm primary" id="btn-show-json">JSON</button>
            </div>
        </div>
        ${kvHtml ? `<div class="kv-grid">${kvHtml}</div>` : ''}
        ${extraHtml || ''}
    `;
}

function renderDvfDetail(item) {
    const adresse = [item.adresse_numero, item.adresse_nom_voie, item.adresse_code_postal, item.adresse_nom_commune]
        .filter(Boolean).join(' ');
    const prix = item.valeur_fonciere ? fmtEuro(item.valeur_fonciere) : null;
    const surf = item.surface_reelle_bati ? fmtSurface(item.surface_reelle_bati) : null;
    const pm2  = fmtPrixM2(item.valeur_fonciere, item.surface_reelle_bati);

    const kv = [
        kvCard('Valeur foncière', prix),
        kvCard('Surface bâtie', surf),
        kvCard('Prix / m²', pm2),
        kvCard('Surface terrain', item.surface_terrain ? fmtSurface(item.surface_terrain) : null),
        kvCard('Type de local', item.type_local),
        kvCard('Nature mutation', item.nature_mutation),
        kvCard('Nb. pièces', item.nombre_pieces_principales),
        kvCard('Date mutation', item.date_mutation),
        kvCard('Commune', item.adresse_nom_commune),
        kvCard('Code commune', item.code_commune),
        kvCard('Section', item.section),
        kvCard('N° plan (parcelle)', item.numero_plan),
    ].join('');

    qs('#detail').innerHTML = detailWrapper(
        `<span class="badge type-address">DVF</span>`,
        adresse || '—',
        item.date_mutation ? `Vente du ${item.date_mutation}` : null,
        kv
    );

    qs('#btn-show-json').addEventListener('click', () => showJson(item, 'dvf'));
}

function renderDpeDetail(item) {
    const adresse = item.adresse || item.geo_adresse || item.label || '—';
    const classe = item.classe_bilan_dpe || item.classe_consommation_energie || '';
    const letter = classe.trim().charAt(0).toUpperCase();
    const surf = item.surface_habitable || item.surface_utile;

    const kv = [
        kvCard('Classe DPE', classe),
        kvCard('Classe GES', item.classe_emission_ges || item.classe_estimation_ges),
        kvCard('Type bâtiment', item.type_batiment_dpe),
        kvCard('Année construction', item.annee_construction),
        kvCard('Surface', surf ? fmtSurface(surf) : null),
        kvCard('Date DPE', item.date_reception_dpe),
        kvCard('N° DPE', item.numero_dpe || item.identifiant_dpe || item.id),
    ].join('');

    qs('#detail').innerHTML = detailWrapper(
        `<span class="badge type-dpe">DPE</span>${letter ? ` <span class="dpe-badge dpe-${letter}">${letter}</span>` : ''}`,
        adresse,
        null,
        kv
    );

    qs('#btn-show-json').addEventListener('click', () => showJson(item, 'dpe'));
}

function renderRnicDetail(item) {
    const nom = item.nom || item.numero_immatriculation || '—';
    const kv = [
        kvCard('N° immatriculation', item.numero_immatriculation),
        kvCard('Adresse', item.adresse),
        kvCard('Nb. lots total', item.nombre_total_lots),
        kvCard('Type de syndic', item.type_syndic),
        kvCard('Période construction', item.periode_construction),
        kvCard('Code commune', item.code_commune),
    ].join('');

    qs('#detail').innerHTML = detailWrapper(
        `<span class="badge type-copro">Copropriété</span>`,
        nom,
        item.adresse || null,
        kv
    );

    qs('#btn-show-json').addEventListener('click', () => showJson(item, 'rnic'));
}

/* ---- JSON overlay ---- */
function showJson(payload, name) {
    state.currentPayload = payload;
    const json = JSON.stringify(payload, null, 2);
    qs('#json-output').textContent = json;
    const blob = new Blob([json], { type: 'application/json' });
    const dl = qs('#download-json');
    dl.href = URL.createObjectURL(blob);
    dl.download = `registers_${name}_${Date.now()}.json`;
    qs('#overlay-json').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

/* ---- Overlays ---- */
function closeOverlay(id) {
    qs('#' + id).classList.add('hidden');
    document.body.style.overflow = '';
}

/* ---- Event wiring ---- */

/* Onglets source */
qsa('.source-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        qsa('.source-tab').forEach(t => t.classList.remove('active'));
        qsa('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const panel = qs(`#form-${tab.dataset.tab}`);
        if (panel) panel.classList.add('active');
        state.source = tab.dataset.tab;
        state.results = [];
        state.total = 0;
        qs('#results').innerHTML = '<div class="empty-msg">Lancez une recherche.</div>';
        qs('#result-meta').innerHTML = '<span>Résultats</span>';
        qs('#detail').innerHTML = `<div class="empty-centered"><p>Sélectionnez un résultat.</p></div>`;
        qs('#load-more-wrap').classList.add('hidden');
    });
});

/* Formulaires */
qsa('.tab-panel').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        runSearch(false);
    });
});

/* Réinitialiser */
qsa('[data-reset]').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = qs('#' + btn.dataset.reset);
        if (form) form.reset();
        state.results = [];
        state.total = 0;
        qs('#results').innerHTML = '<div class="empty-msg">Lancez une recherche.</div>';
        qs('#result-meta').innerHTML = '<span>Résultats</span>';
        qs('#detail').innerHTML = `<div class="empty-centered"><p>Sélectionnez un résultat.</p></div>`;
        qs('#load-more-wrap').classList.add('hidden');
    });
});

/* Load more */
qs('#load-more-btn').addEventListener('click', () => runSearch(true));

/* Sources overlay */
qs('#btn-sources').addEventListener('click', () => {
    qs('#overlay-sources').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
});

qsa('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => closeOverlay(btn.dataset.close));
});

qsa('.overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeOverlay(overlay.id);
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
            if (!o.classList.contains('hidden')) closeOverlay(o.id);
        });
    }
});
