<?php

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<div class="site-index">
    <?php use app\controllers\SiteController; ?>
    <h3>Most popular words</h3>
    <?php
    foreach ($result as $k => $v) {
        echo $k .' - '. $v.'<br>';
    }
    ?>
    <h3>All nodes</h3>
    <?= SiteController::displayNode($model, 0) ?>

</div>
