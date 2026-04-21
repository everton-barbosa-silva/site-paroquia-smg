const questions = [
    { question: "Qual é o primeiro livro da Bíblia?", options: ["Êxodo", "Gênesis", "Levítico", "Salmos"], correct: 1 },
    { question: "Quem foi o primeiro Papa da Igreja Católica?", options: ["São Paulo", "São João", "São Pedro", "Santo André"], correct: 2 },
    { question: "Quantos conjuntos de mistérios tem o Santo Rosário?", options: ["3", "4", "5", "20"], correct: 1 },
    { question: "Quais são os mistérios instituídos pelo Papa João Paulo II?", options: ["Gozosos", "Dolorosos", "Gloriosos", "Luminosos"], correct: 3 },
    { question: "Qual é a cor litúrgica do Tempo do Advento?", options: ["Verde", "Branco", "Roxo", "Vermelho"], correct: 2 },
    { question: "Quem é a padroeira do Brasil?", options: ["Nossa Senhora de Fátima", "Nossa Senhora Aparecida", "Nossa Senhora de Lourdes", "Nossa Senhora do Carmo"], correct: 1 },
    { question: "O que celebramos no Natal?", options: ["A Ressurreição de Jesus", "A Morte de Jesus", "O Nascimento de Jesus", "A Ascensão de Jesus"], correct: 2 },
    { question: "Qual sacramento nos torna filhos de Deus?", options: ["Eucaristia", "Crisma", "Batismo", "Matrimônio"], correct: 2 },
    { question: "Quem recebeu os Dez Mandamentos?", options: ["Abraão", "Isaac", "Moisés", "Davi"], correct: 2 },
    { question: "Qual é a oração que Jesus nos ensinou?", options: ["Ave Maria", "Salve Rainha", "Pai Nosso", "Credo"], correct: 2 },
    { question: "Onde Jesus nasceu?", options: ["Nazaré", "Jerusalém", "Belém", "Cafarnaum"], correct: 2 },
    { question: "Quem traiu Jesus?", options: ["Pedro", "Judas Iscariotes", "Tomé", "Tiago"], correct: 1 },
    { question: "Qual é o dia da semana consagrado à Ressurreição?", options: ["Sábado", "Domingo", "Sexta-feira", "Segunda-feira"], correct: 1 },
    { question: "Quem é a mãe de João Batista?", options: ["Maria", "Isabel", "Ana", "Marta"], correct: 1 },
    { question: "Qual anjo anunciou a Maria que ela seria mãe de Jesus?", options: ["Miguel", "Rafael", "Gabriel", "Uriel"], correct: 2 },
    { question: "O que significa 'Eucaristia'?", options: ["Sacrifício", "Ação de Graças", "Comunhão", "Perdão"], correct: 1 },
    { question: "Quem negou Jesus três vezes?", options: ["Judas", "Pedro", "João", "Mateus"], correct: 1 },
    { question: "Qual é o tempo litúrgico de preparação para a Páscoa?", options: ["Advento", "Natal", "Quaresma", "Tempo Comum"], correct: 2 },
    { question: "Quantos dias durou o dilúvio?", options: ["7 dias", "30 dias", "40 dias e 40 noites", "100 dias"], correct: 2 },
    { question: "Quem foi engolido por um grande peixe?", options: ["Jonas", "Elias", "Daniel", "Noé"], correct: 0 },
    { question: "Qual é o último livro da Bíblia?", options: ["Apocalipse", "Atos dos Apóstolos", "Judas", "Hebreus"], correct: 0 },
    { question: "Quem batizou Jesus?", options: ["Pedro", "João Batista", "Tiago", "Paulo"], correct: 1 },
    { question: "Onde Jesus realizou seu primeiro milagre?", options: ["Nazaré", "Caná", "Betânia", "Jericó"], correct: 1 },
    { question: "Quem ajudou Jesus a carregar a cruz?", options: ["Simão de Cirene", "José de Arimateia", "Nicodemos", "Lázaro"], correct: 0 },
    { question: "Qual é o dom do Espírito Santo que nos dá coragem?", options: ["Sabedoria", "Fortaleza", "Ciência", "Piedade"], correct: 1 },
    { question: "Quem é o padroeiro dos trabalhadores?", options: ["São José", "São Pedro", "São Paulo", "Santo Antônio"], correct: 0 },
    { question: "Qual apóstolo duvidou da ressurreição?", options: ["Pedro", "João", "Tomé", "André"], correct: 2 },
    { question: "O que comemoramos em Pentecostes?", options: ["A Ascensão de Jesus", "A Vinda do Espírito Santo", "A Transfiguração", "O Batismo de Jesus"], correct: 1 },
    { question: "Quem escreveu a maior parte das epístolas do Novo Testamento?", options: ["Pedro", "João", "Paulo", "Tiago"], correct: 2 },
    { question: "Qual é o mandamento novo que Jesus nos deu?", options: ["Não matarás", "Amar a Deus sobre todas as coisas", "Amai-vos uns aos outros como eu vos amei", "Honrar pai e mãe"], correct: 2 }
];

// Shuffle function
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

// Shuffle questions on load
shuffleArray(questions);

let currentQuestion = 0;
let score = 0;
let scoreSaved = false;

const questionEl = document.getElementById('question');
const optionsEl = document.getElementById('options');
const nextBtn = document.getElementById('next-btn');
const quizBox = document.getElementById('question-container');
const resultBox = document.getElementById('result-container');
const scoreEl = document.getElementById('score');
const feedbackEl = document.getElementById('feedback-msg');
const ratingSection = document.getElementById('rating-section');
const performanceBadge = document.getElementById('performance-badge');
const saveForm = document.getElementById('save-score-form');
const playerNameInput = document.getElementById('player-name');
const saveStatus = document.getElementById('save-status');
const rankingContainer = document.getElementById('ranking-container');

