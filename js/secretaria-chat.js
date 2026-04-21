const secretariaRoot = document.querySelector('[data-secretaria-chat]');

if (secretariaRoot) {
    const chatWindow = document.getElementById('chat-window');
    const optionsContainer = document.getElementById('chat-options');
    const panels = Array.from(secretariaRoot.querySelectorAll('[data-panel]'));
    const selectedAssunto = secretariaRoot.dataset.selectedAssunto || '';
    const optionLabels = {
        item_religioso: 'Comprar item religioso',
        batismo: 'Marcar batismo',
        casamento: 'Marcar casamento',
    };
    let batismoReadyAnnounced = false;

    const appendBubble = (type, text) => {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${type}`;
        bubble.innerHTML = `<p>${text}</p>`;
        chatWindow.appendChild(bubble);
        chatWindow.scrollTop = chatWindow.scrollHeight;
    };

    const showPanel = (assunto) => {
        panels.forEach((panel) => {
            panel.classList.toggle('is-visible', panel.dataset.panel === assunto);
        });
    };

    const answerFor = (assunto) => {
        if (assunto === 'batismo') {
            return 'Vamos conferir os documentos do batismo. Quando os itens obrigatorios estiverem completos, eu libero o pedido de agendamento com a secretaria.';
        }

        if (assunto === 'casamento') {
            return 'Certo. Deixe o nome do casal e uma previsao de data. A secretaria organiza o primeiro atendimento.';
        }

        return 'Perfeito. Escreva qual item religioso voce deseja e a secretaria responde com disponibilidade e orientacao de retirada.';
    };

    const chooseAssunto = (assunto, label) => {
        if (!optionLabels[assunto]) {
            return;
        }

        if (optionsContainer) {
            optionsContainer.querySelectorAll('[data-assunto]').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.assunto === assunto);
            });
        }

        showPanel(assunto);
        appendBubble('user', label);
        appendBubble('bot', answerFor(assunto));
    };

    if (optionsContainer) {
        optionsContainer.querySelectorAll('[data-assunto]').forEach((button) => {
            button.addEventListener('click', () => {
                chooseAssunto(button.dataset.assunto, button.textContent.trim());
            });
        });
    }

    const checklistInputs = Array.from(document.querySelectorAll('[data-hidden-target]'));
    const requiredInputs = checklistInputs.filter((input) => input.hasAttribute('data-batismo-required'));
    const batismoReady = document.getElementById('batismo-ready');

    const syncChecklist = () => {
        checklistInputs.forEach((input) => {
            const hiddenField = document.getElementById(input.dataset.hiddenTarget);
            const item = input.closest('.checklist-item');
            if (hiddenField) {
                hiddenField.value = input.checked ? '1' : '0';
            }
            if (item) {
                item.classList.toggle('checked', input.checked);
            }
        });

        const allRequiredChecked = requiredInputs.every((input) => input.checked);
        if (batismoReady) {
            batismoReady.classList.toggle('is-visible', allRequiredChecked);
        }

        if (allRequiredChecked && !batismoReadyAnnounced) {
            appendBubble('bot', 'Documentos obrigatorios confirmados. Agora voce pode pedir o agendamento com a secretaria.');
            batismoReadyAnnounced = true;
        }
    };

    checklistInputs.forEach((input) => {
        input.addEventListener('change', syncChecklist);
    });

    const agendarField = document.getElementById('batismo-deseja-agendar');
    document.querySelectorAll('[data-agendar-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.dataset.agendarChoice || '0';
            if (agendarField) {
                agendarField.value = value;
            }

            document.querySelectorAll('[data-agendar-choice]').forEach((item) => {
                item.classList.toggle('is-active', item === button);
            });
        });
    });

    if (selectedAssunto && optionLabels[selectedAssunto]) {
        showPanel(selectedAssunto);
        if (optionsContainer) {
            optionsContainer.querySelectorAll('[data-assunto]').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.assunto === selectedAssunto);
            });
        }
    }

    syncChecklist();
}