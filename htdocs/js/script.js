const botaoMenu = document.querySelector('.btnmenu');
const menu = document.querySelector('.menu-left');

if (botaoMenu && menu) {
    botaoMenu.addEventListener('click', function () {
        menu.classList.toggle('aberto');
    });

    document.addEventListener('click', function (event) {
        const clicouNoMenu = menu.contains(event.target);
        const clicouNoBotao = botaoMenu.contains(event.target);

        if (!clicouNoMenu && !clicouNoBotao && window.innerWidth <= 980) {
            menu.classList.remove('aberto');
        }
    });
}

const botoesConfirmar = document.querySelectorAll('[data-confirmar]');

botoesConfirmar.forEach(function (botao) {
    botao.addEventListener('click', function (event) {
        const mensagem = botao.getAttribute('data-confirmar') || 'Deseja continuar?';

        if (!confirm(mensagem)) {
            event.preventDefault();
        }
    });
});

function somenteNumeros(valor, limite) {
    return valor.replace(/\D/g, '').substring(0, limite);
}

function aplicarMascaraCpf(campo) {
    let valor = somenteNumeros(campo.value, 11);

    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

    campo.value = valor;
}

function aplicarMascaraCnpj(campo) {
    let valor = somenteNumeros(campo.value, 14);

    valor = valor.replace(/^(\d{2})(\d)/, '$1.$2');
    valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    valor = valor.replace(/\.(\d{3})(\d)/, '.$1/$2');
    valor = valor.replace(/(\d{4})(\d)/, '$1-$2');

    campo.value = valor;
}

function aplicarMascaraTelefone(campo) {
    let valor = somenteNumeros(campo.value, 11);

    if (valor.length <= 10) {
        valor = valor.replace(/^(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        valor = valor.replace(/^(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    }

    campo.value = valor;
}

function aplicarMascaras() {
    document.querySelectorAll('[data-mask="cpf"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            aplicarMascaraCpf(campo);
        });
    });

    document.querySelectorAll('[data-mask="cnpj"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            aplicarMascaraCnpj(campo);
        });
    });

    document.querySelectorAll('[data-mask="telefone"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            aplicarMascaraTelefone(campo);
        });
    });

    document.querySelectorAll('[data-mask="cnh"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = somenteNumeros(campo.value, 11);
        });
    });

    document.querySelectorAll('[data-mask="renavam"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = somenteNumeros(campo.value, 11);
        });
    });

    document.querySelectorAll('[data-mask="ano"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = somenteNumeros(campo.value, 4);
        });
    });

    document.querySelectorAll('[data-mask="placa"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .substring(0, 7);
        });
    });

    document.querySelectorAll('[data-mask="chassi"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .substring(0, 17);
        });
    });

    document.querySelectorAll('[data-uppercase]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value.toUpperCase();
        });
    });
}

aplicarMascaras();

const botoesSenha = document.querySelectorAll('[data-toggle-password]');

botoesSenha.forEach(function (botao) {
    botao.addEventListener('click', function () {
        const idCampo = botao.getAttribute('data-toggle-password');
        const campo = document.getElementById(idCampo);

        if (!campo) {
            return;
        }

        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';

        const icone = botao.querySelector('i');

        if (icone) {
            icone.className = mostrar ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    });
});

const entradasImagem = document.querySelectorAll('[data-image-preview]');

entradasImagem.forEach(function (entrada) {
    entrada.addEventListener('change', function () {
        const seletorPreview = entrada.getAttribute('data-image-preview');
        const preview = document.querySelector(seletorPreview);
        const arquivo = entrada.files && entrada.files[0];

        if (!preview || !arquivo) {
            return;
        }

        if (!arquivo.type.startsWith('image/')) {
            return;
        }

        const leitor = new FileReader();

        leitor.onload = function (event) {
            preview.innerHTML = '<img src="' + event.target.result + '" alt="Pré-visualização">';
        };

        leitor.readAsDataURL(arquivo);
    });
});

const dataPartida = document.getElementById('data_partida');
const previsaoRetorno = document.getElementById('previsao_retorno');

if (dataPartida && previsaoRetorno) {
    function atualizarRetornoMinimo() {
        if (dataPartida.value !== '') {
            previsaoRetorno.min = dataPartida.value;
        }
    }

    dataPartida.addEventListener('change', atualizarRetornoMinimo);
    atualizarRetornoMinimo();
}
