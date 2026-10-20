(() => {
    'use strict';
    const labels = {titre:'Titre', description:'Description', vetement_origine:'Vêtement d’origine', resultat:'Résultat', materiel_necessaire:'Matériel nécessaire', difficulte:'Difficulté', duree_minutes:'Durée (minutes)', contenu:'Consignes détaillées', outils_necessaires:'Outils nécessaires', conseils_securite:'Conseils de sécurité', numero:'Numéro'};
    const token = () => document.querySelector('input[name="_token"]')?.value;
    const message = (root, text) => { root.querySelector('[data-ai-message]').textContent = text; };
    let retryUntil = 0;
    let retryTimer = null;
    const refreshCooldown = () => {
        const remaining = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));
        document.querySelectorAll('[data-ai-generate]').forEach(button => {
            button.dataset.aiOriginalLabel ||= button.textContent;
            button.disabled = remaining > 0 || button.dataset.aiBusy === 'true';
            button.textContent = remaining > 0 ? `Réessayer dans ${remaining} s` : button.dataset.aiOriginalLabel;
        });
        if (retryTimer) clearTimeout(retryTimer);
        retryTimer = remaining > 0 ? setTimeout(refreshCooldown, 1000) : null;
    };
    const cooldown = seconds => {
        if (!Number.isFinite(seconds) || seconds < 0) return;
        retryUntil = Math.max(retryUntil, Date.now() + seconds * 1000);
        refreshCooldown();
    };
    const request = async (root, data) => {
        const response = await fetch(root.dataset.url, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN':token()}, body:JSON.stringify(data)});
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = Object.values(json.errors || {}).flat().join(' ');
            const error = new Error(errors || json.message || 'La demande a échoué. Réessayez ou continuez manuellement.');
            const rawDelay = response.headers.get('Retry-After');
            const headerDelay = rawDelay && /^\d+$/.test(rawDelay.trim()) ? Number(rawDelay) : rawDelay ? Math.max(0, Math.ceil((Date.parse(rawDelay) - Date.now()) / 1000)) : null;
            error.retryAfter = typeof json.retry_after === 'number' ? json.retry_after : headerDelay;
            error.fallbackUrl = json.fallback_url;
            throw error;
        }
        return json.proposition;
    };
    const run = async (root, button, task) => {
        if (retryUntil > Date.now() || button.dataset.aiBusy === 'true') return;
        button.dataset.aiBusy = 'true'; button.disabled = true; message(root,'Préparation en cours…');
        try { await task(); message(root,'Proposition prête. Relisez-la avant de la valider.'); }
        catch (error) {
            message(root,error.message || 'Le service ne répond pas. Continuez manuellement.');
            if (error.retryAfter !== null && error.retryAfter !== undefined) cooldown(error.retryAfter);
            if (error.fallbackUrl) {
                const url = new URL(error.fallbackUrl, window.location.href);
                if (url.origin === window.location.origin && url.pathname === '/upcycling') {
                    const link = document.createElement('a'); link.href = url.href; link.target = '_blank'; link.rel = 'noopener';
                    link.textContent = 'Voir des projets similaires avec la recherche locale (sans API)';
                    root.querySelector('[data-ai-message]').append(document.createElement('br'), link);
                }
            }
        }
        finally { delete button.dataset.aiBusy; refreshCooldown(); }
    };
    const showValues = (container, values) => {
        container.replaceChildren();
        for (const [key, value] of Object.entries(values)) {
            if (key === 'etapes') continue;
            const heading = document.createElement('dt'); heading.textContent = labels[key] || key;
            const content = document.createElement('dd'); content.textContent = String(value ?? ''); content.style.whiteSpace = 'pre-wrap';
            container.append(heading,content);
        }
    };
    document.querySelectorAll('[data-ai-project]').forEach(root => {
        let suggestion = null;
        const button = root.querySelector('[data-ai-generate]');
        button.addEventListener('click', () => run(root,button,async () => {
            suggestion = await request(root,{idee:root.querySelector('[data-ai-idea]').value});
            showValues(root.querySelector('[data-ai-values]'),suggestion);
            root.querySelector('[data-ai-preview]').hidden = false;
        }));
        root.querySelector('[data-ai-apply]').addEventListener('click', () => {
            if (!suggestion) return;
            const form = root.closest('form');
            for (const field of ['titre','description','vetement_origine','resultat','difficulte','duree_minutes','materiel_necessaire']) {
                const input = form.elements.namedItem(field);
                if (input && suggestion[field] !== undefined) { input.value = suggestion[field]; input.dispatchEvent(new Event('input',{bubbles:true})); input.dispatchEvent(new Event('change',{bubbles:true})); }
            }
            message(root,'Champs remplis. Corrigez-les si nécessaire et ajoutez vos photos.');
        });
    });
    document.querySelectorAll('[data-ai-steps]').forEach(root => {
        const form = root.querySelector('[data-ai-steps-form]');
        const list = root.querySelector('[data-ai-steps-list]');
        let counter = 0;
        const add = data => {
            if (list.children.length >= 12) { message(root,'L’aperçu accepte au maximum 12 étapes à la fois.'); return; }
            const i = counter++;
            const fragment = root.querySelector('template').content.cloneNode(true);
            const block = fragment.querySelector('[data-ai-step]');
            const fields = block.querySelector('[data-ai-step-fields]');
            for (const field of ['numero','titre','contenu','outils_necessaires','duree_minutes','conseils_securite','photo']) {
                const label = document.createElement('label'); label.className = 'd-block my-2'; label.dataset.aiField = field; label.textContent = (labels[field] || 'Photo (JPG, PNG ou WEBP, 2 Mo maximum)') + ' *';
                const input = document.createElement(['contenu','outils_necessaires','conseils_securite'].includes(field) ? 'textarea' : 'input');
                input.name = `etapes[${i}][${field}]`; input.required = true; input.dataset.stepField = 'true'; input.className = 'form-control tcu-input';
                if (field === 'photo') { input.type = 'file'; input.accept = 'image/jpeg,image/png,image/webp'; }
                else if (['numero','duree_minutes'].includes(field)) { input.type = 'number'; input.min = '1'; input.max = field === 'numero' ? '100' : '1440'; input.step = '1'; input.value = data[field] ?? ''; }
                else { input.value = data[field] ?? ''; input.maxLength = field === 'titre' ? 150 : field === 'contenu' ? 5000 : 2000; input.minLength = field === 'titre' ? 3 : field === 'contenu' ? 10 : 1; }
                label.append(input); fields.append(label);
            }
            block.querySelector('[data-ai-accept]').addEventListener('change', event => block.querySelectorAll('[data-step-field]').forEach(input => { input.disabled = !event.target.checked; }));
            block.querySelector('[data-ai-remove]').addEventListener('click', () => block.remove());
            list.append(fragment);
        };
        const previous = root.querySelector('[data-ai-old-steps]');
        if (previous) {
            Object.values(JSON.parse(previous.textContent)).forEach(add); form.hidden = false;
            message(root,'Vos étapes ont été conservées. Corrigez les erreurs et sélectionnez à nouveau les photos.');
        }
        const button = root.querySelector('[data-ai-generate]');
        button.addEventListener('click', () => run(root,button,async () => {
            if (list.children.length && !window.confirm('Remplacer l’aperçu actuel ? Les étapes déjà enregistrées seront conservées.')) return;
            const steps = await request(root,{});
            list.replaceChildren(); counter = 0; steps.forEach(add); form.hidden = false;
        }));
        root.querySelector('[data-ai-add]').addEventListener('click', () => {
            const numbers = [...list.querySelectorAll('input[type="number"][name$="[numero]"]')].map(input => Number(input.value));
            add({numero:Math.max(Number(root.dataset.next)-1,...numbers)+1});
        });
        form.addEventListener('submit', event => {
            if (![...list.querySelectorAll('[data-ai-accept]')].some(input => input.checked)) { event.preventDefault(); message(root,'Sélectionnez au moins une étape à enregistrer.'); }
        });
    });
    document.querySelectorAll('[data-ai-translation]').forEach(root => {
        const button = root.querySelector('[data-ai-generate]');
        button.addEventListener('click', () => run(root,button,async () => {
            if (!root.querySelector('[data-ai-preview]').hidden && !window.confirm('Remplacer cet aperçu de traduction ? Vos corrections actuelles seront remplacées uniquement si la nouvelle traduction réussit.')) return;
            const proposal = await request(root,{langue:root.querySelector('[data-ai-language]').value});
            const container = root.querySelector('[data-ai-values]');
            container.dir = proposal.langue === 'ar' ? 'rtl' : 'ltr'; container.lang = proposal.langue;
            container.replaceChildren();
            const edit = (parent, field, value, name, optional = false) => {
                const label = document.createElement('label'); label.className = 'd-block my-2'; label.textContent = labels[field] || field;
                const input = document.createElement('textarea'); input.className = 'form-control tcu-input';
                input.name = name; input.value = value ?? ''; input.required = !optional;
                input.maxLength = field === 'titre' ? 150 : ['outils_necessaires','conseils_securite'].includes(field) ? 2000 : 5000;
                label.append(input); parent.append(label);
            };
            for (const field of ['titre','description','vetement_origine','resultat','materiel_necessaire']) {
                edit(container, field, proposal.texte[field], `texte[${field}]`);
            }
            proposal.texte.etapes.forEach((step, i) => {
                const section = document.createElement('fieldset');
                const title = document.createElement('legend'); title.textContent = `Étape ${step.numero ?? i + 1}`; section.append(title);
                const id = document.createElement('input'); id.type = 'hidden'; id.name = `texte[etapes][${i}][id]`; id.value = step.id; section.append(id);
                if (step.numero !== undefined) { const number = document.createElement('input'); number.type = 'hidden'; number.name = `texte[etapes][${i}][numero]`; number.value = step.numero; section.append(number); }
                for (const field of ['titre','contenu','outils_necessaires','conseils_securite']) {
                    edit(section, field, step[field], `texte[etapes][${i}][${field}]`, ['outils_necessaires','conseils_securite'].includes(field));
                }
                container.append(section);
            });
            if (!proposal.texte.etapes.length) {
                // HTML n’envoie pas de tableau vide ; le serveur le restaure explicitement.
                const empty = document.createElement('input'); empty.type = 'hidden'; empty.name = 'sans_etapes'; empty.value = '1'; container.append(empty);
            }
            const form = root.querySelector('[data-ai-translation-form]');
            form.action = root.dataset.confirmUrl.replace(/\/fr$/, '/' + proposal.langue); form.elements.namedItem('jeton').value = proposal.jeton;
            root.querySelector('[data-ai-preview]').hidden = false;
        }));
    });
})();