function loadQuestion() {
    const q = questions[currentQuestion];
    questionEl.textContent = `${currentQuestion + 1}. ${q.question}`;
    optionsEl.innerHTML = '';

    q.options.forEach((opt, index) => {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.textContent = opt;
        btn.onclick = () => selectOption(index, btn);
        optionsEl.appendChild(btn);
    });

    nextBtn.classList.add('hidden');
}

function selectOption(index, btn) {
    // Disable all buttons
    const buttons = optionsEl.querySelectorAll('.option-btn');
    buttons.forEach(b => b.disabled = true);

    const q = questions[currentQuestion];
    if (index === q.correct) {
        btn.classList.add('correct');
        score++;
    } else {
        btn.classList.add('wrong');
        // Highlight correct answer
        buttons[q.correct].classList.add('correct');
    }

    nextBtn.classList.remove('hidden');
}

nextBtn.onclick = () => {
    currentQuestion++;
    if (currentQuestion < questions.length) {
        loadQuestion();
    } else {
        showResults();
    }
};

function showResults() {
    quizBox.classList.add('hidden');
    nextBtn.classList.add('hidden');
    resultBox.classList.remove('hidden');

    scoreEl.textContent = `${score}/${questions.length}`;

    const percentage = (score / questions.length) * 100;
    if (percentage === 100) {
        feedbackEl.textContent = "Perfeito! Você é um especialista na fé!";
        performanceBadge.textContent = "Desempenho: Excelente";
    } else if (percentage >= 70) {
        feedbackEl.textContent = "Muito bem! Ótimos conhecimentos.";
        performanceBadge.textContent = "Desempenho: Muito bom";
    } else if (percentage >= 50) {
        feedbackEl.textContent = "Bom trabalho! Continue estudando.";
        performanceBadge.textContent = "Desempenho: Bom";
    } else {
        feedbackEl.textContent = "Que tal estudar um pouco mais?";
        performanceBadge.textContent = "Desempenho: Continue firme";
    }

    loadRanking();
}

function rateQuiz(stars) {
    alert(`Obrigado por avaliar com ${stars} estrelas!`);
    document.getElementById('rating-display').innerHTML = '<p style="color: var(--gold); font-weight: bold;">Obrigado pelo seu voto!</p>';
}

function shareResult() {
    const text = `Fiz o Quiz Católico da Paróquia Santa Maria Goretti e acertei ${score} de ${questions.length}! Tente você também!`;
    const url = window.location.href;

    if (navigator.share) {
        navigator.share({
            title: 'Quiz Católico',
            text: text,
            url: url
        }).catch((error) => console.log('Erro ao compartilhar', error));
    } else {
        // Fallback for desktop or unsupported browsers
        navigator.clipboard.writeText(`${text} ${url}`).then(() => {
            alert("Resultado copiado! Cole no seu Instagram ou WhatsApp.");
            // Try to open Instagram if on mobile, otherwise just alert
            if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                window.location.href = "instagram://story-camera";
            }
        }).catch(err => {
            console.error('Erro ao copiar: ', err);
            alert("Não foi possível copiar automaticamente. Tire um print e compartilhe!");
        });
    }
}

async function saveScore(name) {
    const res = await fetch('quiz-api.php?action=save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            nome: name,
            pontuacao: score,
            total_perguntas: questions.length
        })
    });

    const data = await res.json();
    if (!res.ok || !data.ok) {
        throw new Error(data.error || 'Falha ao salvar pontuação.');
    }

    return data;
}

function formatDateBR(dateText) {
    const d = new Date(dateText);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

async function loadRanking() {
    try {
        const res = await fetch('quiz-api.php?action=ranking');
        const data = await res.json();

        if (!res.ok || !data.ok || !Array.isArray(data.ranking)) {
            throw new Error('Erro ao carregar ranking');
        }

        if (data.ranking.length === 0) {
            rankingContainer.innerHTML = '<p>Ainda não há pontuações registradas. Seja o primeiro!</p>';
            return;
        }

        const rows = data.ranking.map((item, index) => {
            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.nome}</td>
                    <td>${item.pontuacao}/${item.total_perguntas}</td>
                    <td>${Number(item.percentual).toFixed(2)}%</td>
                    <td>${formatDateBR(item.created_at)}</td>
                </tr>
            `;
        }).join('');

        rankingContainer.innerHTML = `
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Pontos</th>
                        <th>Aproveitamento</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    } catch (err) {
        rankingContainer.innerHTML = '<p>Não foi possível carregar o ranking agora.</p>';
    }
}

if (saveForm) {
    saveForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (scoreSaved) {
            saveStatus.textContent = 'Sua pontuação já foi salva nesta rodada.';
            return;
        }

        const name = (playerNameInput.value || '').trim();
        if (name.length < 2) {
            saveStatus.textContent = 'Informe um nome válido com pelo menos 2 letras.';
            return;
        }

        try {
            saveStatus.textContent = 'Salvando sua pontuação...';
            await saveScore(name);
            scoreSaved = true;
            saveStatus.textContent = 'Pontuação salva com sucesso no ranking!';
            loadRanking();
        } catch (err) {
            saveStatus.textContent = 'Não foi possível salvar agora. Tente novamente.';
        }
    });
}

// Start
loadQuestion();
