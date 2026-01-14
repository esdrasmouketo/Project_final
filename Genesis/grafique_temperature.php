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

        <!-- Ajouter le CDN FontAwesome pour l'icône de thermomètre -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style type="text/css">
        ${demo.css}
        </style>

        <script type="text/javascript">
            $(function () {
                $('#container').highcharts({
                    title: {
                        text: 'Température',
                        x: -20 //center
                    },
                    subtitle: {
                        text: 'Capteur DHT11 ',
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
                            text: 'Valeur du capteur'
                        },
                        plotLines: [{
                            value: 0,
                            width: 1,
                            color: '#808080'
                        }]
                    },
                    tooltip: {
                        valueSuffix: ' °C'
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'middle',
                        borderWidth: 0
                    },
                    series: [
                    {   name: 'Température',
                        data: [
                        <?php
                            $query = " select temperature from table_capteurs order by id desc limit 100 ";
                            $resultados = mysqli_query($connection, $query);
                            while($rows = mysqli_fetch_array($resultados)){
                                ?>
                                    <?php echo $rows["temperature"]?>,
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

        <!-- Icône de thermomètre à gauche du titre -->
        <div style="text-align: left; font-size: 24px; display: flex; align-items: center; justify-content: flex-start;">
            <i class="fas fa-thermometer-half" style="margin-right: 10px;"></i> Température
        </div>

        <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

    </body>
</html>
