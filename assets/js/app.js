const state = {
    selected: null,
    lastJson: null,
};

const labels = {
    company: 'Entreprise',
    establishment: 'Etablissement',
    parcel: 'Parcelle',
    copro: 'Copropriete',
    dpe: 'DPE',
    address: 'Adresse',
};

const primaryKeys = {
    company: ['siren', 'nom_entreprise', 'denomination', 'forme_juridique', 'code_naf', 'statut_rcs'],
    establishment: ['siret', 'siren', 'nom_commercial', 'enseigne', 'adresse_ligne_1', 'code_postal', 'ville', 'siege'],
    parcel: ['numero', 'adresse', 'commune', 'code_commune', 'contenance', 'latitude', 'longitude'],
    copro: ['numero_immatriculation', 'nom', 'adresse', 'parcelle_numero', 'nombre_total_lots', 'type_syndic'],
    dpe: ['identifiant_dpe', 'parcelle_numero', 'classe_bilan_dpe', 'classe_emission_ges', 'type_batiment_dpe', 'date_reception_dpe'],
    address: ['id', 'label', 'numero', 'rue', 'ville', 'code_postal', 'code_commune', 'latitude', 'longitude'],
};

function qs(selector) {
    return document.querySelector(selector);
}

function qsa(selector) {
    return Array.from(document.querySelectorAll(selector));
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function endpoint(path, params = {}) {
    const url = new URL(path, window.location.href);
    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });
    return url;
}

async function getJson(path, params = {}) {
    const response = await fetch(endpoint(path, params), {headers: {'Accept': 'application/json'}});
    const payload = await response.json();
    if (!response.ok) {
        throw new Error(payload.error || `Erreur HTTP ${response.status}`);
    }
    return payload;
}

