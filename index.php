<?php

use model\Session;

include dirname(__FILE__) . '/model/session.php';
Session::initialize();

$config = require(dirname(__FILE__) . '/.config.php');

$assetMeta = file_get_contents(dirname(__FILE__) . '/assets/meta.json');
$assetMeta = json_decode($assetMeta);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <base href="<?php echo $config['host'] . $config['base']; ?>/" target="_blank">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="session" content="<?php echo htmlspecialchars(json_encode($_SESSION)); ?>">
    <meta name="assets" content="<?php echo htmlspecialchars(json_encode($assetMeta)); ?>">
    <title>DominoZ</title>
</head>

<body>
    <div id="app"></div>
    <script src="assets/base.js"></script>
</body>

</html>
