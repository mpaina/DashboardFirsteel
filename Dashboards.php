<?php

date_default_timezone_set('America/Sao_Paulo');

$pagina = $_GET['pagina'] ?? 'comercial';

$grafico = $_GET['grafico'] ?? 'origem';

$filtroVendedor = $_GET['vendedor'] ?? '';

/*
|--------------------------------------------------------------------------
| ARQUIVOS
|--------------------------------------------------------------------------
*/

if (!file_exists("cache_vendas.json")) {
    die("cache_vendas.json não encontrado.");
}

if (!file_exists("Origem_Vendas.json")) {
    die("Origem_Vendas.json não encontrado.");
}

$vendas = json_decode(
    file_get_contents("cache_vendas.json"),
    true
);

$origensJson = json_decode(
    file_get_contents("Origem_Vendas.json"),
    true
);

/*
|--------------------------------------------------------------------------
| META
|--------------------------------------------------------------------------
*/

$metaMensal = 3500000;

/*
|--------------------------------------------------------------------------
| ARRAYS
|--------------------------------------------------------------------------
*/
$pedidosAbertos = [];

$origemLabels = [];
$origemValores = [];

$faturadoMes = [];

$vistaPrazo = [
    'vista' => 0,
    'prazo' => 0
];

$vendasVendedor = [];

$vendasMes = [];

$vistaVendedor = [];

$realizadoMesAtual = 0;

$totalPedidosRealizado = 0;
$totalValorRealizado = 0;

/*
|--------------------------------------------------------------------------
| ORIGEM DAS VENDAS
|--------------------------------------------------------------------------
*/

if(isset($origensJson['origens'])){

    foreach($origensJson['origens'] as $origem){

        $origemLabels[] = $origem['origem'];

        $origemValores[] = floatval(
            $origem['valor']
        );
    }
}

/*
|--------------------------------------------------------------------------
| DATA ATUAL
|--------------------------------------------------------------------------
*/

$mesAtual = date('m');
$anoAtual = date('Y');

$totalVendidoMesAtual = 0;
$pedidosMesAtual = 0;
$clientesMesAtual = [];

$vendasVendedorMesAtual = [];

$vistaVendedorMesAtual = [];

$vistaPrazoMesAtual = [
    'vista' => 0,
    'prazo' => 0
];

$vendedoresFiltro = [];

/*
|--------------------------------------------------------------------------
| PROCESSA VENDAS
|--------------------------------------------------------------------------
*/

