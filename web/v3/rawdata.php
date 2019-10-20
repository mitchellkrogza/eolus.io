<?php

$model = $_GET['model'];
$year = $_GET['year'];
$month = $_GET['month'];
$day = $_GET['day'];
$hour = $_GET['hour'];
$lat = $_GET['lat'];
$lng = $_GET['lng'];
$debug = false;
$errors = array();

// Only one group of the following is allowed:
$fcstVar = $_GET['var'];
$fcstLvl = $_GET['level'];
//  --- or ---
$bands = $_GET['bands'];

header('Content-type: application/json');

if (isset($bands) && (isset($fcstVar) || isset($fcstLvl))) {
    $errors[] = "'Bands' parameter cannot be used in conjuction with var/level.";
}

if (!isset($bands) && (!isset($fcstVar) || !isset($fcstLvl))) {
    $errors[] = "Var/level or bands parameters must be set.";
}


if (!preg_match('/^[0-9,]+$/', $bands)) {
    $errors[] = "Invalid bands.";
} else {
    $bandArr = explode(",", $bands);
    $bandList = join(" -b ", $bandArr);
    $bands = "-b " . $bandList;
}

if (!is_numeric($year) || strlen($year) != 4) {
    $errors[] = "Invalid year.";
}

if (!is_numeric($month) || strlen($month) != 2) {
    $errors[] = "Invalid month.";
    exit (1);
}

if (!is_numeric($day) || strlen($day) != 2) {
    $errors[] = "Invalid day.";
    exit (1);
}

if (!is_numeric($hour) || strlen($hour) != 2) {
    $errors[] = "Invalid hour.";
    exit (1);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $model) || strlen($model) < 3) {
    $errors[] = "Invalid model.";
    exit (1);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $fcstLvl)) {
    $errors[] = "Invalid level.";
    exit (1);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $fcstVar)) {
    $errors[] = "Invalid var.";
    exit (1);
}

if (!is_numeric($lat)) {
    $errors[] = "Invalid lat.";
    exit (1);
}

if (!is_numeric($lng)) {
    $errors[] = "Invalid lng.";
    exit (1);
}

if (isset($_GET['debug'])) {
    $debug = true;
}

if ((float) $lat > 90 || (float) $lat < -90 || (float) $lng > 180 || (float) $lng < -180 ) {
    $errors[] = "Invalid bounds.";
    exit (1);
}

$prefix = "/map/{$model}/{$model}_${year}{$month}{$day}_{$hour}Z_";
$values = [];

if (count ($errors) > 0) {
    echo json_encode([ "errors" => $errors ]);
    exit (1);
}

if (isset ($bands)) {
    $filename = $prefix . "t1.tif";
    $cmd = "gdallocationinfo -valonly {$bands} -wgs84 {$filename} {$lng} {$lat}";
    exec($cmd, $output, $return_var );
    if ($return_var > 0) {
        $errors[] = "Get value failed with code " . strval($return_var);
        echo json_encode([ "errors" => $errors ]);
        exit (1);
    } else {
        $values = $output;
    }
    echo json_encode([ "values" => $values ]);
    exit (0);
}
else {
    $filename = $prefix . $fcstVar . "_" . $fcstLvl ".tif";
    $cmd = "gdallocationinfo -valonly -wgs84 {$filename} {$lng} {$lat}";
    exec($cmd, $output, $return_var );
    if ($return_var > 0) {
        $errors[] = "Get value failed with code " . strval($return_var);
        echo json_encode([ "errors" => $errors ]);
        exit (1);
    } else {
        $values = $output;
    }
    echo json_encode([ "values" => $values ]);
    exit (0);
}


?>