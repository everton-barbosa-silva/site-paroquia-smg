(function(){
    const root = document.getElementById('liturgy-root');
    const datePicker = document.getElementById('liturgy-date');
    
    if(!root) return;

    const API_BASE = 'https://liturgia.up.railway.app/v2/';

    // Set default date to today
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    
    if(datePicker) {
        datePicker.value = `${yyyy}-${mm}-${dd}`;
        datePicker.addEventListener('change', (e) => {
            load(e.target.value);
        });
    }

    function setError(msg){
        root.innerHTML = '<p class="text-red-600 font-semibold mt-4">' + msg + '</p>';
    }

    function escapeHtml(str){
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function nl2br(s){
        return escapeHtml(s).replace(/\r?\n/g, '<br>');
    }

    async function fetchViaApi(dateStr){
        let url = API_BASE;
        if(dateStr) {
            // Convert YYYY-MM-DD to DD-MM-YYYY
            const [y, m, d] = dateStr.split('-');
            url += `${d}-${m}-${y}`;
        }
        
        console.log('Fetching:', url);
        const res = await fetch(url);
        if(!res.ok) throw new Error('API liturgia respondeu com status ' + res.status);
        const data = await res.json();
        if(!data) throw new Error('API retornou vazio');
        return data;
    }

    function renderApi(data){
        root.innerHTML = '';
        const card = document.createElement('div');
        card.className = 'liturgy-card animate-fadeIn';

        const title = document.createElement('h3');
        title.textContent = (data.liturgia || 'Liturgia do dia') + (data.data ? ' — ' + data.data : '');
        card.appendChild(title);

        if(data.cor){
            const cor = document.createElement('p');
            cor.className = 'liturgy-color';
            cor.style.color = '#666';
            cor.style.fontStyle = 'italic';
            cor.style.marginBottom = '1rem';
            cor.textContent = 'Cor litúrgica: ' + data.cor;
            card.appendChild(cor);
        }

        if(data.leituras){
            const lsec = document.createElement('section');
            lsec.className = 'liturgy-leituras';
            
            const renderLeia = (arr, titlePrefix)=>{
                if(!arr || !arr.length) return;
                arr.forEach(function(item){
                    const h = document.createElement('h4');
                    h.textContent = (item.titulo ? item.titulo : (titlePrefix || 'Leitura')) + (item.referencia ? ' — ' + item.referencia : '');
                    lsec.appendChild(h);
                    
                    const p = document.createElement('p'); 
                    p.innerHTML = nl2br(item.texto || item.resumo || ''); 
                    lsec.appendChild(p);
                });
            };

            renderLeia(data.leituras.primeiraLeitura, 'Primeira Leitura');
            renderLeia(data.leituras.salmo, 'Salmo');
            renderLeia(data.leituras.segundaLeitura, 'Segunda Leitura');
            renderLeia(data.leituras.evangelho, 'Evangelho');

            card.appendChild(lsec);
        }

        const source = document.createElement('p');
        source.className = 'liturgy-source';
        source.innerHTML = 'Fonte: <a href="https://liturgia.up.railway.app" target="_blank">API Liturgia Diária</a>';
        card.appendChild(source);

        root.appendChild(card);
    }

    async function load(dateStr){
        root.innerHTML = '<p style="text-align:center; padding: 2rem;">Carregando liturgia...</p>';
        try {
            const apiData = await fetchViaApi(dateStr);
            renderApi(apiData);
        } catch (err) {
            console.error(err);
            setError('Não foi possível carregar a Liturgia do Dia. Tente novamente mais tarde.');
        }
    }

    // Initial load
    load();
})();