foreach($vendas as $pedido => $venda){

    $situacao = strtoupper(
        trim($venda['situacao'] ?? '')
    );

    if(
        strpos($situacao,'CANCELADO') !== false
        || strpos($situacao,'DEVOLVIDO') !== false
        || strpos($situacao,'DEVOLVIDA') !== false
    ){
        continue;
    }

    $vendedorCompleto = trim(
    $venda['vendedor'] ?? 'SEM VENDEDOR'
    );

    $partesNome = explode(
        ' ',
        $vendedorCompleto
    );

    $primeiroNome = strtoupper(
        trim($partesNome[0] ?? '')
    );

    if(
    strpos($situacao,'FATURADO') === false
    ){

    $hoje = new DateTime();

    $diasAberto =
        $dataObj
        ? $dataObj->diff($hoje)->days
        : 0;

        if(
            !empty($filtroVendedor)
            &&
            strtoupper($filtroVendedor) != $primeiroNome
        ){
            continue;
        }

        $vendedoresFiltro[$primeiroNome] = $primeiroNome;

    $pedidosAbertos[] = [

        'data' => $venda['data'] ?? '',

        'id' => $pedido,

        'cliente' => $venda['cliente'] ?? '',

        'vendedor' => $primeiroNome,

        'situacao' => $situacao,

        'venda' => floatval(
            $venda['valor_total'] ?? 0
        ),

        'dias' => $diasAberto
    ];
}

    $valor = floatval(
        $venda['valor_total'] ?? 0
    );

    $vendedor = trim(
    $venda['vendedor'] ?? 'SEM VENDEDOR'
);


if($vendedor == '-'){
    $vendedor = 'SEM VENDEDOR';
}

/*
|--------------------------------------------------------------------------
| APENAS PRIMEIRO NOME
|--------------------------------------------------------------------------
*/

if($vendedor != 'SEM VENDEDOR'){

    $partesNome = explode(
        ' ',
        trim($vendedor)
    );

    $vendedor = $partesNome[0];
}
    /*
    |--------------------------------------------------------------------------
    | VENDAS POR VENDEDOR
    |--------------------------------------------------------------------------
    */

    if(!isset($vendasVendedor[$vendedor])){

        $vendasVendedor[$vendedor] = 0;
    }

    $vendasVendedor[$vendedor] += $valor;

    /*
    |--------------------------------------------------------------------------
    | VENDA MÊS A MÊS
    |--------------------------------------------------------------------------
    */

    $dataVenda = trim(
        $venda['data'] ?? ''
    );

    $dataObj = DateTime::createFromFormat(
        'd/m/Y',
        substr($dataVenda,0,10)
    );

    if($dataObj){

        $mesAno = $dataObj->format('Y-m');

        if(!isset($vendasMes[$mesAno])){

            $vendasMes[$mesAno] = 0;
        }

        $vendasMes[$mesAno] += $valor;
    }

$prazo = floatval(
    $venda['prazo_medio'] ?? 0
);

if(
    $dataObj &&
    $dataObj->format('m') == $mesAtual &&
    $dataObj->format('Y') == $anoAtual
){

    $totalVendidoMesAtual += $valor;

    $pedidosMesAtual++;

    $clienteAtual = trim(
        $venda['cliente'] ?? ''
    );

    if(!empty($clienteAtual)){
        $clientesMesAtual[$clienteAtual] = true;
    }

    if(!isset($vendasVendedorMesAtual[$vendedor])){
        $vendasVendedorMesAtual[$vendedor] = 0;
    }

    $vendasVendedorMesAtual[$vendedor] += $valor;

    if($prazo <= 0){

        $vistaPrazoMesAtual['vista'] += $valor;

        if(!isset($vistaVendedorMesAtual[$vendedor])){
            $vistaVendedorMesAtual[$vendedor] = 0;
        }

        $vistaVendedorMesAtual[$vendedor] += $valor;

    }else{

        $vistaPrazoMesAtual['prazo'] += $valor;

    }

}
    /*
    |--------------------------------------------------------------------------
    | FATURAMENTO
    |--------------------------------------------------------------------------
    */

$nf = $venda['nf'] ?? '-';

if (is_array($nf)) {
    $nf = $nf['numero'] ?? '-';
}

$nf = trim((string)$nf);

if(
    $nf == '-'
    || $nf == ''
    || empty($nf)
){
    continue;
}

$dataNF = $venda['data_nf'] ?? '';

    if (is_array($dataNF)) {
    
        $dataNF = $dataNF['data'] ?? '';
    
    } else {
    
        $dataNF = trim($dataNF);
    }

        $dataNFObj = DateTime::createFromFormat(
            'd/m/Y',
            substr($dataNF,0,10)
        );

        if($dataNFObj){

        if(
    $dataNFObj->format('m') == $mesAtual
    &&
    $dataNFObj->format('Y') == $anoAtual
    ){

    }

            $mesAnoNF =
                $dataNFObj->format('Y-m');

            if(!isset(
                $faturadoMes[$mesAnoNF]
            )){
                $faturadoMes[$mesAnoNF] = 0;
            }

            $faturadoMes[$mesAnoNF] += $valor;

            /*
            |--------------------------------------------------------------------------
            | META X REALIZADO
            |--------------------------------------------------------------------------
            */

            if(
    $dataNFObj->format('m') == $mesAtual
    &&
    $dataNFObj->format('Y') == $anoAtual
    ){

    $realizadoMesAtual += $valor;

    $totalPedidosRealizado++;

    $totalValorRealizado += $valor;

}

        
}
}

