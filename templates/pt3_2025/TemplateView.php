<?php

require_once(PHP_DIR."/mtimes.php");
fmt_pagemtime($wiki->pagemtime);

// TODO: REFACTOR ONCE DONE, MOVE COMMON STUFF OUTSIDE THE SPECIFIC TEMPLATES
?><!DOCTYPE html>
<html>
<head>
  <meta charset='utf8'/>
  <title><?php echo "$pagename : /$subdir : ".SITE_SHORT_TITLE; ?></title>
<?php
require(PHP_DIR."/favicon.php");
?>
<?php
require(PHP_DIR."/localconfig.php"); # local to dir in wiki
?>

<?php
$options_json = json_encode($options);
$scripts->addscr("window.pageOptions = $options_json");
echo $htmlmeta->join("\n")."\n\n";
echo $scripts->join("\n")."\n\n";
echo $styles->join("\n")."\n\n";
?>
</head>
<?php
$classes = $bodyclasses->join(" ");
if( $classes !== "" ) {
  echo "<body class='$classes'>\n";
} else {
  echo "<body>\n";
}?>
<div class="container">
<header>
<?php
  require("TemplateView_Header.php");
?>
</header>
<?php
if( file_exists("Template_PreBody.php") ) {
  require("Template_PreBody.php");
}
?><section class="main">
<?php echo $page_rendered; ?>
<div class='clearer'>&nbsp;</div>
</section>
</div>
<?php
include("TemplateView_Footer.php");
?>
</body>
</html>
