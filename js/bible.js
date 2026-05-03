const bibleForm = document.getElementById('bible-search-form');
const bibleResult = document.getElementById('bible-result');

function renderBibleError(message) {
    bibleResult.innerHTML = `<div class="alert alert-error">${message}</div>`;
}

function renderBiblePassage(data) {
    if (!data || !data.verses) {
        renderBibleError('Não foi possível encontrar a passagem. Verifique a referência.');
        return;
    }

    const verses = data.verses.map((verse) => {
        return `<p><strong>${verse.book_name} ${verse.chapter}:${verse.verse}</strong> ${verse.text}</p>`;
    }).join('');

    bibleResult.innerHTML = `
        <div>
            <h3>${data.reference}</h3>
            <p style="opacity:.75; margin-bottom:1rem;">Tradução: ${data.translation_name}</p>
            ${verses}
        </div>
    `;
}

function normalizeReference(value) {
    return value.trim().replace(/\s+/, ' ');
}

async function fetchBible(reference, translation) {
    const encoded = encodeURIComponent(reference);
    const url = `https://bible-api.com/${encoded}?translation=${translation}`;
    const response = await fetch(url);
    if (!response.ok) {
        const body = await response.json().catch(() => null);
        throw new Error((body && body.error) ? body.error : 'Não foi possível carregar a passagem.');
    }
    return response.json();
}

if (bibleForm) {
    bibleForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const referenceInput = document.getElementById('bible-reference');
        const translationSelect = document.getElementById('bible-translation');

        const reference = normalizeReference(referenceInput.value);
        if (!reference) {
            renderBibleError('Informe a referência da Bíblia para buscar.');
            return;
        }

        bibleResult.innerHTML = '<p>Buscando passagem...</p>';

        try {
            const data = await fetchBible(reference, translationSelect.value);
            renderBiblePassage(data);
        } catch (error) {
            renderBibleError(error.message);
        }
    });
}
