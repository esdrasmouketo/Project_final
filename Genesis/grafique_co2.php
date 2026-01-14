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
        

        <script type="text/javascript" src="../resources/jquery.js"></script>

        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style type="text/css">
        ${demo.css}
        </style>

        <script type="text/javascript">
            $(function () {
                $('#container').highcharts({
                    title: {
                        text: 'Capteur de CO2',
                        x: -20 //center
                    },
                    subtitle: {
                        text: 'Capteur MQ-135 ',
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
                        valueSuffix: ' '
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'middle',
                        borderWidth: 0
                    },
                    series: [
                    {   name: ' Concentration de CO2',
                        data: [
                        <?php
                            $query = " select co2_level from table_capteurs order by id desc limit 100 ";
                            $resultados = mysqli_query($connection, $query);
                            while($rows = mysqli_fetch_array($resultados)){
                                ?>
                                    <?php echo $rows["co2_level"]?>,
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

        <!-- Icône de CO2 à gauche du titre -->
        <div style="text-align: left; font-size: 24px; display: flex; align-items: center;">
            <i class="fas fa-smog" style="margin-right: 10px;"></i> Capteur de CO2
        </div>

        <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

    </body>
</html>
