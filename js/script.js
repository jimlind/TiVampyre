$(document).ready(function(){
    
    // Hide the Pop-up
    $("#selector").css('display', 'none');
    
    // Show the Pop-up
    $(".show").click(function(){
        $p = $(this).position();
        $("#selector").position($p);
        $("#selector").css({'display':'block', 'left':$p.left+50, 'top':$p.top-50});
        $("#show_id").val($(this).attr('showId'));
        $("#selector h1").html($(this).attr('showTitle'));
        $("#selector h2").html($(this).children('.episodeTitle').html());
        $("#keep").attr('checked', false);
        $("#chop").attr('checked', false);
        $("#full").attr('checked', false);
        $("#crop").attr('checked', false);
    });
    
    // Close the Pop-up
    $(".close").click(function(){
        $("#selector").css('display', 'none');
    });
    
    // Click the 'GO' button in the Pop-up
    $(".process").click(function(){
        $v = $("#show_id").val();
        $k = $("#keep").attr('checked');
        $ch = $("#chop").attr('checked');
        $f = $("#full").attr('checked');
        $cr= $("#crop").attr('checked');
        $url = "index.php?/job/queue/"+$v+"/?";
        if($k){ $url+="keep&"; }
        if($ch){ $url+="chop&"; }
        if($f){ $url+="full&"; }
        if($cr){ $url+="crop&"; }
        $.post($url);
        $("#selector").css('display', 'none');
        $("#show_"+$v).find('img').attr("src", "images/icons/clock.png");
    });
    
});