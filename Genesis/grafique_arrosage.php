<?php
require_once("conexion.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script type="text/javascript">
            setTimeout('document.location.reload()',5000)
        </script>
        <title>Highcharts Example</title>

        <script type="text/javascript" src="../resources/jquery.js"></script>

        <!-- Ajouter le CDN FontAwesome pour l'icône d'arrosage -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style type="text/css">
        ${demo.css}
        </style>

        <script type="text/javascript">
            $(function () {
                $('#container').highcharts({
                    title: {
                        text: 'Cycle arrosage',
                        x: -20 //center
                    },
                    subtitle: {
                        text: 'Pompe Immergée ',
                        x: -20
                    },
                    xAxis: {
                        categories: [
                        <?php
                            $sql = " select date_heure from table_capteurs order by id desc limit 100 ";
                            $result = mysqli_query($connection, $sql);
                            while($registros = mysqli_fetch_array($result)){
                                ?>
                                    '<?php echo  $registros["date_heure"]?>',
                                <?php
                            }
                        ?>
                        ]
                    },
                    yAxis: {
                        title: {
                            text: 'Etat 1 = ON & 0 = OFF'
                        },
                        plotLines: [{
                            value: 0,
                            width: 1,
                            color: '#808080'
                        }]
                    },
                    tooltip: {
                        valueSuffix: ' Niveau '
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'middle',
                        borderWidth: 0
                    },
                    series: [
                    {   name: 'Etat de la pompe',
                        data: [
                        <?php
                            $query = " select arrosage from table_capteurs order by id desc limit 100 ";
                            $resultados = mysqli_query($connection, $query);
                            while($rows = mysqli_fetch_array($resultados)){
                                ?>
                                    <?php echo $rows["arrosage"]?>,
                                <?php
                            }
                        ?>
                        ]
                    }
                    ]
                });
            });
        </script>
    </head>
    <body>
        <script src="../resources/highcharts.js"></script>
        <script src="../resources/exporting.js"></script>

        <!-- Icône d'arrosage à gauche du titre -->
        <div style="text-align: left; font-size: 24px; display: flex; align-items: center;">
            <i class="fas fa-tint" style="margin-right: 10px;"></i> Cycle arrosage
        </div>

        <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

    </body>
</html>
