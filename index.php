<?php require_once(dirname(__FILE__) . '/includes.php');

$assetMeta = file_get_contents(dirname(__FILE__) . '/assets/meta.json');
$assetMeta = json_decode($assetMeta);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <base href="<?php echo $LOCAL_CONFIG['host'] . $LOCAL_CONFIG['base']; ?>/" target="_blank">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="session" content="<?php echo htmlspecialchars(json_encode($_SESSION)); ?>">
    <meta name="assets" content="<?php echo htmlspecialchars(json_encode($assetMeta)); ?>">
    <link rel="stylesheet" href="assets/global-styles.css">
    <title>DominoZ</title>
</head>

<body>
    <div id="app"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.0.11/typed.min.js"></script>
    <script src="assets/base.js"></script>
</body>

</html>
