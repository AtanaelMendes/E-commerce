// Get the modal
var modal = document.getElementById("myModal");

var monitores = {};

var ultimaModificacao = [];

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

var arquivoEmVisualizacao = "";

const BASEPATH = "/views/clearlog/engine/";

// When the user clicks the button, open the modal
btn.onclick = function () {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function () {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function (event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// ao carregar a tela
window.addEventListener("load", function (event) {
    pegaListaDeLog();
    carregaConfiguracao();
});

$("#menu-toggle").click(function (e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});

function verificaPermissao() {
    if (Notification.permission !== 'granted') {
        Notification.requestPermission();
        return false;
    }
    return true;
}

function executarLimpeza(arquivo) {
    var response = null;

    avisoBloqueioModo();

    if (arquivo) {
        $.ajax({
            url: "/views/clearlog/engine/engine.php",
            type: 'post',
            data: {
                arquivo: arquivo,
                limpar: true,
            }
        }).done(function (resp) {
            response = JSON.parse(resp);
            verConteudoDoLog(arquivo, true);
            adicionaAtividadeLimpeza(arquivo, response);
        });
    } else {
        $.ajax({
            url: "/views/clearlog/engine/engine.php",
            type: 'post',
            data: {
                clearall: true,
            }
        }).done(function (resp) {
            response = JSON.parse(resp);
            response.forEach(function (arquivo) {
                adicionaAtividadeLimpeza(arquivo);
            });
            $('#displaylog').html("");
        });

    }
}

function adicionaAtividadeLimpeza(arquivo, acao) {
    if ((arquivo && arquivo.Limpou) || (acao && acao.Limpou)) {
        $('#conteudo').prepend(
            $('<p><li class=\'success-tag col-xs-2\'>Limpou</li><div class=\'text-success\'>' + (arquivo.Limpou || arquivo) + '</div></p>').hide().fadeIn(1000)
        );
    }
    if ((acao && acao.Apagou) || (arquivo && arquivo.Apagou)) {
        $('#conteudo').prepend(
            $('<p><li class=\'col-xs-2 danger-tag\'>Apagou</li><div class=\'text-success\'>' + (arquivo.Apagou || arquivo) + '</div></p>').hide().fadeIn(1000)
        );
    }
    $('#alertas').html(
        $("<div style='height: 25px; !important' class='alert alert-success text-center' role='alert'>Limpeza Executada</div>").show().fadeOut(3000)
    );
    pegaListaDeLog();
}

function chamaMonitor(caminho, nomeDoArquivo) {
    var datadoarquivo = null;

    $.ajax({
        url: "/views/clearlog/engine/engine.php",
        type: 'post',
        data: {
            monitor: true,
            arquivo: caminho + nomeDoArquivo
        },
        async: false,
        success: (resp) => {
            var response = JSON.parse(resp);
            datadoarquivo = response;
        }
    })
    return datadoarquivo;
}

function avisarOProgramador(nomeDoArquivo, localDoArquivo, fofoca) {
    fofoca = chamaMonitor(localDoArquivo, nomeDoArquivo);
    var nomeModificado = limpaNomeArquivo(nomeDoArquivo);

    eval(
        `if (this.ultimaModificacao && this.ultimaModificacao.${nomeModificado} && fofoca.datadoarquivo != this.ultimaModificacao.${nomeModificado}) {
            somDeAviso();
            meAvisa();
            adicionaAtividadeMonitor("${nomeDoArquivo}");
        }`
    );



    this.ultimaModificacao[nomeModificado] = fofoca.datadoarquivo;
}

function adicionaAtividadeMonitor(arquivo) {
    const data = new Date();
    const horas = data.getHours();
    const minutos = data.getMinutes();
    const segundos = data.getSeconds();
    const hhmmmss = [horas, minutos, segundos].join(':');

    $('#conteudo').prepend(
        $(`<p><li class='warning-tag col-xs-2'>Deu LOG ${data.toLocaleTimeString()} </li><div class='text-success'>${arquivo}</div></p>`).hide().fadeIn(1000)
    );
}

function somDeAviso() {
    if (document.getElementById("som-aviso").checked) {
        var mp3Source = '<source src="/views/clearlog/engine/aviso.mp3" type="audio/mpeg">';
        var embedSource = '<embed hidden="true" autostart="true" loop="false" src="/views/clearlog/engine/aviso.mp3">';
        document.getElementById("sound").innerHTML = '<audio autoplay="autoplay">' + mp3Source + embedSource + '</audio>';
    }
}

function meAvisa() {
    var notification = new Notification('Deu Log', {
        icon: '/views/clearlog/engine/bug.jpg',
        body: 'Deu um negócio aqui!',
    });
    notification.onclick = () => { window.focus(); }
}

function iniciarFuxiqueiro(nomeDoArquivo, localDoArquivo) {
    if (verificaPermissao()) {
        var nomeModificado = limpaNomeArquivo(nomeDoArquivo);
        var tempoMonitor = document.getElementById("tempomonitor").value;

        eval(
            `
            if (!this.monitores.${nomeModificado}) {
                this.monitores.${nomeModificado} = setInterval(()=>avisarOProgramador('${nomeDoArquivo}', '${localDoArquivo}'), ${tempoMonitor});
                $("#${nomeModificado}-infinite").addClass("monitor-infinite");
                $("#${nomeModificado}").addClass("monitor");
            } else {
                clearInterval(this.monitores.${nomeModificado});
                delete this.monitores.${nomeModificado};
                $("#${nomeModificado}-infinite").removeClass("monitor-infinite");
                $("#${nomeModificado}").removeClass("monitor");
            }`
        );
    } else {
        alert('A notifiação está bloqueada no seu navegador, habilite para utilizar o monitor em tempo real.');
    }
}

function pegaListaDeLog(isReloadBtn) {
    $.ajax({
        url: "/views/clearlog/engine/engine.php",
        type: 'post',
        data: {
            loglist: true,
        },
        success: (resp) => {
            var response = JSON.parse(resp);
            var treehtml = "";
            var arquivoMaisCaminho = "";

            for (pasta in response) {
                treehtml += `<ul class='list-unstyled'>
                        <li style='color: grey !important;' class='h5'>${pasta.substring(8, 50)}</li>`;

                for (arquivo in response[pasta]) {

                    if (response[pasta][arquivo].length > 5 && response[pasta][arquivo].match(/(txt|log|db|xdebug|xml)/g) && !(response[pasta][arquivo].match(/(README|.gz)/g))) {
                        arquivoMaisCaminho = pasta + response[pasta][arquivo];
                        var nomeDoArquivo = response[pasta][arquivo];
                        var nomeModificado = limpaNomeArquivo(nomeDoArquivo);

                        debugger;

                        treehtml +=
                            `<div class='item-tree ${this.arquivoEmVisualizacao == arquivoMaisCaminho ? "active-item" : ""}'>
                                    <div id="${nomeModificado}-infinite" class="${(estaMonitorando(nomeModificado) ? "monitor-infinite" : "")}">
                                        <div id="${nomeModificado}" class="${(estaMonitorando(nomeModificado) ? "monitor" : "")}">
                                            <a onclick='verConteudoDoLog("${arquivoMaisCaminho}")' data-toggle='tooltip' data-placement='right' title='${nomeDoArquivo}'>
                                                <h6>&nbsp&nbsp${nomeDoArquivo.split(".")[0]}</h6>
                                            </a>
                                        </div>
                                    </div>
                                    <div class='form-check' style='padding-left:10px;'>
                                        <button id='${nomeDoArquivo}' data-toggle='tooltip' data-placement='right' title='Monitorar em tempo real' class='btn-link mini-btn' type='button' onclick="iniciarFuxiqueiro('${nomeDoArquivo}', '${pasta}')">
                                            <img style='height: 14px; width:14px; filter: invert(100%);' src='/views/clearlog/engine/olho.png'/>
                                        </button>
                                    </div>
                                    <div class='form-check' style='padding-left:10px;'>
                                        <button id='${nomeDoArquivo}' data-toggle='tooltip' data-placement='right' title='Excluir' class='btn-link mini-btn' type='button' onclick="excluirLog('${arquivoMaisCaminho}')">
                                            <img style='height: 14px; width:14px;' src='/views/clearlog/engine/delete.svg'/>
                                        </button>
                                    </div>
                                    <div class='form-check' style='padding-left:10px;'>
                                        <button id='${nomeDoArquivo}' data-toggle='tooltip' data-placement='right' title='Limpar' class='btn-link mini-btn' type='button' onclick="executarLimpeza('${arquivoMaisCaminho}')">
                                            <img style='height: 14px; width:14px; filter: invert(100%);' src='/views/clearlog/engine/vassora.png'/>
                                        </button>
                                    </div>
                                    <div class='form-check' style='padding-left:10px;'>
                                        <button id='${nomeDoArquivo}' data-toggle='tooltip' data-placement='right' title='Atualizar' class='btn-link mini-btn' type='button' onclick="verConteudoDoLog('${arquivoMaisCaminho}')">
                                            <img style='height: 14px; width:14px; filter: invert(100%);' src='/views/clearlog/engine/reload.png'/>
                                        </button>
                                    </div>
                                </div>`;
                    }
                }
                treehtml += "</ul>";
            }

            if (isReloadBtn) {
                $("#arvore-de-log").html($(treehtml)).hide().fadeIn(1000);
            } else {
                $("#arvore-de-log").html($(treehtml));
            }
        }
    });
}

function marcaLogAtivo(itemAtivo) {
    $(".item-tree").removeClass("active");
    $(itemAtivo).addClass("active");
}

function excluirLog(arquivo) {

    avisoBloqueioModo();

    $.ajax({
        url: "/views/clearlog/engine/engine.php",
        type: 'post',
        data: {
            arquivoDelete: arquivo,
            delete: true
        }
    }).done(function (resp) {
        var response = JSON.parse(resp);
        adicionaAtividadeLimpeza(arquivo, response);
        pegaListaDeLog();
    });
}

function verConteudoDoLog(logPath, isTailF) {
    this.arquivoEmVisualizacao = logPath;
    $.ajax({
        url: "/views/clearlog/engine/engine.php",
        type: 'post',
        data: {
            logpath: logPath,
            limitelinhas: document.getElementById("linhas").value,
        }
    }).done(function (resp) {
        var logContent = null;

        if (resp.length > 3) {
            logContent = formatContent(resp);
            $('#displaylog').html(logContent).hide().fadeIn(500);
        } else {
            $('#displaylog').html("");
            if (!isTailF) {
                $('#alertas').html(
                    $("<div style='height: 25px; !important' class='alert alert-danger text-center' role='alert'>Sem conteúdo</div>").show().fadeOut(3000)
                );
            }
        }

        pegaListaDeLog();

        return logContent;
    });
}

function formatContent(logContent) {

    if (!document.getElementById("marcatextotoogle").checked) {
        return logContent.replace(/(\n)/g, '<br />');
    }

    var marcaTextoa = document.getElementById("marcatextoa").value;
    var cormarcaTextoa = document.getElementById("cormarcatextoa").value;
    var marcaTextob = document.getElementById("marcatextob").value;
    var cormarcaTextob = document.getElementById("cormarcatextob").value;
    var marcaTextoc = document.getElementById("marcatextoc").value;
    var cormarcaTextoc = document.getElementById("cormarcatextoc").value;
    var marcaTextod = document.getElementById("marcatextod").value;
    var cormarcaTextod = document.getElementById("cormarcatextod").value;
    var defaultSuccess = document.getElementById("marcatextoe").value;
    var defaultDefault = document.getElementById("marcatextof").value;
    var defaultInfo = document.getElementById("marcatextog").value;
    var defaultWarning = document.getElementById("marcatextoh").value;
    var defaultDanger = document.getElementById("marcatextoi").value;

    logContent = logContent.replace(/(\d)/g, "<span style='color: blue'>$&</span>");
    logContent = logContent.replace(/(PHP\s)/gi, "<span class='php-tag'>$&</span>");

    if (marcaTextoa) {
        logContent = logContent.replace(getRegex(marcaTextoa), `<span class="tag" style="background-color:${cormarcaTextoa};">$&</span>`);
    }
    if (marcaTextob) {
        logContent = logContent.replace(getRegex(marcaTextob), `<span class="tag" style="background-color:${cormarcaTextob};">$&</span>`);
    }
    if (marcaTextoc) {
        logContent = logContent.replace(getRegex(marcaTextoc), `<span class="tag" style="background-color:${cormarcaTextoc};">$&</span>`);
    }
    if (marcaTextod) {
        logContent = logContent.replace(getRegex(marcaTextod), `<span class="tag" style="background-color:${cormarcaTextod};">$&</span>`);
    }
    if (defaultSuccess) {
        logContent = logContent.replace(getRegex(defaultSuccess), "<span class='success-tag'>$&</span>");
    }
    if (defaultDefault) {
        logContent = logContent.replace(getRegex(defaultDefault), "<span class='default-tag'>$&</span>");
    }
    if (defaultInfo) {
        logContent = logContent.replace(getRegex(defaultInfo), "<span class='info-tag'>$&</span>");
    }
    if (defaultWarning) {
        logContent = logContent.replace(getRegex(defaultWarning), "<span class='warning-tag'>$&</span>");
    }
    if (defaultDanger) {
        logContent = logContent.replace(getRegex(defaultDanger), "<span class='danger-tag'>$&</span>");
    }

    return logContent.replace(/(\n)/g, '<br />');
}

function getRegex(palavras) {
    var regexx = palavras.split(",").map((pala) => {
        return "(" + pala + ")"
    });
    return new RegExp(regexx.join().replaceAll(",", "|"), "gi");
}

function limpaNomeArquivo(nome) {
    return nome.replace(/(\.|\-)/g, "");
}

function irParaBaixo() {
    window.scroll(0, $('html, body').height());
}

function recarregaLog() {
    verConteudoDoLog(this.arquivoEmVisualizacao);
}

function carregaConfiguracao() {
    var configuracao = pegaConfiguracao();
    document.getElementById("linhas").value = configuracao.linhas;
    document.getElementById("tempomonitor").value = configuracao.tempomonitor;
    document.getElementById("som-aviso").checked = configuracao.somaviso;
    document.getElementById("marcatextotoogle").checked = configuracao.marcatextotoogle;
    document.getElementById("marcatextoa").value = configuracao.marcatextoa || "";
    document.getElementById("cormarcatextoa").value = configuracao.cormarcatextoa;
    document.getElementById("marcatextob").value = configuracao.marcatextob || "";
    document.getElementById("cormarcatextob").value = configuracao.cormarcatextob;
    document.getElementById("marcatextoc").value = configuracao.marcatextoc || "";
    document.getElementById("cormarcatextoc").value = configuracao.cormarcatextoc;
    document.getElementById("marcatextod").value = configuracao.marcatextod || "";
    document.getElementById("cormarcatextod").value = configuracao.cormarcatextod;
    document.getElementById("marcatextoe").value = configuracao.marcatextoe || "";
    document.getElementById("marcatextof").value = configuracao.marcatextof || "";
    document.getElementById("marcatextog").value = configuracao.marcatextog || "";
    document.getElementById("marcatextoh").value = configuracao.marcatextoh || "";
    document.getElementById("marcatextoi").value = configuracao.marcatextoi || "";

}

function atualizaConfiguracao() {
    var cacheado = JSON.parse(localStorage.getItem("configuracao"));
    cacheado.linhas = document.getElementById("linhas").value;
    cacheado.tempomonitor = document.getElementById("tempomonitor").value;
    cacheado.somaviso = document.getElementById("som-aviso").checked;
    cacheado.marcatextotoogle = document.getElementById("marcatextotoogle").checked;
    cacheado.marcatextoa = document.getElementById("marcatextoa").value;
    cacheado.cormarcatextoa = document.getElementById("cormarcatextoa").value;
    cacheado.marcatextob = document.getElementById("marcatextob").value;
    cacheado.cormarcatextob = document.getElementById("cormarcatextob").value;
    cacheado.marcatextoc = document.getElementById("marcatextoc").value;
    cacheado.cormarcatextoc = document.getElementById("cormarcatextoc").value;
    cacheado.marcatextod = document.getElementById("marcatextod").value;
    cacheado.cormarcatextod = document.getElementById("cormarcatextod").value;
    cacheado.marcatextoe = document.getElementById("marcatextoe").value;
    cacheado.marcatextof = document.getElementById("marcatextof").value;
    cacheado.marcatextog = document.getElementById("marcatextog").value;
    cacheado.marcatextoh = document.getElementById("marcatextoh").value;
    cacheado.marcatextoi = document.getElementById("marcatextoi").value;
    localStorage.setItem("configuracao", JSON.stringify(cacheado));
    window.location.reload();
}

function pegaConfiguracao() {
    var cacheado = (localStorage.getItem("configuracao") != "undefined" ? JSON.parse(localStorage.getItem("configuracao")) : false);

    if (cacheado) {
        return cacheado
    }

    var configuracao = criaConfiguracao();
    localStorage.setItem("configuracao", JSON.stringify(configuracao));
    return configuracao;
}

function criaConfiguracao() {
    return {
        linhas: 2000,
        tempomonitor: 1000,
        somaviso: true,
        marcatextotoogle: true,
        marcatextoa: "",
        cormarcatextoa: document.getElementById("cormarcatextoa").value,
        marcatextob: "",
        cormarcatextob: document.getElementById("cormarcatextob").value,
        marcatextoc: "",
        cormarcatextoc: document.getElementById("cormarcatextoc").value,
        marcatextod: "",
        cormarcatextod: document.getElementById("cormarcatextod").value,
        marcatextoe: "success",
        marcatextof: "default",
        marcatextog: "info",
        marcatextoh: "warning",
        marcatextoi: "error",
    };
}

function isModoDev() {
    var response = null;
    $.ajax({
        url: "/views/clearlog/engine/engine.php",
        type: 'post',
        data: {
            pegamodo: true,
        },
        async: false,
        success: (resp) => {
            response = JSON.parse(resp);
        }
    })
    return (response.modo == "dev");
}

function validaTempo() {
    if (document.getElementById("tempomonitor").value < 1000) {
        document.getElementById("tempomonitor").value = 1000
    }
}

function estaMonitorando(nome) {
    for (var monitor in this.monitores) {
        if (monitor == nome) {
            return true;
        }
    }
    return false;
}

function avisoBloqueioModo() {
    if (!isModoDev()) {
        return alert("O modo do ambiente não permite manipular os logs");
    }
}