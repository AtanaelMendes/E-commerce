<?php

// limpa os logs novos e exclui os antigos
function clearLog(): array
{

    if (retornaModo() != "dev") {
        return [];
    }

    $arrayTextLog = [];
    $arrArquivos = pegarOsArquivos();

    foreach ($arrArquivos as $arquivos) {
        foreach ($arquivos as $caminho => $arquivo) {

            if (file_exists($caminho . $arquivo) && !empty(pathinfo($arquivo, PATHINFO_EXTENSION))) {
                if (!empty($arquivo)) {

                    $somenteLimpar = encontrarAgulhaNoPalheiro($arquivo, ["db", "log", "xdebug", "txt"]);
                    $naoFazerNada = encontrarAgulhaNoPalheiro($arquivo, ["README."]);
                    $criadoEm = date("Ymd", filectime($caminho . $arquivo));

                    if (($somenteLimpar && !$naoFazerNada) && $criadoEm == date("Ymd")) {
                        $arrayTextLog[] =  ["Limpou" => $caminho . $arquivo];
                        @fopen($caminho . $arquivo, "w");
                    } else if (!$naoFazerNada) {
                        $seDeletou = @unlink($caminho . $arquivo);

                        if ($seDeletou) {
                            $arrayTextLog[] = ["Apagou" => $caminho . $arquivo];
                        }
                    }
                } //end if
            } //end if

        } //end foreach
    } //end foreach
    return $arrayTextLog;
};

// retorna a data da ultima alteração do arquivo
function fofoqueiro(string $arquivo): array
{
    return ["datadoarquivo" => date("Ymd h:i:s", fileatime($arquivo))];
}

// pega os arquivos em array, separando caminho e nome do arquivo
function pegarOsArquivos(): array
{

    error_log(print_r(basename(__FILE__)." DEBUGGER linha-> ".__LINE__, true));
    error_log(print_r(__DIR__, true));
    $arrArquivos = [];
    $pastas = varrerConteudoNasPastas();

    foreach ($pastas as $pastaCaminho => $arquivos) {
        foreach ($arquivos as $arquivo) {

            if (strlen($arquivo) > 3 && !empty(pathinfo($arquivo, PATHINFO_EXTENSION))) {
                $arrArquivos[] = [$pastaCaminho => $arquivo];
            }
        }
    }
    return $arrArquivos;
}

// faz a leitura das pastas e retorna a lista de arquivos
function varrerConteudoNasPastas(): array
{
    $pastaDeLogs = [
        "../",
    ];

    $pastas = [];

    foreach ($pastaDeLogs as $diretorio) {
        if (is_dir($diretorio)) {
            $pastas[$diretorio] = scandir($diretorio);
        }
    }

    return $pastas;
}

// array key exists
function encontrarAgulhaNoPalheiro(string $palheiro, array $agulhas): int
{
    $agulhasNoPalheiro = 0;

    foreach ($agulhas as $agulha) {
        if (strpos($palheiro, $agulha) !== false) {
            $agulhasNoPalheiro++;
        }
    }

    return $agulhasNoPalheiro > 0;
}

// retorna o conteudo do log por completo ou limitado pelas ultimas linhas
function pegaConteudoLog(string $logPath, int $limiteLinha)
{

    if (file_exists($logPath)) {
        if (!empty($limiteLinha) && !empty($logPath)) {
            $arquivo = @file($logPath);

            if ($limiteLinha > count($arquivo)) {
                return @file_get_contents($logPath);
            }
            return implode("", array_slice($arquivo, -$limiteLinha, count($arquivo)));
        }

        if (!empty($logPath)) {
            return @file_get_contents($logPath);
        }
    }

    return null;
}

function deletarArquivo(string $arquivo)
{
    if (retornaModo() != "dev" || !file_exists($arquivo)) {
        return [];
    }
    return unlink($arquivo);
}

function limparArquivo(string $arquivo)
{
    if (retornaModo() != "dev" || !file_exists($arquivo)) {
        return [];
    }
    return fopen($arquivo, "w");
}

function retornaModo(): string
{
    return "dev";
}

// deletar arquivo especifico
if (!empty($_REQUEST["arquivoDelete"]) && !empty($_REQUEST["delete"])) {
    deletarArquivo($_REQUEST["arquivoDelete"]);
    echo json_encode(["Apagou" => true]);
}

// pegar conteudo do log
if (!empty($_REQUEST["logpath"])) {
    echo pegaConteudoLog($_REQUEST["logpath"], $_REQUEST["limitelinhas"]);
}

// limpar arquivo especifico
if (!empty($_REQUEST["arquivo"]) && !empty($_REQUEST["limpar"])) {
    limparArquivo($_REQUEST["arquivo"]);
    echo json_encode(["Limpou" => true]);
}

//limpa os arquivos atuais e exclui os antigos
if (!empty($_REQUEST["clearall"])) {
    echo json_encode(clearLog());
}

// lista de arquivos para criar a tree
if (!empty($_REQUEST["loglist"])) {
    echo json_encode(varrerConteudoNasPastas());
}

//tail -f monitar arquivo
if (!empty($_REQUEST["monitor"]) && !empty($_REQUEST["arquivo"])) {
    echo json_encode(fofoqueiro($_REQUEST["arquivo"]));
}

// se quiser bloquear a limpeza e exclusão do log
if (!empty($_REQUEST["pegamodo"])) {
    echo json_encode(["modo" => retornaModo()]);
}
