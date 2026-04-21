const mysteryByWeekDay = {
    0: {
        day: 'Domingo',
        title: 'Misterios Gloriosos',
        intro: 'Hoje e domingo: contemple os Misterios Gloriosos.',
        mysteries: [
            'A Ressurreicao de Jesus',
            'A Ascensao de Jesus ao Ceu',
            'A vinda do Espirito Santo',
            'A Assuncao de Maria',
            'A Coroacao de Maria no Ceu'
        ]
    },
    1: {
        day: 'Segunda-feira',
        title: 'Misterios Gozosos',
        intro: 'Hoje e segunda-feira: contemple os Misterios Gozosos.',
        mysteries: [
            'A Anunciacao do Anjo a Maria',
            'A Visitacao de Maria a Isabel',
            'O Nascimento de Jesus em Belem',
            'A Apresentacao do Menino Jesus no Templo',
            'A perda e o encontro do Menino Jesus no Templo'
        ]
    },
    2: {
        day: 'Terca-feira',
        title: 'Misterios Dolorosos',
        intro: 'Hoje e terca-feira: contemple os Misterios Dolorosos.',
        mysteries: [
            'A Agonia de Jesus no Horto',
            'A Flagelacao de Jesus',
            'A Coroacao de Espinhos',
            'Jesus carrega a Cruz para o Calvario',
            'A Crucificacao e morte de Jesus'
        ]
    },
    3: {
        day: 'Quarta-feira',
        title: 'Misterios Gloriosos',
        intro: 'Hoje e quarta-feira: contemple os Misterios Gloriosos.',
        mysteries: [
            'A Ressurreicao de Jesus',
            'A Ascensao de Jesus ao Ceu',
            'A vinda do Espirito Santo',
            'A Assuncao de Maria',
            'A Coroacao de Maria no Ceu'
        ]
    },
    4: {
        day: 'Quinta-feira',
        title: 'Misterios Luminosos',
        intro: 'Hoje e quinta-feira: contemple os Misterios Luminosos.',
        mysteries: [
            'O Batismo de Jesus no Jordao',
            'As Bodas de Cana',
            'O anuncio do Reino de Deus',
            'A Transfiguracao de Jesus',
            'A Instituicao da Eucaristia'
        ]
    },
    5: {
        day: 'Sexta-feira',
        title: 'Misterios Dolorosos',
        intro: 'Hoje e sexta-feira: contemple os Misterios Dolorosos.',
        mysteries: [
            'A Agonia de Jesus no Horto',
            'A Flagelacao de Jesus',
            'A Coroacao de Espinhos',
            'Jesus carrega a Cruz para o Calvario',
            'A Crucificacao e morte de Jesus'
        ]
    },
    6: {
        day: 'Sabado',
        title: 'Misterios Gozosos',
        intro: 'Hoje e sabado: contemple os Misterios Gozosos.',
        mysteries: [
            'A Anunciacao do Anjo a Maria',
            'A Visitacao de Maria a Isabel',
            'O Nascimento de Jesus em Belem',
            'A Apresentacao do Menino Jesus no Templo',
            'A perda e o encontro do Menino Jesus no Templo'
        ]
    }
};

(function renderRosaryOfTheDay() {
    const today = new Date();
    const rosary = mysteryByWeekDay[today.getDay()];

    const mysteryTitle = document.getElementById('mystery-title');
    const dayLabel = document.getElementById('day-label');
    const intro = document.getElementById('terco-day-intro');
    const list = document.getElementById('mysteries-list');

    if (!mysteryTitle || !dayLabel || !intro || !list || !rosary) {
        return;
    }

    mysteryTitle.textContent = rosary.title;
    dayLabel.textContent = `Dia da semana: ${rosary.day}`;
    intro.textContent = rosary.intro;

    list.innerHTML = rosary.mysteries
        .map((mystery, index) => `
            <article class="card">
                <h4>${index + 1}º misterio</h4>
                <p>${mystery}</p>
                <p style="margin-top: .5rem; color: #7a6741; font-size: .9rem;">Pai Nosso + 10 Ave-Marias + Gloria</p>
            </article>
        `)
        .join('');
})();
