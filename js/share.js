function openShareModal(prayerName, text) {
    // Create modal HTML if not exists
    if (!document.getElementById('shareModal')) {
        const modalHtml = `
        <div id="shareModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeShareModal()">&times;</span>
                <h3>Compartilhar Benção</h3>
                <p style="margin-bottom: 1rem;">Leve esta oração para mais pessoas!</p>
                
                <div class="share-preview">
                    <img src="assets/igreja.jpg" alt="Imagem para compartilhar">
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">"${text}"</p>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="assets/igreja.jpg" download="bencao-smg.jpg" class="btn">Baixar Imagem</a>
                    <button onclick="copyText('${text}')" class="btn btn-outline" style="border-color: var(--gold); color: var(--gold);">Copiar Texto</button>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    const modal = document.getElementById('shareModal');
    modal.style.display = "flex";
}

function closeShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) modal.style.display = "none";
}

function copyText(text) {
    navigator.clipboard.writeText(text + " - Paróquia Santa Maria Goretti").then(() => {
        alert("Texto copiado! Agora você pode colar no Instagram ou WhatsApp.");
    });
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('shareModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