/*
|--------------------------------------------------------------------------
| ORDENAÇÕES
|--------------------------------------------------------------------------
*/

ksort($vendedoresFiltro);

arsort($vendasVendedor);

arsort($vendasVendedorMesAtual);

$vendasVendedorMesAtual = array_slice(
    $vendasVendedorMesAtual,
    0,
    5,
    true
);

$vendasVendedor = array_slice(
    $vendasVendedor,
    0,
    5,
    true
);

arsort($vistaVendedorMesAtual);

$vistaVendedorMesAtual = array_slice(
    $vistaVendedorMesAtual,
    0,
    5,
    true
);

ksort($faturadoMes);

/*
|--------------------------------------------------------------------------
| AJUSTE MANUAL JAN/FEV
|--------------------------------------------------------------------------
*/

$faturadoMes['2026-01'] = 2659330.58;
$faturadoMes['2026-02'] = 3563092.84;

ksort($faturadoMes);

ksort($vendasMes);

/*
|--------------------------------------------------------------------------
| AJUSTE MANUAL JAN/FEV
|--------------------------------------------------------------------------
*/

$vendasMes['2026-01'] = 4722365.38;
$vendasMes['2026-02'] = 4719583.94;

ksort($vendasMes);

$topVendedorNome = '-';
$topVendedorValor = 0;

$totalVendedores = count($vendasVendedor);

if(!empty($vendasVendedor)){

    $primeiroNome = array_key_first($vendasVendedor);

    $topVendedorNome = $primeiroNome;

    $topVendedorValor =
        $vendasVendedor[$primeiroNome];
}

$somaVendedores =
    array_sum($vendasVendedor);

$mediaPorVendedor =
    $totalVendedores > 0
    ? $somaVendedores / $totalVendedores
    : 0;

/*
|--------------------------------------------------------------------------
| CARDS MÊS ATUAL
|--------------------------------------------------------------------------
*/

$ticketMedioMesAtual =
    $pedidosMesAtual > 0
    ? $totalVendidoMesAtual /
        $pedidosMesAtual
    : 0;

$topVendedorMesAtual = '-';

$topVendedorValorMesAtual = 0;

if(!empty($vendasVendedorMesAtual)){

    $topVendedorMesAtual =
        array_key_first(
            $vendasVendedorMesAtual
        );

    $topVendedorValorMesAtual =
        current(
            $vendasVendedorMesAtual
        );
}

$totalVendedoresMesAtual =
    count($vendasVendedorMesAtual);

$mediaVendedorMesAtual =
    $totalVendedoresMesAtual > 0
    ? array_sum(
        $vendasVendedorMesAtual
    ) / $totalVendedoresMesAtual
    : 0;

/*
|--------------------------------------------------------------------------
| META
|--------------------------------------------------------------------------
*/

$faltaMeta =
    $metaMensal - $realizadoMesAtual;

if($faltaMeta < 0){
    $faltaMeta = 0;
}

$percentualMeta =
    $metaMensal > 0
    ? ($realizadoMesAtual / $metaMensal) * 100
    : 0;

echo '<pre>';

echo 'PEDIDOS: ' .
     $totalPedidosRealizado .
     PHP_EOL;

echo 'VALOR: ' .
     number_format(
         $totalValorRealizado,
         2,
         ',',
         '.'
     );

echo '</pre>';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">

<title>Dashboard Firsteel</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#000;
    color:#fff;
    font-family:Arial, Helvetica, sans-serif;
    padding:20px;
}

.layout{
    display:flex;
    min-height:100vh;
}

.sidebar{

    width:260px;

    background:#050505;

    border-right:2px solid #f3c623;

    padding:20px;

    position:fixed;

    top:0;
    left:0;

    height:100%;
}

