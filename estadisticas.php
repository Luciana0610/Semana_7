<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Valoración de Riesgos</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 30px;
        }

        h1{
            text-align: center;
            color: #333;
        }

        table{
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th, td{
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }

        th{
            background-color: #007BFF;
            color: white;
        }

        .bajo{
            background-color: #b6f0c2;
        }

        .medio{
            background-color: #ffe08a;
        }

        .alto{
            background-color: #ff9b9b;
        }
    </style>
</head>
<body>

<h1>Valoración de Riesgos del Proyecto</h1>

<?php

$riesgos = [
    [
        "riesgo" => "Fallo en la conexión con la base de datos",
        "probabilidad" => 3,
        "impacto" => 3,
        "justificacion" => "Puede impedir el funcionamiento completo del sistema."
    ],
    [
        "riesgo" => "Errores en el registro de usuarios",
        "probabilidad" => 2,
        "impacto" => 2,
        "justificacion" => "Afecta parcialmente el acceso de los usuarios."
    ],
    [
        "riesgo" => "Pérdida de información",
        "probabilidad" => 2,
        "impacto" => 3,
        "justificacion" => "Puede generar pérdida de datos importantes."
    ],
    [
        "riesgo" => "Caída temporal del servidor",
        "probabilidad" => 1,
        "impacto" => 2,
        "justificacion" => "Interrumpe temporalmente el servicio."
    ]
];

?>

<table>
    <tr>
        <th>Riesgo</th>
        <th>Probabilidad</th>
        <th>Impacto</th>
        <th>Nivel de Riesgo</th>
        <th>Interpretación</th>
        <th>Justificación</th>
    </tr>

<?php

foreach($riesgos as $r){

    $nivel = $r["probabilidad"] * $r["impacto"];

    if($nivel <= 2){
        $interpretacion = "Bajo";
        $clase = "bajo";
    }elseif($nivel <= 4){
        $interpretacion = "Medio";
        $clase = "medio";
    }else{
        $interpretacion = "Alto";
        $clase = "alto";
    }

    echo "
    <tr class='$clase'>
        <td>{$r['riesgo']}</td>
        <td>{$r['probabilidad']}</td>
        <td>{$r['impacto']}</td>
        <td>$nivel</td>
        <td>$interpretacion</td>
        <td>{$r['justificacion']}</td>
    </tr>
    ";
}

?>

</table>

</body>
</html>