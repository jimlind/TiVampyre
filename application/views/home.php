<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=700" />
    <meta name="format-detection" content="telephone=no" />
    <title>TiVampyre</title>
    <link href="http://fonts.googleapis.com/css?family=Droid+Serif" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Droid+Sans" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="css/manager.css" />
    
    <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
    <script type="text/javascript" src="js/script.js"></script>
</head>
<body> 
<?php
    $counter = 0;
    $showCount = 0;
    $oldShowTitle = "";
    $tempShowTitle = "";
?>
    <div id="selector">
        <img class="close" src="images/icons/cross-button.png"/>
        <h1>Show Title</h1>
        <h2>Episode Title</h2>
        <form>
            <input type="hidden" id="show_id"/>
            <div class="process">
		<span>Process</span>
	    </div>
            <label for="keep"><img src="images/icons/safe.png"/>
            <input type="checkbox" id="keep"/> Keep MP2 Files</label>
            <br />
            <label for="chop"><img src="images/icons/scissors.png"/>
            <input type="checkbox" id="chop"/> Chop Commercials</label>
            <br />
            <label for="full"><img src="images/icons/magnifier-zoom-actual-equal.png"/>
            <input type="checkbox" id="full"/> Full Resolution</label>
            <br />
            <label for="crop"><img src="images/icons/image-crop.png"/>
            <input type="checkbox" id="crop"/> Crop Letterbox</label>
        </form>
        <?php //http://192.168.25.72/tivo/index.php?/job/queue/2/?keep&chopper ?>
    </div>

    <div id="content">
    <ul id="show_list">
    <?php foreach($shows as $show): ?>
	<?php if($tempShowTitle != $oldShowTitle && $tempShowTitle != $show->show_title): ?>
            <?php $oldShowTitle = $tempShowTitle ?>
            </ul>
	<?php endif; ?>
	
	<?php if($tempShowTitle != $show->show_title): ?>
            <?php $tempShowTitle = $show->show_title ?>
            <li><span class="show_title"><?= $tempShowTitle ?></span>
            <ul>
	<?php endif; ?>
	
        <li>
	<span class="show" id="show_<?= $show->true_id ?>" showId="<?= $show->true_id ?>" showTitle="<?= $tempShowTitle ?>">
	<?php if($show->icon !== NULL): ?>
            <img src="images/icons/<?= $show->icon ?>" height="16" width="16">
        <?php else: ?>
            <img src="images/icons/television--plus.png" height="16" width="16">
        <?php endif; ?>
        
	<?php if ($show->hd == 'Yes'): ?>
	    <strong class="hd">HD</strong>
        <?php endif; ?>
	
	    <span class="episodeTitle"><?= ($show->episode_title != "") ? $show->episode_title : "Episode/Movie" ?></span>
        
        <?php if($show->episode_number != 0): ?>
            (ep: <?= $show->episode_number ?>)
        <?php else: ?>
            (date: <?= date('n.d-Gi', strtotime($show->date)) ?>)
        <?php endif; ?>
        </span>
        </li>
    <?php endforeach; ?>
    </ul>
    </div>
    
</body>