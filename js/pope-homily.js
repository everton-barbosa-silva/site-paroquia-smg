(function () {
  const root = document.getElementById('pope-homily-root');
  if (!root) return;

  const fallbackHtml = `
    <h3>Homilias do Santo Padre</h3>
    <p>Acompanhe as homilias e reflexoes diretamente em fontes oficiais da Santa Se.</p>
    <div class="pope-links">
      <a class="btn" href="https://www.vatican.va/content/vatican/pt.html" target="_blank" rel="noopener noreferrer">Portal do Vaticano</a>
      <a class="btn btn-outline" style="color: var(--gold-dark); border-color: var(--gold-dark);" href="https://www.vaticannews.va/pt/papa.html" target="_blank" rel="noopener noreferrer">Vatican News - Papa</a>
    </div>
    <p style="font-size:.88rem;color:var(--text-light);margin-top:1rem;">Sem uso de imagens protegidas: esta secao exibe apenas texto e links oficiais.</p>
  `;

  async function loadHomily() {
    try {
      const sourceRss = 'https://www.vaticannews.va/pt/papa.rss.xml';
      const proxyUrl = 'https://api.allorigins.win/raw?url=' + encodeURIComponent(sourceRss);
      const res = await fetch(proxyUrl, { method: 'GET' });

      if (!res.ok) {
        throw new Error('Falha ao buscar RSS do Papa');
      }

      const xmlText = await res.text();
      const parser = new DOMParser();
      const xml = parser.parseFromString(xmlText, 'text/xml');
      const item = xml.querySelector('item');

      if (!item) {
        throw new Error('RSS sem itens');
      }

      const title = (item.querySelector('title')?.textContent || '').trim();
      const link = (item.querySelector('link')?.textContent || '').trim();
      const pubDate = (item.querySelector('pubDate')?.textContent || '').trim();
      const description = (item.querySelector('description')?.textContent || '').replace(/<[^>]+>/g, '').trim();

      if (!title || !link) {
        throw new Error('RSS com dados incompletos');
      }

      root.innerHTML = `
        <h3>${title}</h3>
        <p><strong>Publicado em:</strong> ${pubDate || 'Data indisponivel'}</p>
        <p>${description || 'Leia a homilia/reflexao completa no link oficial abaixo.'}</p>
        <div class="pope-links">
          <a class="btn" href="${link}" target="_blank" rel="noopener noreferrer">Ler no Vatican News</a>
          <a class="btn btn-outline" style="color: var(--gold-dark); border-color: var(--gold-dark);" href="https://www.vatican.va/content/vatican/pt.html" target="_blank" rel="noopener noreferrer">Portal do Vaticano</a>
        </div>
        <p style="font-size:.88rem;color:var(--text-light);margin-top:1rem;">Sem uso de imagens protegidas: esta secao exibe apenas texto e links oficiais.</p>
      `;
    } catch (err) {
      root.innerHTML = fallbackHtml;
    }
  }

  loadHomily();
})();