.sidebar.fechada{

    width:70px;

    overflow:hidden;
}

.sidebar.fechada a,
.sidebar.fechada h3{

    display:none;
}

.sidebar.fechada .logo-menu{

    width:40px;
}

.conteudo{

    transition:0.3s;
}

.conteudo.expandido{

    margin-left:90px;

    width:calc(100% - 90px);
}

.logo-menu{

    width:180px;

    margin-bottom:30px;
}

.sidebar a{

    display:block;

    color:#fff;

    text-decoration:none;

    padding:15px;

    margin-bottom:10px;

    border-radius:8px;

    background:#111;

    transition:0.2s;
}

.sidebar a:hover{

    background:#f3c623;

    color:#000;
}

.conteudo{

    margin-left:280px;

    width:calc(100% - 280px);

    padding:20px;
}

.dashboard{
    display:block;
}

.grafico{

    width:100%;

    background:#111;

    border:1px solid #3a3a3a;

    border-radius:15px;

    padding:20px;

    min-height:560px;

    box-shadow:
        0 0 12px rgba(243,198,35,.12);
}

.grafico:hover{

    transform:translateY(-3px);

    box-shadow:
        0 0 20px rgba(243,198,35,0.35);
}

.grafico h3{

    margin-bottom:15px;

    color:#f3c623;

    font-size:22px;

    font-weight:bold;

    border-bottom:1px solid #333;

    padding-bottom:10px;
}

canvas{
    width:100% !important;
    height: 460px !important;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#111;
    order:1;
}

th{
    background:#050505;
    color:#f3c623;
    padding:12px;
    text-align:left;
}

td{
    padding:10px;
    border-bottom:1px solid #222;
}

tr:nth-child(even){
    background:#151515;
}

tr:hover{
    background:#1f1f1f;
}

.filtro-vendedores{

    width:260px;

    background:#111;

    border:1px solid #333;

    border-radius:8px;

    padding:10px;

    height:auto;

    order:2;
}

.filtro-titulo{

    color:#ffffff;

    font-weight:bold;

    font-size:24px;

    margin-bottom:10px;

    border-bottom:2px solid #f3c623;

    padding-bottom:5px;
}

.filtro-item{

    display:block;

    color:#ffffff;

    text-decoration:none;

    font-weight:bold;

    padding:6px 4px;

    transition:0.2s;
}

.filtro-item:hover{

    color:#f3c623;
}

.filtro-item.ativo{

    color:#f3c623;

    background:#1a1a1a;
}

</style>

</head>

<body>

<div class="layout">

    <div class="sidebar">

        <img
            src="logo_firsteel.png"
            class="logo-menu"
            id="toggleMenu"
            style="cursor:pointer;"
        >

        <a href="?grafico=origem">
            Origem das Vendas
        </a>

        <a href="?grafico=vendas_mes">
            Vendas por Mês
        </a>

        <a href="?grafico=vendedor">
            Vendas por Vendedor
        </a>

        <a href="?grafico=faturamento">
            Faturamento Mensal
        </a>

        <a href="?grafico=meta">
            Meta x Realizado
        </a>

        <a href="?grafico=vistaprazo">
            À Vista x Prazo
        </a>

        <a href="?grafico=vistavendedor">
            À Vista por Vendedor
        </a>

        <a href="?grafico=pedidos_abertos">
            Pedidos em Aberto
        </a>

    </div>

    <div class="conteudo">

