<h1 class='page-title'><?php echo $headerTitle; ?></h1>
<?php
if( isset($meta['subtitle'] ) ) {
  echo "<h2 class='page-subtitle'>".$meta['subtitle']."</h2>\n";
}
if( ! is_null($navbar_source) ) {
  echo $navbar_rendered; 
}
