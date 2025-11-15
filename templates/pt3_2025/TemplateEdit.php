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
require(PHP_DIR."/localconfig.php");
?>
<?php
echo $htmlmeta->join("\n")."\n\n";
?>
<?php
echo $scripts->join("\n")."\n\n";
echo $styles->join("\n")."\n\n";
?>
<script>
window.addEventListener("load",_ => {
  let editor_textarea = q("textarea.editor")

  function relativise(targetUrl) {
    // Links to the same subdomain are turned into
    // intra wiki links. Links to a subfolder
    // are turned into relative links, others
    // into links from the root.
    let targetUrl2 = targetUrl.split("?")[0]
    targetUrl2 = targetUrl2.replace(/\/home$/,"")
    function firstDifferingChar(x,y) {
      let l = Math.min(x.length,y.length)
      for(let i=0; i<l; i++) {
        let a = x[i]
        let b = y[i]
        if( a !== b ) return i
      }
      return l
    }
    function commonPrefix(x,y) {
      let i = firstDifferingChar(x,y)
      return x.substr(0,i)
    }
    function dirOf(x) {
      return x.replace(/[^\/]*$/,"",x)
    }
    let currentUrl = window.location.href.split("?")[0]
    let dirOfCurrent = dirOf(currentUrl)
    let dirOfTarget = dirOf(targetUrl2)
    let cp = commonPrefix(dirOfCurrent,dirOfTarget)
    let regex = /^https:\/\/[^/]+\//i;
    if( ! cp.match(regex) ) {
      // link is off site
      return targetUrl
    }
    // if link is in a subdir, use relative
    // else if link is within wiki, strip http and domain
    console.log({cp,dirOfTarget,a:cp.length,b:dirOfTarget.length})
    if( cp.length === dirOfTarget.length ) {
      return targetUrl2.replace(new RegExp("^https?://[^/]*/"),"/")
    }
    return targetUrl2.replace(regex,"/")
  }
  function getSelectedRange(cm) {
    return { from: editor.getCursor(true), to: editor.getCursor(false) };
  }
  function pasteLinkPlain(cm) {
    if(!navigator.clipboard) {
      console.log("pasteLink1 Can't access clipboard")
      return true // TODO test
    }
    console.log("pasteLinkPlain")    
    navigator.clipboard.readText()
      .then(paste => {
        paste = relativise(paste)
        let selectedRange = getSelectedRange(cm)
        let { from } = selectedRange
        let selected = cm.getSelection()
        let delta
        if( selected === "" ) {
          newText = `[]($paste})`
          delta = 1-newText.length
        } else {
          newText = `[{$selected}]({$paste})`
          delta = 0
        }
        cm.replaceSelection(newText)
        if( delta != 0 ) {
          selectedRange = getSelectedRange(cm)
          from = selectedRange.from
          from.ch += delta
          cm.setCursor(from)
        }
    })
  }
})
</script>
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
$more_options = null;
$lines = explode("\n",$page_source,10);
$meta1 = [];
foreach($lines as $line) {
  if( preg_match('/^([a-zA-Z0-9]+):\s+(.*)$/',$line,$m) ) {
    $meta1[strtolower($m[1])] = $m[2];
  } else {
    break;
  }
}
if( isset($meta1['title']) ) {
  $headerTitle = $meta1['title'];
}
require("TemplateEdit_Header.php");
?>
</header>
<section class="main">
<textarea name='source' class="editor" autofocus><?php echo htmlspecialchars($page_source); ?></textarea>
</section>
</div>
</body>
</html>