function setActiveTab(tabName) {
    qsa('.tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.tab === tabName));
    qsa('.panel').forEach((panel) => panel.classList.toggle('active', panel.id === `tab-${tabName}`));
    if (tabName === 'schema') {
        loadSchema();
    }
}

function renderResults(items) {
    const target = qs('#results');
    if (!items.length) {
        target.innerHTML = '<div class="empty">Aucun resultat local pour cette recherche.</div>';
        return;
    }

    target.innerHTML = items.map((item, index) => `
        <button class="result" type="button" data-index="${index}">
            <span class="badge">${escapeHtml(labels[item.type] || item.type)}</span>
            <strong>${escapeHtml(item.title || item.id)}</strong>
            <small>${escapeHtml(item.subtitle || item.id)}</small>
        </button>
    `).join('');

    qsa('.result').forEach((button) => {
        button.addEventListener('click', () => {
            qsa('.result').forEach((node) => node.classList.remove('active'));
            button.classList.add('active');
            loadEntity(items[Number(button.dataset.index)]);
        });
    });
}

function firstObject(payload) {
    const preferred = ['company', 'establishment', 'parcel', 'address', 'copro', 'dpe'];
    const key = preferred.find((name) => typeof payload[name] === 'object' && !Array.isArray(payload[name]) && payload[name])
        || Object.keys(payload).find((name) => !['source', 'raw'].includes(name) && typeof payload[name] === 'object' && !Array.isArray(payload[name]) && payload[name]);
    return key ? payload[key] : {};
}

function objectTitle(type, object) {
    if (type === 'company') return object.nom_entreprise || object.denomination || object.siren;
    if (type === 'establishment') return object.nom_commercial || object.enseigne || object.siret;
    if (type === 'parcel') return object.adresse || object.numero;
    if (type === 'address') return object.label || object.id;
    if (type === 'copro') return object.nom || object.numero_immatriculation;
    if (type === 'dpe') return object.identifiant_dpe || `DPE #${object.id}`;
    return object.id || 'Entite';
}

function renderKeyValues(type, object) {
    const keys = primaryKeys[type] || Object.keys(object).slice(0, 8);
    return `
        <div class="kv">
            ${keys.filter((key) => key in object).map((key) => `
                <div><span>${escapeHtml(key)}</span>${escapeHtml(formatValue(object[key]))}</div>
            `).join('')}
        </div>
    `;
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') return 'NULL';
    if (typeof value === 'object') return JSON.stringify(value);
    return value;
}

function renderRows(name, rows) {
    if (!Array.isArray(rows)) return '';
    const count = rows.length;
    if (count === 0) {
        return `<section class="block"><h3>${escapeHtml(name)} <span class="badge">0</span></h3></section>`;
    }

    const columns = Array.from(new Set(rows.flatMap((row) => Object.keys(row)))).slice(0, 8);
    return `
        <section class="block">
            <h3>${escapeHtml(name)} <span class="badge">${count}</span></h3>
            <table class="mini-table">
                <thead><tr>${columns.map((col) => `<th>${escapeHtml(col)}</th>`).join('')}</tr></thead>
                <tbody>
                    ${rows.slice(0, 8).map((row) => `
                        <tr>${columns.map((col) => `<td>${escapeHtml(formatValue(row[col]))}</td>`).join('')}</tr>
                    `).join('')}
                </tbody>
            </table>
        </section>
    `;
}

function renderDetail(payload) {
    const type = payload.type;
    const object = firstObject(payload);
    const title = objectTitle(type, object);
    const blocks = Object.entries(payload)
        .filter(([name, value]) => name !== 'type' && Array.isArray(value))
        .map(([name, rows]) => renderRows(name, rows))
        .join('');

    qs('#detail').innerHTML = `
        <div class="detail-header">
            <div class="detail-title">
                <span class="badge">${escapeHtml(labels[type] || type)}</span>
                <h2>${escapeHtml(title)}</h2>
                <p>${escapeHtml(object.siren || object.siret || object.numero || object.numero_immatriculation || object.identifiant_dpe || '')}</p>
            </div>
            <button type="button" id="detail-json">JSON</button>
        </div>
        <div class="section-grid">
            <section class="block">
                <h3>Champs principaux</h3>
                ${renderKeyValues(type, object)}
            </section>
            ${blocks}
        </div>
    `;

    qs('#detail-json').addEventListener('click', () => {
        state.lastJson = payload;
        qs('#json-output').textContent = JSON.stringify(payload, null, 2);
        const id = type === 'address'
            ? (object.label || object.id)
            : (object.siren || object.siret || object.numero || object.numero_immatriculation || object.identifiant_dpe || object.id);
        qs('#json-type').value = type;
        qs('#json-id').value = id || '';
        updateDownloadLink();
        setActiveTab('json');
    });
}

async function loadEntity(item) {
    state.selected = item;
    qs('#detail').innerHTML = '<div class="empty large">Chargement...</div>';
    try {
        const response = await getJson('api/live_detail.php', {type: item.type, id: item.detail_id || item.id});
        renderDetail(response.data);
    } catch (error) {
        qs('#detail').innerHTML = `<div class="empty large">${escapeHtml(error.message)}</div>`;
    }
}

async function runSearch(event) {
    event.preventDefault();
    const q = qs('#search-query').value.trim();
    const type = qs('#search-type').value;
    if (!q) {
        qs('#results').innerHTML = '<div class="empty">Entre au moins un identifiant, une adresse ou un nom.</div>';
        return;
    }

    qs('#results').innerHTML = '<div class="empty">Recherche...</div>';
    qs('#detail').innerHTML = '<div class="empty large">Selectionne un resultat pour voir le detail.</div>';

    try {
        const payload = await getJson('api/live_search.php', {q, type, limit: 20});
        renderResults(payload.results || []);
    } catch (error) {
        qs('#results').innerHTML = `<div class="empty">${escapeHtml(error.message)}</div>`;
    }
}

function updateDownloadLink() {
    const type = qs('#json-type').value;
    const id = qs('#json-id').value.trim();
    const mode = qs('#json-mode').value;
    const link = qs('#download-json');
    if (!id) {
        link.href = '#';
        link.setAttribute('aria-disabled', 'true');
        return;
    }
    link.href = endpoint('api/live_detail.php', {type, id, mode}).toString();
    link.removeAttribute('aria-disabled');
}

async function runJson(event) {
    event.preventDefault();
    const type = qs('#json-type').value;
    const id = qs('#json-id').value.trim();
    const mode = qs('#json-mode').value;
    if (!id) {
        qs('#json-output').textContent = 'Identifiant manquant.';
        return;
    }

    qs('#json-output').textContent = 'Chargement...';
    try {
        const response = await getJson('api/live_detail.php', {type, id, mode});
        state.lastJson = response.data;
        qs('#json-output').textContent = JSON.stringify(response.data, null, 2);
        updateDownloadLink();
    } catch (error) {
        qs('#json-output').textContent = error.message;
    }
}

async function runCompanyImport(event) {
    event.preventDefault();
    const identifier = qs('#company-identifier').value.trim();
    const output = qs('#company-import-output');
    if (!identifier) {
        output.textContent = 'Identifiant manquant.';
        return;
    }

    output.textContent = 'Import en cours...';
    try {
        const payload = await getJson('api/live_detail.php', {type: 'company', id: identifier});
        output.textContent = JSON.stringify(payload, null, 2);
        qs('#search-query').value = payload.data?.company?.siren || identifier;
        qs('#search-type').value = 'company';
    } catch (error) {
        output.textContent = error.message;
    }
}

async function runGeocode(event) {
    event.preventDefault();
    const q = qs('#geocode-query').value.trim();
    const output = qs('#geocode-output');
    if (!q) {
        output.textContent = 'Adresse manquante.';
        return;
    }

    output.textContent = 'Geocodage en cours...';
    try {
        const payload = await getJson('api/live_detail.php', {type: 'address', id: q});
        output.textContent = JSON.stringify(payload, null, 2);
    } catch (error) {
        output.textContent = error.message;
    }
}

async function loadSchema() {
    const target = qs('#schema-table');
    target.innerHTML = '<div class="empty">Chargement du schema...</div>';
    try {
        const payload = await getJson('api/schema.php');
        const rows = payload.objects || [];
        target.innerHTML = `
            <table>
                <thead>
                    <tr><th>Objet</th><th>Type</th><th>Lignes</th></tr>
                </thead>
                <tbody>
                    ${rows.map((row) => `
                        <tr>
                            <td>${escapeHtml(row.name)}</td>
                            <td>${escapeHtml(row.type)}</td>
                            <td>${escapeHtml(row.rows ?? '')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } catch (error) {
        target.innerHTML = `<div class="empty">${escapeHtml(error.message)}</div>`;
    }
}

qsa('.tab').forEach((tab) => tab.addEventListener('click', () => setActiveTab(tab.dataset.tab)));
qs('#search-form').addEventListener('submit', runSearch);
qs('#json-form').addEventListener('submit', runJson);
qs('#company-import-form').addEventListener('submit', runCompanyImport);
qs('#geocode-form').addEventListener('submit', runGeocode);
qs('#json-id').addEventListener('input', updateDownloadLink);
qs('#json-type').addEventListener('change', updateDownloadLink);
qs('#json-mode').addEventListener('change', updateDownloadLink);
qs('#refresh-schema').addEventListener('click', loadSchema);
qs('#copy-json').addEventListener('click', async () => {
    await navigator.clipboard.writeText(qs('#json-output').textContent);
});

updateDownloadLink();