<?php if($grafico == 'origem'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>Origem Venda</h3>
        <canvas id="origemVenda"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'vendas_mes'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>Venda Mês a Mês</h3>
        <canvas id="vendasMes"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'vendedor'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>Vendas por Vendedor</h3>
        <canvas id="vendedor"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'faturamento'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>Faturamento por Mês</h3>
        <canvas id="faturadoMes"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'meta'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>Meta X Realizado</h3>
        <canvas id="metaRealizado"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'vistaprazo'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>À Vista X A Prazo</h3>
        <canvas id="vistaPrazo"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'vistavendedor'){ ?>

<div class="dashboard">

    <div class="grafico">
        <h3>À Vista por Vendedor</h3>
        <canvas id="vistaVendedor"></canvas>
    </div>

</div>

<?php } elseif($grafico == 'pedidos_abertos'){ ?>

<div class="grafico">

    <h3>
        Pedidos Pendentes de Faturamento
    </h3>

    <div style="display:flex;gap:20px;align-items:flex-start;">

    <div class="filtro-vendedores">

    <div class="filtro-titulo">
        Filtro
    </div>

    <a
        href="?grafico=pedidos_abertos"
        class="filtro-item <?php echo empty($filtroVendedor) ? 'ativo' : ''; ?>"
    >
        TODOS
    </a>

    <?php foreach($vendedoresFiltro as $vend){ ?>

        <a
            href="?grafico=pedidos_abertos&vendedor=<?php echo urlencode($vend); ?>"
            class="filtro-item <?php echo ($filtroVendedor == $vend ? 'ativo' : ''); ?>"
        >
            <?php echo strtoupper($vend); ?>
        </a>

    <?php } ?>

</div>

<div style="flex:1;">

    <table style="width:100%;border-collapse:collapse;">

        <thead>

            <tr>

                <th>Data</th>
                <th>ID</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Situação</th>
                <th>Venda</th>
                <th>Dias em Aberto</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach($pedidosAbertos as $pedido){ ?>

            <tr>

                <td>
                    <?php echo $pedido['data']; ?>
                </td>

                <td>
                    <?php echo $pedido['id']; ?>
                </td>

                <td>
                    <?php echo htmlspecialchars(
                        $pedido['cliente']
                    ); ?>
                </td>

                <td>
                    <?php echo $pedido['vendedor']; ?>
                </td>

                <td>
                    <?php echo $pedido['situacao']; ?>
                </td>

                <td style="color:#f3c623;font-weight:bold;">

                    R$
                    <?php echo number_format(
                        $pedido['venda'],
                        2,
                        ',',
                        '.'
                    ); ?>

                </td>

                <td>
                    <?php echo $pedido['dias']; ?>
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>
    </div>
    </div>

</div>

<?php } ?>

</div>

<script>

    Chart.defaults.color = '#ffffff';

    Chart.defaults.font.family =
    'Arial, Helvetica, sans-serif';

    Chart.defaults.font.weight =
    'bold';

/*
|--------------------------------------------------------------------------
| ORIGEM DAS VENDAS
|--------------------------------------------------------------------------
*/

const origemVendaCanvas =
document.getElementById('origemVenda');

if(origemVendaCanvas){

    new Chart(
        origemVendaCanvas,
    {
        type:'bar',

        plugins:[ChartDataLabels],

        data:{

            labels:
            <?php echo json_encode($origemLabels); ?>,

            datasets:[{

                data:
                <?php echo json_encode($origemValores); ?>,

                backgroundColor:'#f3c623',

                borderRadius:8

            }]
        },

        options:{

            responsive:true,

            scales:{

                x:{

                    ticks:{
                        color:'#ffffff',
                        font:{
                            weight:'bold',
                            size:12
                        }
                    },

                    grid:{
                        display:false
                    }
                },

                y:{

                    ticks:{
                        color:'#ffffff',
                        font:{
                            weight:'bold',
                            size:12
                        }
                    },

                    grid:{
                        color:'#333333'
                    }
                }
            },

            plugins:{

                legend:{
                    display:false
                },

                tooltip:{
                    titleColor:'#ffffff',
                    bodyColor:'#ffffff'
                },

                datalabels:{

                    color:'#ffffff',

                    font:{
                        weight:'bold',
                        size:12
                    },

                    anchor:'end',

                    align:'top',

                    formatter:function(value){

                        return 'R$ ' +
                            (value / 1000000)
                            .toFixed(2) + ' Mi';

                    }
                }
            }
        }
    }
);

}

/*
|--------------------------------------------------------------------------
| FATURAMENTO POR MÊS
|--------------------------------------------------------------------------
*/

const faturadoMesCanvas =
document.getElementById('faturadoMes');

if(faturadoMesCanvas){

    new Chart(
        faturadoMesCanvas,
    {
        type:'bar',

        plugins:[ChartDataLabels],

        data:{

            labels:
            <?php echo json_encode(array_keys($faturadoMes)); ?>,

            datasets:[{

                data:
                <?php echo json_encode(array_values($faturadoMes)); ?>,

                backgroundColor:'#f3c623'

            }]
        },

        options:{

            responsive:true,

            plugins:{

                legend:{
                    display:false
                },

                datalabels:{
                    color:'#ffffff',
                    anchor:'end',
                    align:'top',
                    font:{
                        weight:'bold'
                    },
                    formatter:function(value){

                        return 'R$ ' +
                            (value / 1000000)
                            .toFixed(1) + ' Mi';
                    }
                }
            }
        }
    }
    );

}

/*
|--------------------------------------------------------------------------
| À VISTA X PRAZO
|--------------------------------------------------------------------------
*/

const vistaPrazoCanvas =
document.getElementById('vistaPrazo');

if(vistaPrazoCanvas){

    new Chart(
        vistaPrazoCanvas,
        {
            type:'pie',

            plugins:[ChartDataLabels],

            data:{

                labels:[
                    'À Vista',
                    'A Prazo'
                ],

                datasets:[{

                    data:[
                        <?php echo $vistaPrazoMesAtual['vista']; ?>,
                        <?php echo $vistaPrazoMesAtual['prazo']; ?>
                    ],

                    backgroundColor:[
                        '#f3c623',
                        '#555555'
                    ]

                }]
            },

            options:{

                responsive:true,

                plugins:{

                    legend:{
                        position:'bottom',
                        labels:{
                            color:'#ffffff',
                            font:{
                                weight:'bold',
                                size:12
                            }
                        }
                    },

                    datalabels:{

                        color:'#ffffff',

                        font:{
                            weight:'bold',
                            size:16
                        },

                        formatter:function(value, context){

                            const dados =
                                context.chart.data.datasets[0].data;

                            const total =
                                dados.reduce(
                                    (a,b) => Number(a) + Number(b),
                                    0
                                );

                            if(total <= 0){
                                return '0%';
                            }

                            const percentual =
                                (value / total) * 100;

                            return percentual.toFixed(1) + '%';
                        }

                    }

                }

            }

        }
    );

}

/*
|--------------------------------------------------------------------------
| VENDAS POR VENDEDOR
|--------------------------------------------------------------------------
*/

const vendedorCanvas =
document.getElementById('vendedor');

if(vendedorCanvas){

    new Chart(
        vendedorCanvas,
    {
        type:'bar',

        plugins:[ChartDataLabels],

        data:{

            labels:
            <?php echo json_encode(array_keys($vendasVendedorMesAtual)); ?>,

            datasets:[{

                data:
                <?php echo json_encode(array_values($vendasVendedorMesAtual)); ?>,

                backgroundColor:'#f3c623'

            }]
        },

        options:{

            responsive:true,

            plugins:{

                legend:{
                    display:false
                },

                datalabels:{
                    color:'#ffffff',
                    anchor:'end',
                    align:'top',
                    font:{
                        weight:'bold'
                    },
                    formatter:function(value){

                        return 'R$ ' +
                            (value / 1000000)
                            .toFixed(2) + ' Mi';
                    }
                }
            }
        }
    }
);

}

/*
|--------------------------------------------------------------------------
| VENDA MÊS A MÊS
|--------------------------------------------------------------------------
*/

const vendasMesCanvas =
document.getElementById('vendasMes');

if(vendasMesCanvas){

    new Chart(
        vendasMesCanvas,
    {
        type:'bar',

        plugins:[ChartDataLabels],

        data:{

            labels:
            <?php echo json_encode(array_keys($vendasMes)); ?>,

            datasets:[{

                data:
                <?php echo json_encode(array_values($vendasMes)); ?>,

                backgroundColor:'#f3c623'

            }]
        },

        options:{

            responsive:true,

            plugins:{

                legend:{
                    display:false
                },

                datalabels:{
                    color:'#ffffff',
                    anchor:'end',
                    align:'top',
                    font:{
                        weight:'bold'
                    },

                    formatter:function(value){

                        return 'R$ ' +
                            (value / 1000000)
                            .toFixed(1) + ' Mi';

                    }
                }
            }
        }
    }
);

}

/*
|--------------------------------------------------------------------------
| À VISTA POR VENDEDOR
|--------------------------------------------------------------------------
*/

const vistaVendedorCanvas =
document.getElementById('vistaVendedor');

if(vistaVendedorCanvas){

    new Chart(
        vistaVendedorCanvas,
    {
        type:'bar',

        plugins:[ChartDataLabels],

        data:{

            labels:
            <?php echo json_encode(array_keys($vistaVendedorMesAtual)); ?>,

            datasets:[{

                data:
                <?php echo json_encode(array_values($vistaVendedorMesAtual)); ?>,

                backgroundColor:'#f3c623'

            }]
        },

        options:{

            responsive:true,

            plugins:{

                legend:{
                    display:false
                },

                datalabels:{
                    color:'#ffffff',
                    anchor:'end',
                    align:'top',
                    font:{
                        weight:'bold'
                    },

                    formatter:function(value){

                        return 'R$ ' +
                            (value / 1000000)
                            .toFixed(1) + ' Mi';

                    }
                }
            }
        }
    }
);

}

/*
|--------------------------------------------------------------------------
| META X REALIZADO
|--------------------------------------------------------------------------
*/

const metaRealizadoCanvas =
document.getElementById('metaRealizado');

if(metaRealizadoCanvas){

    new Chart(
        metaRealizadoCanvas,
        {
            type:'doughnut',

            plugins:[{

                id:'textoCentro',

                afterDraw(chart){

                    const {ctx} = chart;

                    ctx.save();

                    ctx.textAlign = 'center';

                    ctx.fillStyle = '#ffffff';

                    ctx.font =
                        'bold 24px Arial';

                    ctx.fillText(

                        'R$ ' +

                        (
                            <?php echo $realizadoMesAtual; ?> /
                            1000000
                        ).toFixed(2) +

                        ' Mi',

                        chart.width / 2,

                        chart.height / 1.55

                    );

                    ctx.font =
                        'bold 14px Arial';

                    ctx.fillStyle =
                        '#f3c623';

                    ctx.fillText(

                        'Meta: R$ 3,50 Mi',

                        chart.width / 2,

                        chart.height / 1.40

                    );

                    ctx.restore();
                }

            }],

            data:{

                labels:[
                    'Realizado',
                    'Faltante'
                ],

                datasets:[{

                    data:[

                        <?php echo $realizadoMesAtual; ?>,

                        <?php echo $faltaMeta; ?>

                    ],

                    backgroundColor:[

                        '#f3c623',
                        '#333333'

                    ],

                    borderWidth:0

                }]
            },

            options:{

                rotation:-90,

                circumference:180,

                cutout:'75%',

                plugins:{

                    legend:{
                        display:false
                    }
                }
            }
        }
    );

}

    </script>

    </div>

</div>

<script>

const botaoMenu =
    document.getElementById('toggleMenu');

const sidebar =
    document.querySelector('.sidebar');

const conteudo =
    document.querySelector('.conteudo');

botaoMenu.addEventListener(
    'click',
    function(){

        sidebar.classList.toggle('fechada');

        conteudo.classList.toggle('expandido');
    }
);

</script>

</body>
</html>