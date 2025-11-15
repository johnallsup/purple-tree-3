<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo "$pagename - TYPING"; ?></title>
  <script src="/js/exp5.js"></script>
  <link rel="stylesheet" href="/css/exp5.css"/>
<?php 
$a = $source;
$a = str_replace("\r","",$a);
$a = preg_replace("@[“”]@",'"',$a);
$a = preg_replace("@[‘’]@","'",$a);
$a = preg_replace("@–@","--",$a);
$a = preg_replace("@—@","---",$a);
$lines = explode("\n",$a);
$outlines = [];
$from = null;
while(count($lines) > 0) {
  if( preg_match("@^(\w+):\s+(.*)$@",$lines[0],$m) ) {
    array_shift($lines);
    if( $m[1] === "from" ) {
      $from = "'$m[2]'";
    }
  } else {
    break;
  }
}
$a = implode("\n",$lines);
$a = preg_replace("/&/","&amp;",$a);
$a = preg_replace("/</","&lt;",$a);
$a = preg_replace("/>/","&gt;",$a);
$source = $a;

if( is_null($from) ) { $from = "null"; }
$from = "<script>
const attrib = $from
</script>";
$basefontsize = "<script>
    window.baseFontSize = 1.5
    </script>";

echo $from."\n";
?>
</head>
<body>
<?php
echo $source; 
?>
</body>
</html>
