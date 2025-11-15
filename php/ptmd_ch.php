<?php
/*
Purple Tree 3
Copyright (C) 2023-2025

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, version 3.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see https://www.gnu.org/licenses/.
*/
require_once("protect.php");
require_once("Parsedown.php");
require_once("truthy.php");
require_once("defs.php");

# Parser for PTMD -- most of the work is done by Parsedown
# but we want to turn WikiWords to links provided they occur in text (not headings, nor code, nor maths etc.)

# We need storage to get directories.
# So we do want a $wiki object to accumulate stuff.
class PTMD extends stdclass {
  function __construct($wiki) {
    $this->wiki = $wiki;
    $this->parsedown = new Parsedown();
    $this->uses = [];
  }
  function get_option_bool($optname,$default=false) {
    $options = &$this->options;
    return truthy(array_get($options,$optname,$default),$default);
  }
  function WikiWord_to_link($match) {
    $word = $match[0];
    if( preg_match("/[a-z]/",$word) ) {
      return "[$word]($word)";
      #return "<span class='WikiWord'>$word</span>";
    } else {
      return $word;
    }
  }
  ### INCLUDE
  function handle_include($m) {
    $wiki = $this->wiki;
    $subdir = $wiki->subdir;
    $storage = $wiki->storage;

    $fn = trim($m[1]);
    if( !preg_match('/^[\/\.a-zA-Z0-9_+%@=-]+$/',$fn) ) {
      return "";//"\n\nError - Invalid Page Name $fn\n\n";
    }
    if( $fn[0] == "/" ) {
      $fn .= ".ptmd";
    } else {
      $fn = "/".$subdir."/".$fn.".ptmd";
    }
    
    if( ! in_array($fn,$this->included) ) {
      array_push($this->included,$fn);
      if( $storage->has($fn) ) {
        $content = $storage->get($fn)."\n";

        # Strip optioms from included pages
        while( preg_match('/^[A-Za-z0-9-]+:/',$content) ) {
          $content = explode("\n",$content)[1];
        }
        $content = trim($content);

        # Recursively include
        $out = $this->process_include($content);
        $out = preg_replace('/^(#{1,5}) /m','#\1 ',$out);
      } else {
        return "";//"\n\nError $fn not found\n\n";
      }
      array_pop($this->included);
      return "\n\n".$out."\n\n";
    } else {
      return "";//\n\nError - circular include $fn\n\n"; # TODO fail silently
    }
  }
  function process_include($src) {
    $out = preg_replace_callback('@^#Include\s+(\S.*)$@m',[$this,"handle_include"],$src);
    return $out;
  }
  ### INLINE SPECIALS
  function special_inline_done($what,$args) {
    return "<span class='done_button'>✓ DONE</span>";
  }
  function special_inline_d($what,$args) {
    return $this->special_inline_done($what,$args);
  }
  function special_inline_this($what,$args) {
    $args = trim($args);
    $opts = explode(",",$args);
    $args = trim(array_shift($opts));
    $wiki = $this->wiki;
    $path = $wiki->path;
    $path = substr($path,0,strlen($path)-strlen(PAGE_EXT)-1);
    $xs = explode("/",$path);
    $name = array_pop($xs);
    $subdir = $wiki->subdir;
    switch($args) {
      case "name":
        $rval = $name;
        break;
      case "path":
        $rval = $path;
        break;
      case "subdir":
        $rval = $subdir;
        break;
      default:
        $rval = "THIS_$args??";
        break;
    }
    if( in_array("link",$opts) ) {
      return "<a href='$rval'>$rval</a>";
    } else {
      return $rval;
    }
  }
  function special_inline_sound($what,$args) {
    return $this->special_inline_audio($what,$args);
  }
  function special_inline_audio($what,$args) {
    $xs = explode(";",$args);
    $src = trim(array_shift($xs));
    $src = preg_replace("/'/","",$src);
    if( preg_match('/\.(mp3|m4a|ogg)$/',$src,$m) ) {
      $mime = $this->audio_mime($m[1]);
    } else {
      $mime = "unknown";
    }
    $autoplay = false;
    $name = false;
    $box = false;
    foreach($xs as $x) {
      $x = trim($x);
      if( $x === "autoplay" ) $autoplay = true;
      if( $x === "name" ) $name = true;
      if( $x === "box" ) $box = true;
    }
    $cls_add = $box ? " box" : "";
    
    $t = "";
    $t .= "<span class='audio-item$cls_add'>";
    if( $name ) $t .= "<span class='audio-item-name'>$src</span>";
    $t .= "<audio controls".($autoplay ? " autoplay" : "").">";
    $t .= "<source src='$src' type='".$mime."'/>";
    $t .= "</audio>";
    $t .= "</span>\n";
    return $t;
  }
  function special_inline_youtube($what,$args) {
    # Turn [[y:youtube-id]] into embedded youtube videos
    $args = trim($args);
    if( strlen($args) != 11 ) {
      return "<span class='error'>Invalid Youtube Id: <code>$args</code></span>";
    } 
    return "<div class='embedded-video centre'><iframe width='420' height='315' src='https://www.youtube.com/embed/$args'></iframe></div>";
  }
  function special_inline_dailymotion($what,$args) {
    # https://www.dailymotion.com/video/x8oyhq2
    $args = trim($args);
    return "<div style='position:relative;padding-bottom:56.25%;height:0;overflow:hidden;'> <iframe style='width:100%;height:100%;position:absolute;left:0px;top:0px;overflow:hidden' frameborder='0' type='text/html' src='https://www.dailymotion.com/embed/video/$args' width='100%' height='100%' allowfullscreen title='Dailymotion Video Player'> </iframe> </div>";
    //return "<div style='position:relative;padding-bottom:56.25%;height:0;overflow:hidden;'> <iframe style='width:100%;height:100%;position:absolute;left:0px;top:0px;overflow:hidden' frameborder='0' type='text/html' src='https://www.dailymotion.com/embed/video/$args' width='100%' height='100%' allowfullscreen title='Dailymotion Video Player'> </iframe> </div>";
    //return "<div style='position:relative;padding-bottom:56.25%;height:0;overflow:hidden;'> <iframe style='width:100%;height:100%;position:absolute;left:0px;top:0px;overflow:hidden' frameborder='0' type='text/html' src='https://www.dailymotion.com/embed/video/$args?autoplay=1' width='100%' height='100%' allowfullscreen title='Dailymotion Video Player' allow='autoplay'> </iframe> </div>";
    // return "<div class='embedded-video centre'><iframe class='dailymotion'>frameborder='0' type='text/html' src='https://www.dailymotion.com/embed/video/$args?autoplay=1' allowfullscreen title='Dailymotion Video Player' allow='autoplay'></iframe></div>";
  }
  function char_out_expletive($word) {
    $chars = '$@#%&!';
    $c = strlen($chars);
    $hash = hash("sha256",$chars);
    $l = strlen($word) - 2;
    if( $l < 1 ) return $l;
    $a = $word[0];
    $z = $word[$l+1];
    $s = null;
    for( $i = 0; $i < $l; $i++ ) {
      $h = $hash[$i%strlen($hash)];
      $h = intval($h,16);
      $h %= $c - 1;
      if( ! is_null($s) ) {
        $cs = str_replace("$s","",$chars);
      } else {
        $cs = $chars;
      }
      $x = $cs[$h];
      $s = $x;
      $a .= $s;
      }
    $a .= $z;
    return $a;
  }
  function special_inline_expletive($what,$args) {
    $args = trim($args);
    $l = strlen($args);
    if( $l < 3) {
      return "**expletive**";
    } else {
      return "**".$this->char_out_expletive($args)."**";
    }
  }
  function special_inline_dir($what,$args) {
    $wiki = $this->wiki;
    $subdir = $wiki->subdir;
    $storage = $wiki->storage;
    $pages = $storage->getglob($subdir."/*.".PAGE_EXT);
    $files = $storage->getglob($subdir."/*.*");
    $dirs = $storage->getglob($subdir."/*");
    $dirs = array_filter($dirs, function ($x) use($storage) { return $storage->isdir($x); } );
    $files = array_filter($files,function ($x) { return !preg_match('/\\.'.PAGE_EXT.'$/',$x); });
    $dirs = array_map(function($x) { return basename(trim($x,"/")); },$dirs);
    $dirs = array_filter($dirs,function($x) { return $x !== "" ; });
    $files = array_map(function($x) { return basename(trim($x,"/")); },$files);
    $pages = array_map(function($x) { return basename(trim($x,"/")); },$pages);
    $pages = array_filter($pages, function($x) { return preg_match('/^[^\.]+\.'.PAGE_EXT.'$/',$x); });
    $files = array_diff($files,$pages);
    
    $result = [];
    $opts = ["pages"=>false,"dirs"=>false,"files"=>false,"images"=>false,"regex"=>null,"fmt"=>null,"except"=>null];
    $args = preg_split("/\s*,\s*/",trim($args));
    foreach($args as $x) {
      $xs = explode("=",$x,2);
      if( count($xs) == 1 ) {
        array_push($xs,true);
      } else {
        if( ! preg_match('/^(regex|except)$/',$xs[0]) ) {
          $xs[1] = truthy($xs[1],false);
        }
      }
      $opts[$xs[0]] = $xs[1];
    }
    if( $opts["pages"] ) {
      foreach($pages as $page) {
        $page = preg_replace('/\.'.PAGE_EXT.'$/','',$page);
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$page ) ) {
          continue;
        }
        if( is_string($opts['regex']) ) {
          if( preg_match("/".$o['regex']."/",$page ) ) {
            array_push($result,"[$page](".urlencode($page).")");
          }
        } else {
          array_push($result,"[$page](".urlencode($page).")");
        }
      }
    }
    if( $opts["dirs"] ) {
      foreach($dirs as $dir) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$dir ) ) {
          continue;
        }
        if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$dir ) ) {
          array_push($result,"[$dir](".urlencode($dir).")");
        }
      }
    }
    if( $opts["files"] ) {
      foreach($files as $file) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$file ) ) {
          continue;
        }
        if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$file ) ) {
          array_push($result,"[$file](".urlencode($file).")");
        }
      }
    }
    if( $opts["images"] && ! $opts["files"] ) {
      foreach($files as $file) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$file ) ) {
          continue;
        }
        if( preg_match(IMAGE_REGEX,$file) ) {
          if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$file ) ) {
            array_push($result,"[$file](".urlencode($file).")");
          }
        }
      }
    }
    return implode(" ",$result); 
  }
  function special_inline_jucedocl($what,$args) {
    # juce class docs
    $args = trim($args);
    # once Parsedown meets html, it stops expanding markdown, so we need to protect this from the parser
    # return "<a href='https://docs.juce.com/master/class{$args}.html' class='inline special juce class'><span class='juce-prefix cpp-prefix'>juce::</span><span class='class-name'>$args</span></a>";
    return "[juce::$args](https://docs.juce.com/master/class{$args}.html)";
  }

  ### END INLINE SPECIALS
  ### BLOCK SPECIALS
  function audio_mime($ext) {
    switch($ext) {
    case "mp3":
      return "audio/mpeg";
    case "m4a":
      return "audio/mp4";
    case "ogg":
      return "audio/ogg";
    default:
      return "unknown";
    }
  }
  function special_block_ythead($what, $options, $content) {
    $content = trim($content);
    $options = trim($options);
    $lines = explode("\n",$content);
    if( preg_match('/^[1-6]$/', $options) ) {
      $lvl = intval($options);
    } else {
      $lvl = 2;
    }
    if( count($lines) != 4 ) {
      return "<h1>Invalid Ythead</h1>";
    }
    $htitle = $lines[0];
    $h = str_repeat("#",$lvl) . " " . $htitle;
    $who = $lines[1];
    if( preg_match('@(.*\S)\s+(https://\S+)$@',$who,$m) ) {
      $whoname = $m[1];
      $chan = $m[2];
      $who = "<span class='yt-chan'>By <a class='yt-chan-name' href='$chan'>$whoname</a></span>";
    } else {
      $who = "<span class='yt-chan'>By <span class='yt-chan-name'>$who</a></span>";
    }
    $title = $lines[2];
    $url = $lines[3];
    $vtitle = "<span class='yt-vidname'>Video: <a href='$url'>$title</a></span>";
    $out = [ $h, "<p class='yt-ref'>$vtitle<br/>$who</p>" ];
    return implode("\n",$out);
  }
  function special_block_large($what, $options, $content) {
    $c = $this->parsedown->text($content);
    return "<div class='large'>$c</div>";
  }
  function special_block_cquote($what, $options, $content) {
    $c = $this->parsedown->text($content);
    $o = trim($options);
    if( $o === "" ) {
      return "<div class='cquote'><div class='quote'>$c</div></div>";
    } else {
      $d = "<div class='attrib'>$o</div>";
      return "<div class='cquote'><div class='quote'>$c\n$d</div></div>";
    }
  }
  function special_block_aquote($what, $options, $content) {
    $c = $this->parsedown->text($content);
    $o = trim($options);
    if( $o === "" ) {
      return "<div class='aquote'><div class='quote'>$c</div></div>";
    } else {
      $d = "<div class='attrib'>$o</div>";
      return "<div class='aquote'><div class='quote'>$c\n$d</div></div>";
    }
  }
  function special_block_g($what, $options, $content) { // alias for glish since I use it so often
    return $this->special_block_glish($what, $options, $content);
  }
  function special_block_glish($what, $options, $content) {
    #$c = $this->parsedown->text($content);
    $c = htmlentities($content);
    $options = trim($options);
    if( $options == "" ) {
      $os = [];
    } else {
      $os = preg_split('/\s+/',$options);
    }
    $os = array_map(function($x) { return "glish$x"; },$os);
    array_unshift($os,"glish");
    $o = implode(" ",$os);
    $s = "<div class='$o'>$c</div>";
    $hash = hash("sha256",$s);
    $phash = "<p>$hash</p>";
    array_push($this->protected_outer,[$phash,$s]);
    return $phash;
  }
  function special_block_largepoem($what, $options, $content) {
    $c = $this->parsedown->text($content);
    return "<div class='large poem'>$c</div>";
  }
  function get_dir($opts) {
    $wiki = $this->wiki;
    $subdir = $wiki->subdir;
    $storage = $wiki->storage;
    $pages = $storage->getglob($subdir."/*.".PAGE_EXT);
    $files = $storage->getglob($subdir."/*.*");
    $dirs = $storage->getglob($subdir."/*");
    $pages = array_map(function($x) { return ltrim($x,"/"); },$pages);
    $files = array_map(function($x) { return ltrim($x,"/"); },$files);
    $dirs = array_map(function($x) { return ltrim($x,"/"); },$dirs);
    $dirs = array_filter($dirs, function ($x) use($storage) { return $storage->isdir($x); } );
    $files = array_filter($files,function ($x) { return !preg_match('/\\.'.PAGE_EXT.'$/',$x); });
    $dirs = array_map(function($x) { return basename(trim($x,"/")); },$dirs);
    $dirs = array_filter($dirs,function($x) { return $x !== "" ; });
    $files = array_map(function($x) { return basename(trim($x,"/")); },$files);
    $pages = array_map(function($x) { return basename(trim($x,"/")); },$pages);
    $pages = array_filter($pages, function($x) { return preg_match('/^[^\.]+\.'.PAGE_EXT.'$/',$x); });
    $files = array_diff($files,$pages);
    $result = [];
    if( $opts["pages"] ) {
      foreach($pages as $page) {
        $page = preg_replace('/\.'.PAGE_EXT.'$/','',$page);
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$page ) ) {
          continue;
        }
        if( is_string($opts['regex']) ) {
          if( preg_match("/".$opts['regex']."/",$page ) ) {
            array_push($result,$page);
          }
        } else {
          array_push($result,$page);
        }
      }
    }
    if( $opts["dirs"] ) {
      foreach($dirs as $dir) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$dir ) ) {
          continue;
        }
        if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$dir ) ) {
          array_push($result,$dir);
        }
      }
    }
    if( $opts["files"] ) {
      foreach($files as $file) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$file ) ) {
          continue;
        }
        if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$file ) ) {
          array_push($result,$file);
        }
      }
    }
    if( $opts["images"] && ! $opts["files"] ) {
      foreach($files as $file) {
        if( is_string($opts['except']) && preg_match("/".$opts['except']."/",$file ) ) {
          continue;
        }
        if( preg_match(IMAGE_REGEX,$file) ) {
          if( is_null($opts['regex']) || preg_match("/".$opts['regex']."/",$file ) ) {
            array_push($result,$file);
          }
        }
      }
    }
    return $result;
  }
  function special_block_dir($what, $options, $content) {
    // main idea is a recursive search
    $args = preg_split("/\s*,\s*/",trim($options));
    $opts = ["pages"=>false,"dirs"=>false,"files"=>false,"images"=>false,"regex"=>null,"fmt"=>null,"except"=>null];
    $meta = [];
    $lines = explode("\n",trim($content));
    $normal_lines = [];
    foreach($lines as $line) {
      if( preg_match('/^(\S+):\s+(.*)$/',$line,$m) ) {
        [ $all, $key, $value ] = $m;
        $meta[$key] = trim($value);
      } else {
        array_push($normal_lines,$line);
      }
    }
    $inner_content = trim(implode("\n",$normal_lines));
    $classes = [];
    if( array_key_exists("class",$meta) ) {
      $xs = preg_split('/\s+/',$meta["class"]);
      foreach($xs as $x) {
        array_push($classes,$x);
      }
    }
    foreach($args as $x) {
      $xs = explode("=",$x,2);
      if( count($xs) == 1 ) {
        array_push($xs,true);
      } else {
        if( ! preg_match('/^(regex|except)$/',$xs[0]) ) {
          $xs[1] = truthy($xs[1],false);
        }
      }
      $opts[$xs[0]] = $xs[1];
    }
    $xs = $this->get_dir($opts);
    if( is_null($opts["fmt"]) ) {
      $fmt = "ul";
    } else {
      $fmt = $opts["fmt"];
      if( ! method_exists($this,"fmt_dir_$fmt") ) {
        $fmt = "ul";
      }
    }
    $method = "fmt_dir_$fmt";
    $formatted = $this->$method($xs);
    array_unshift($classes,"dir");
    $classes = implode(" ",$classes);
    $out = "<div class='$classes'>\n";
    $heading = null;
    if( array_key_exists("heading",$meta) ) {
      $heading = $meta["heading"];
    } else if( array_key_exists("h1",$meta) ) {
      $heading = $meta["h1"];
    } else if( array_key_exists("h",$meta) ) {
      $heading = $meta["h"];
    }
    if( ! is_null($heading) ) {
      $out .= "<h2>$heading</h2>\n";
    }
    if( $inner_content != "" ) {
      $out .= "<div class='inner_content'>".$this->parsedown->text($inner_content)."</div>";
    }
    $out .= "<div class='inner_dir'>".$this->parsedown->text($formatted)."</div>\n";
    $out .= "</div>";
    return $out;
  }
  function special_block_chords1($what, $options, $content) {
    $html = "<pre class='chords1'>\n";
    $html .= htmlentities($content);
    $html = preg_replace('/^\s*$/m','&nbsp;',$html);
    $html .= "</pre>";
    return $html;
  }
  function special_block_emoji($what, $options, $content) {
    $b = explode("\n",$content);
    $output = "";
    foreach($b as $line) {
      $xs = explode(":",$line,2);
      if( count($xs) != 2 ) continue;
      $cat = trim($xs[0]);
      $ems = trim($xs[1]);
      $ems = preg_split("//u",$ems);
      $t = "";
      foreach($ems as $em) {
        if( $em == "" ) continue;
        $ord = mb_ord($em,"utf8");
        if( $ord < 128 ) continue;
        $hex = dechex($ord);
        $t .= "<span class='unicode-char'><span class='emoji-char'>$em</span> <span class='int'>$ord</span> <span class='hex'>0x$hex</span> <span class='html-entity'>&amp;#x$hex;</span>\n";
      }
      if( $t !== "" ) {
        $output .= "<div class='emoji-category'><span class='category-name'>$cat</span>: \n$t\n</div>";
      }
    }
    if( $output !== "" ) {
      return "<div class='emoji'>\n$output\n</div>";
    }
    return "";
  }
  function special_block_marginpar($what, $options, $content) {
    return "<div class='block-special marginpar'>\n".$this->parsedown->text(trim($content))."\n</div>";
  }
  function special_block_remarks($what, $options, $content) {
    return "<div class='block-special boxed1 remarks'>\n".$this->parsedown->text(trim($content))."\n</div>";
  }
  function special_block_audio1($what, $options, $content) {
    # list of audio files
    $lines = explode("\n",trim($content));
    $t = "<div class='block-special audio1'>\n";
    foreach($lines as $line) {
      $xs = explode(";",$line);
      $src = trim(array_shift($xs));
      if( preg_match('/\.(mp3|m4a|ogg)$/',$src,$m) ) {
        $mime = $this->audio_mime($m[1]);
      } else {
        $mime = "unknown";
      }
      $autoplay = false;
      $name = true;
      foreach($xs as $x) {
        $x = trim($x);
        if( $x === "autoplay" ) $autoplay = true;
        if( $x === "noname" ) $name = false;
      }
      $t .= "<div class='audio-item'>\n";
      if( $name ) $t .= "<span class='audio-item-name'>$src</span> ";
      $t .= "<audio controls".($autoplay ? " autoplay" : "").">";
      $t .= "<source src='$src' type='".$mime."'/>";
      $t .= "</audio>\n";
      $t .= "</div>\n";
    }
    $t .= "</div>";
    return $t;
  }
  function special_block_boxed1($what,$options,$content) {
    $text = $this->parsedown->text($content);
    $text = preg_replace('@</h\d>@','</div>',$text);
    $text = preg_replace('@<h(\d)>@','<div class="heading h\1">',$text);
    return "<div class='block-special boxed1'>\n$text\n</div>";
  }
  function special_block_listspecials($what,$options,$content) {
    $a = @file_get_contents("ptmd.php");
    $lines = explode("\n",$a);
    $specials = array_filter($lines,function($x) {
      return preg_match('/^\s*function special_/',$x);
    });
    $specials = array_map(function($x) {
      preg_match('/special_([A-Za-z0-9_]+)/',$x,$m);
      $a = $m[1];
      return $a;
    },$specials);
    $html = "<pre><code>\n";
    $html .= implode("\n",$specials);
    $html .= "</code></pre>";
    return $html;
  }
  function special_block_aside($what,$options,$content) {
    $content_html = $this->parsedown->text($content);
    return "<div class='block-special aside'>$content_html</div>";
  }
  function special_block_htmlcomment($what,$options,$content) {
    return "<!--\n$content\n-->";
  }
  function special_block_NoteToSelf($what,$options,$content) {
    $content_html = $this->parsedown->text($content);
    return "<div class='block-special note-to-self'>$content_html</div>";
  }
  const BIBLE_BOOKS = [
    # OT
    "gen" => "Genesis",
    "exo" => "Exodus",
    "lev" => "Leviticus",
    "num" => "Numbers",
    "deu" => "Deuteronomy",

    "psa" => "Psalms",

    # NT
    "matt" => "Matthew",
    "mark" => "Mark",
    "luke" => "Luke",
    "john" => "John",
    "acts" => "Acts",
    "rom" => "Romans",
    "1cor" => "1 Corinthians",
    "2cor" => "2 Corinthians",
    "gal" => "Galatians",
    "eph" => "Ephesians",
    "phil" => "Philippians",
    "col" => "Colossians",
    "1thes" => "1 Thessalonians",
    "2thes" => "2 Thessalonians",
    "1tim" => "1 Timothy",
    "2tim" => "2 Timothy",
    "tit" => "Titus",
    "phlm" => "Philemon",
    "heb" => "Hebrews",
    "jam" => "James",
    "1pet" => "1 Peter",
    "2pet" => "2 Peter",
    "1jhn" => "1 John",
    "2jhn" => "2 John",
    "3jhn" => "3 John",
    "jud" => "Jude",
    "rev" => "Revelation"
  ];
  const BIBLE_SIGILS = [
    "#" => "bible-comment",
    "%" => "bible-text",
    "%%" => "bible-text",
    "@" => "bible-poetry",
    "&" => "note"
  ];
  function make_biblep_para($sigil,$para,$bookchap = "",$sigilpara = false) {
    if( ! $sigil == "@" ) $para = trim($para);
    $pt = self::BIBLE_SIGILS[$sigil];
    if( $sigil[0] == "%" || $sigil == "@") {
      $para = preg_replace('/^\s*\n/m','',$para);
      $para = $this->parsedown->line($para); # TODO can we use ->line instead? Then bullet lists don't work.
      $para = preg_replace_callback('/(\d+)([a-z]?)/',function($m) {
        [ $all, $vn, $c ] = $m;
        $r = "<span class='verse-number'>$vn";
        if( $m[2] ) {
          $r .= "<span class='continued'>".$m[2]."</span>";
        }
        $r .= "</span>";
        return $r;
      },$para);
      $p = "<p class='$pt'>$para</p>";
      if( $sigilpara && $sigil == "%%" && $bookchap !== "" ) {
        $p = "<p class='book-chapter'>$bookchap</p>\n$p";
      }
    } else {
      $para = $this->parsedown->text($para);
      $p = "<div class='$pt'>$para</div>";
    }
    return $p;
  }
  const QUOTES = array(
    "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
    "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
    "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
    "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
    "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
    "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
    "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
    "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
    "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
    "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
    "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
    "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
  );
  function special_block_output($what,$options,$content) {
    return "<pre class='output'>\n".trim($content)."\n</pre>";
  }
  function special_block_biblep($what,$options,$content) {
    # # comment
    # % book
    # & note
    $content = trim($content);
    $content = strtr($content,self::QUOTES);
    $lines = explode("\n",$content);
    $paras = [];
    $sigil = "#";
    $para = "";
    $firstline = "";
    $bookchap = "";
    $sigilpara = false;
    foreach($lines as $line) {
      if( preg_match('/^\s*$/',$line) ) { # blank lines break paras
        if( $para !== "" ) {
          array_push($paras,$this->make_biblep_para($sigil,$para,$bookchap,$sigilpara));
          $sigilpara = false;
        }
        $para = "";
        continue;
      }
      $s = explode(" ",$line,2)[0];
      if( isset(self::BIBLE_SIGILS[$s]) ) {
        if( $para !== "" ) {
          array_push($paras,$this->make_biblep_para($sigil,$para,$bookchap,$sigilpara));
        }
        $sigilpara = true;
        $para = "";
        $sigil = $s;
        if( $sigil === "%" && preg_match('/(\w{3,4})\s+(\d+)/',$line,$m) ) {
          $bookname = $m[1];
          $chapnum = $m[2];
          if( isset(self::BIBLE_BOOKS[$bookname]) ) {
            $bookname = self::BIBLE_BOOKS[$bookname];
          }
          $bookchap = "<span class='book-name'>$bookname</span> <span class='chapter-number'>$chapnum</span>";
          array_push($paras,"<p class='book-chapter'>$bookchap</p>\n");
          $para = "";
        } else {
          $para = preg_replace('/^[%$@&#]+\s*/','',$line);
        }
        continue;
      }
      $para .= "\n$line";
    }
    if( $para !== "" ) {
      array_push($paras,$this->make_biblep_para($sigil,$para,$bookchap));
    }
    $div = "<div class='block-special biblep'>\n".implode("\n",$paras)."\n</div>";
    return $div;
  }
  function special_block_defn($what,$options,$content) {
    #$content_html = $this->parsedown->text($content);
    $options = trim($options);
    if( $options == "" ) $terms = "";
    else {
      $terms = implode(" ",preg_split('/\s+/',trim($options)));
      $terms = " terms='$terms'";
    }
    $paras = preg_split('/\n{2,}/',trim($content));
    $t = "";
    $first = true;
    foreach($paras as $para) {
      $t .= $this->parsedown->text($para);
    }
    return "<div class='block-special definition' $terms>$t</div>";
  }
  function special_block_indblock($what,$options,$content) {
    #$content_html = $this->parsedown->text($content);
    $options = trim($options);
    $paras = preg_split('/\n{2,}/',trim($content));
    $t = "";
    $first = true;
    foreach($paras as $para) {
      $t .= $this->parsedown->text($para);
    }
    return "<div class='block-special indblock'><p><span class='heading'>".trim($options).".</span></p>\n$t</div>";
  }
  function special_block_warning($what,$options,$content) {
    $a = trim($options);
    $paras = preg_split('/\n{2,}/',trim($content));
    $first_para = array_shift($paras);
    if( $a === "" ) { $a = "Warning"; }
    $t = "<div class='warning'>\n";
    $t .= "<div class='warning-para'><span class='warning-prefix'>$a:</span>&nbsp;<span class='warning-text'>$first_para</span></div>\n";
    foreach($paras as $para) {
      $t .= "<div class='warning-para'><span class='warning-text'>$para</span></div>\n";
    }
    $t .= "</div>\n";
    return $t;
  }
  function special_block_dham($what,$options,$content) {
    $verses = explode("\n",trim(str_replace("\r","",$content)));
    $out = [];
    foreach($verses as $verse) {
      if( preg_match('/^(\d+)\.\s+(.*)$/',$verse,$m) ) {
        $vn = "<span class='verse-number'>".$m[1].".</span>";
        $tx = "<span class='verse-text'>".$this->parsedown->line($m[2])."</span>";
        $p = "<p>$vn $tx</p>";
        array_push($out,$p);
      }
    }
    $html = "<div class='dham budd'>\n".implode("\n",$out)."\n</div>";
    return $html;
  }
  function special_block_prayer1($what,$options,$content) {
    $paras = preg_split('/\n{2,}/',trim($content));
    $t = "<div class='block-special prayer1'>\n";
    $who = null;
    foreach($paras as $para) {
      // if( preg_match("/^(\w+)\./",$para) {
      // if no match, output para without a who.
      if( preg_match('/^(\w+)\.\s+(.*)/s',$para,$m) ) {
        $who = $m[1];
        $whos = $who;
        $text = $m[2];
      } else {
        $whos = null;
        $text = $para;
      }
      $text = $this->parsedown->line($text);
      if( $whos ) {
        $text = "<span class='who'>$who</span> ".$text;
      }
      if( $who ) {
        $t .= "<div class='prayer' for='$who'>\n$text\n</div>";
      } else {
        $t .= "<div class='prayer'>\n$text\n</div>";
      }
    }
#      [$who,$what] = explode(".",$para,2);
#      $lines = explode("\n",$what);
#      $first_line = array_shift($lines);
#      $paras = [];
#      array_push($paras, "<span class='who'>$who</span> $first_line");
#      foreach($lines as $line) {
#        $line = preg_replace('/\*\*(.*?)\*\*/',"<strong>$1</strong>",$line);
#        $line = preg_replace('/\*(.*?)\*/',"<em>$1</em>",$line);
#        array_push($paras,$line);
#      }
#      $paras = array_map(function($x) { return "<div class='prayer-para'>$x</div>"; },$paras);
#      $s = implode("\n",$paras);
#      $who = trim($who);
#      $what = trim($what);
#      $t .= "<div class='prayer' for='$who'>\n$s\n</div>";
#    }
    $t .= "</div>";
    return $t;
  }
  function special_block_multi($what,$options,$content) {
    $lines = explode("\n",$content);
    $t = "";
    $line = preg_split("/\\s+/",trim(array_shift($lines)));
    $s = "";
    foreach($line as $word) {
      $s .= "<a href='$word'>$word</a> ";
    }
    $t .= "<p class='hello'>".trim($s)."</p>\n";
    if( count($lines) == 0 ) return $t;
    $line = trim(array_shift($lines));
    $t .= $this->special_block_duolingo("duolingo","apple orange","$line\n$line\n$line\n\n$line\n$line")."\n";
    if( count($lines) == 0 ) return $t;
    $t .= $this->special_block_poem("poem","",implode("\n",$lines));
    return $t;
  }
  function special_block_poetry($what,$options,$content) {
    return $this->special_block_poem("poetry",$options,$content);
  }
  function special_block_quote2($what,$options,$content) {
    return $this->special_block_poem("quote2",$options,$content);
  }
  function special_block_axioms($what,$options,$content) {
    return $this->special_block_poem("axioms",$options,$content);
  }
  function special_block_duolingo($what,$options,$content) {
    # for formatting the pairs of sentences we find in duolingo
    $options = trim($options);
    if( $options === "" ) {
      $meta = $this->meta;
      if( isset($meta["duolingo"]) ) {
        $options = trim($meta["duolingo"]);
      }
    }
    
    $tlang = "";
    $slang = "";
    if( preg_match('/(\S+)\s+(\S+)/',$options,$m) ) {
      [ $all, $tlang, $slang ] = $m;
      $tlang = "<span class='target-lang'>$tlang</span>: ";
      $slang = "<span class='source-lang'>$slang</span>: ";
    }
    $sentences = preg_split('/\n{2,}/',$content);
    $t = "<div class='block-special duolingo-sentences'>\n";
    foreach($sentences as $s) {
      $lines = explode("\n",$s);
      $t .= "<div class='duolingo-sentence'>\n";
      $ts = array_shift($lines);
      $t .= "<div class='duolingo-target-sentence'>$tlang<span class='sentence'>$ts</span></div>\n";
      if( count($lines) > 0 ) {
        $ss = array_shift($lines);
        $t .= "<div class='duolingo-source-sentence'>$slang<span class='sentence'>$ss</span></div>\n";
      }
      if( count($lines) > 0 ) {
        $s = implode("\n",$lines);
        $t .= "<p class='duolingo-comment'>$s</p>\n";
      }
      $t .= "</div>\n";
    }
    $t .= "</div>\n";
    return $t;
  }
  function special_block_langue1($what,$options,$content) {
    # for formatting pairs of translated language (one line foreign, the other english)
    if( trim($content) === "" ) return "";
    $paras = preg_split('/\n{2,}/',trim($content));
    $out_paras = "";
    foreach($paras as $para) {
      $out_para = "<table class='langue1-table'>\n";
      $lines = explode("\n",$para);
      foreach($lines as $line) {
        if( $line[0] === "#" ) {
          $line = trim(substr(ltrim($line,"#"),1));
          $out_para .= "<tr><td class='heading' colspan='2'>$line</td></tr>\n";
        } else if( preg_match('/^(.*?)\s+---\s+(.*)$/',$line,$m) ) {
          [ $all, $for, $eng ] = $m;
          $out_para .= "<tr class='langue-item'><td class='foreign'>$for</td><td class='english'>$eng</td></tr>\n";
        } else {
          $out_para .= "<tr class='langue-item'><td class='foreign' colspan='2'>$line</td></tr>\n";
        }
      }
      $out_para .= "</table>\n";
      $out_paras .= $out_para;
    }
    return "<div class='block-special langue1'>\n$out_paras</div>\n";
  }
  function special_block_keyboardshortcuts($what,$options,$content) {
    # for formatting lists of keyboard shortcuts
    $lines = explode("\n",trim($content));
    $t = "<table class='block-special keyboard-shortcuts'>\n";
    foreach($lines as $line) {
      if( preg_match("/^(.*?)\s+---\s+(.*)$/",$line,$m) ) {
        [ $all, $combo, $desc ] = $m;
        $t .= "<tr><td class='combo'>$combo</td><td class='description'>$desc</td></tr>\n";
      } else {
        $t .= "<tr><td class='comment' colspan='2'>$line</td></tr>\n";
      }
    }
    $t .= "</table>\n";
    return $t;
  }
  function special_block_poem($what,$options,$content) {
    # format a poem
    $meta = [];
    foreach($this->meta as $k => $v) {
      if( preg_match('/^poem-(\w+)/',$k,$m) ) {
        $meta[$m[1]] = trim($v);
      }
    }
    $lines = explode("\n",trim($content));
    while( count($lines) > 0 && preg_match("/: /",$lines[0]) ) {
      [ $k, $v ] = explode(":",array_shift($lines),2);
      $v = trim($v);
      $meta[$k] = $v;
    }
    $content = implode("\n",$lines);
    $verses = preg_split('/\n{2,}/',trim($content));
    #$verses = array_map(function($x) { return preg_replace('/\*\*(.*?)\*\*/','<strong>\1</strong>',$x); },$verses);
    #$verses = array_map(function($x) { return preg_replace('/\*(.*?)\*/','<em>\1</em>',$x); },$verses);
    $verses = array_map(function($x) { return $this->parsedown->line($x); }, $verses);
    $verses = array_map(function($x) { return "<p class='verse'>$x</p>"; },$verses);
    $verses = implode("\n",$verses);
    if( count($meta) > 0 ) {
      $t = "<div class='meta poem-meta'>\n";
      foreach($meta as $k => $v) {
        $t .= "<div class='meta-item'><span class='key'>$k</span>: <span class='value'>$v</span></div>\n";
      }
      $t .= "</div>";
      $meta = $t;
    } else {
      $meta = "";
    }

    return "<div class='poem block-special'>\n".$meta.$verses."\n</div>";
  }
  function special_block_script($type,$options,$content) {
    # inline script
    if( $options !== "" ) $options = " ".$options;
    $s = "<script$options>\n".trim($content)."\n</script>\n";
    $hash = hash("sha256",$s);
    $phash = "<p>$hash</p>";
    array_push($this->protected_outer,[$phash,$s]);
    return $phash;
  }
  function special_block_style($type,$options,$content) {
    # inline css
    if( $options !== "" ) $options = " ".$options;
    $s = "<style$options>\n".trim($content)."\n</style>\n";
    $hash = hash("sha256",$s);
    $phash = "<p>$hash</p>";
    array_push($this->protected_outer,[$phash,$s]);
    return $phash;
  }
  function special_block_quotescr($type,$options,$content) {
    $paras = preg_split('/\n{2,}/',trim($content));
    $t = "<div class='block-special quotes-script'>\n";
    foreach($paras as $para) {
      if( preg_match('/^(\S+):\s+(.*)$/s',$para,$m) ) {
        [ $all, $who, $line ] = $m;
        $t .= "<div class='script-line with-who'><span class='who'>$who:</span> <span class='script-quote'>$line</span></div>\n";
      } else if( preg_match('/^---\s+(.*)$/s',$para,$m) ) {
        [ $all, $line ] = $m;
        $t .= "<div class='attribution'><span class='dashes'>&mdash;</span> <span class='quote-source'>$line</span></div>\n";
      } else {
        $t .= "<div class='script-comment'>$line</div>\n";
      }
    }
    $t .= "</div>\n";
    return $t;
  }
  function special_block_bible1($type,$options,$content) {
    # quotes
    $quotes = explode("\n\n",$content);
    $t = "<div class='block-special bible1'>\n";
    foreach($quotes as $quote) {
      if( preg_match("/^(.*)\s+---\s+(.*?)$/s",$quote,$m) ) {
        [ $all, $text, $verseref ] = $m;
        $text = trim($text);
      } else {
        $verseref = trim($options);
        $text = trim($quote);
      }
      if( $verseref !== "" ) {
        $ftext = $this->parsedown->text($text);
        $ftext = preg_replace('/^<p>/',"",$ftext);
        $ftext = preg_replace('@</p>$@',"",$ftext);
        $ftext = preg_replace('/((\d+:)?\d+)/','<span class="verse-number">\1</span>',$ftext);
        $t .= "<p class='quote'><span class='quote-text'>$ftext</span> &mdash; <span class='verseref'>$verseref</span></p>\n";
      } else {
        $ftext = $this->parsedown->text($quote);
        $ftext = preg_replace('/^<p>/',"",$ftext);
        $ftext = preg_replace('/((\d+:)?\d+)/','<span class="verse-number">\1</span>',$ftext);
        $t .= "<p class='quote'><span class='quote-text'>$ftext</span></p>\n";
      }
    }
    $t .= "</div>";
    return $t;
  }
  function special_block_quotes1($type,$options,$content) {
    # quotes
    $quotes = explode("\n\n",$content);
    $t = "<div class='block-special quotes1'>\n";
    $opts = preg_split('/\s+/',trim($options));
    $noquotes = false;
    $xs = [];
    foreach($opts as $opt) {
      if( $opt === "noquotes" ) {
        $noquotes = true;
      } else {
        array_push($xs,$opt);
      }
    }
    $options = implode(" ",$xs);
    if( $noquotes ) {
      $openquote = "";
      $closequote = "";
    } else {
      $openquote = "<span class='quote-mark'>&#x201C;</span>";
      $closequote = "<span class='quote-mark'>&#x201D;</span>";
    }
    foreach($quotes as $quote) {
      if( preg_match("/^(.*)\s+---\s+(.*?)$/s",$quote,$m) ) {
        [ $all, $text, $author ] = $m;
        $text = trim($text);
      } else {
        $author = trim($options);
        $text = trim($quote);
      }
      if( $author !== "" ) {
        $text = $this->parsedown->line($text);
        $author = $this->parsedown->line($author);
        $t .= "<p class='quote'>$openquote<span class='quote-text'>$text</span>$closequote &mdash; <span class='author'>$author</span></p>\n";
      } else {
        $quote = $this->parsedown->line($quote);
        $t .= "<p class='quote'>$openquote<span class='quote-text'>$quote</span>$closequote</p>\n";
      }
    }
    $t .= "</div>";
    return $t;
  }
  function special_block_quotes2($type,$options,$content) {
    # quotes
    $quotes = explode("\n\n",$content);
    $t = "<div class='block-special quotes2'>\n";
    foreach($quotes as $quote) {
      if( preg_match("/^(.*)\s+---\s+(.*?)$/s",$quote,$m) ) {
        [ $all, $text, $author ] = $m;
        $text = trim($text);
      } else {
        $author = trim($options);
        $text = trim($quote);
      }
      if( $author !== "" ) {
        $t .= "<p class='quote'><span class='quote-mark'>&#x201C;</span><span class='quote-text'>$text</span><span class='quote-mark'>&#x201D;</span> &mdash; <span class='author'>$author</span></p>\n";
      } else {
        $t .= "<p class='quote'><span class='quote-mark'>&#x201C;</span><span class='quote-text'>$quote</span><span class='quote-mark'>&#x201D;</span></p>\n";
      }
    }
    $t .= "</div>";
    return $t;
  }
  function special_block_bookquote($type,$options,$content) {
    # quotes
    $attrib = trim($options);
    $paras = preg_split('/\n{2,}/',trim($content));
    $t = "<div class='block-special bookquote'>\n";
    foreach($paras as $para) {
      $h = $this->parsedown->text($para);
      $h = preg_replace('@^<p>(.*)</p>$@s','\1',$h);
      $t .= "<div class='para'>$h</div>\n";
    }
    $t .= "<div class='attrib'>$attrib</div>\n";
    $t .= "</div>";
    return $t;
  }
  function special_block_plain($type,$options,$content)  {
    # plain text, do not turn into pre code, but use a pre to preserve whitespace
    $content = preg_replace('/^\s+$/m','',$content);
    $paras = preg_split('/\n{2}/',trim($content));
    $xs = explode(":",$options,2);
    $cls = "plain";
    if( count($xs) === 2 ) {
      $cs = trim($xs[0]);
      if( $cs !== "" ) {
        $cls .= " ".$cs;
      }
      $opts = trim($xs[1]);
    } else {
      $opts = trim($options);
    }
    if( $opts !== "" ) { $opts = " ".$opts; }
    $t = "";
    foreach($paras as $para) {
      $t .= "<div class='plain-para'>$para</div>\n";
    }
    return "<div class='$cls'$opts>$t\n</div>";
  }
  function special_block_abc($type,$options,$content) {
    # sheet music in ABC notation
    $this->uses["abc"] = true; 
    $content = trim($content);
    return "<div class='abc'>\n$content\n</div>\n";
  }
  function special_block_abcd($type,$options,$content) {
    # sheet music in ABC notation with default options
    $options = trim($options);
    $content = trim($content);
    $a = "";
    $a .= "X:1\n";
    $a .= "L:1/4\n";
    if( $options !== "" ) {
      $a .= "T:".$options."\n";
    }
    $a .= "M:4/4
K:C
$content";
    return $this->special_block_abc("abc","",$a);
  }
  function special_block_csvsh($what,$options,$content) {
    $content = "csvhead: Shortcut|Action\n$content";
    $options = "cs sep=| $options";
    return $this->special_block_csv($what,$options,$content);
  }
  function special_block_csv($what,$options,$content) {
    $meta = $this->meta;
    if( trim($content) === "" ) return "";
    $opts = preg_split('/\s+/',trim($options));
    $classes = [];
    $sep = ",";
    $ncols = PHP_INT_MAX;
    foreach($opts as $opt) {
      switch($opt) {
      case "cs":
        array_push($classes,"shadow");
        array_push($classes,"centre");
        break;
      case "csb":
        array_push($classes,"shadow");
        array_push($classes,"centre");
        array_push($classes,"border1");
        break;
      case "shadow":
      case "border":
      case "centre":
      case "vert":
      case "hpad":
      case "lnb":
        array_push($classes,$opt);
        break;
      default:
        if( preg_match('/^cols=(\d+)$/',$opt,$m) ) {
          $ncols = intval($m[1]);
        }
        if( preg_match('/^sep=(.+)$/',$opt,$m) ) {
          $sep = $m[1];
        }
      }
    }
    $classes = implode(" ",$classes);
    if( $classes ) $classes = " ".$classes;
    $t = "<div class='csvtable'><table class='csv$classes'>\n";
    $lines = explode("\n",$content);
    if( preg_match('/^csvhead:\s*(.*)$/',$lines[0],$m) ) {
      array_shift($lines);
      $heads = trim($m[1]);
      $heads = explode($sep,$heads,$ncols);
      $tr = "<tr>";
      foreach($heads as $cell) {
        $cell = trim($cell);
        $cellfmt = $this->parsedown->line($cell);
        $th = "<th>$cellfmt</th>";
        $tr .= $th;
      }
      $tr .= "</tr>";
      $t .= "<thead>\n$tr\n</thead>\n";
    } else if( isset($meta["csvhead"]) ) {
      $heads = trim($meta["csvhead"]);
      # the separator in the page meta is hardwired to ,
      # this is because if we want to use a different
      # sep for one table out of many, stuff would
      # break
      $heads = explode(",",$heads); 
      $tr = "<tr>";
      foreach($heads as $cell) {
        $cell = trim($cell);
        $cellfmt = $this->parsedown->line($cell);
        $th = "<th>$cellfmt</th>";
        $tr .= $th;
      }
      $tr .= "</tr>";
      $t .= "<thead>\n$tr\n</thead>\n";
    }
    $rulenext = false;
    $tbody = "<tbody>\n";
    foreach($lines as $line) {
      $line = str_replace("\\,","&comma;",$line); # for escaping commas
      if( preg_match('/^-{3,}\s*$/',$line) ) {
        $rulenext = true;
        continue;
      }
      $cells = explode($sep,$line,$ncols);
      if( $rulenext ) {
        $tr = "<tr class='rule-before'>";
        $rulenext = false;
      } else {
        $tr = "<tr>";
      }
      foreach($cells as $cell) {
        $cell = trim($cell);
        $cell = str_replace("&comma;",",",$cell);
        $cellfmt = $this->parsedown->line($cell);
        $td = "<td>$cellfmt</td>";
        $tr .= $td;
      }
      $tr .= "</tr>";
      $tbody .= "$tr\n";
    }
    $tbody .= "</tbody>";
    $t .= "$tbody\n";
    $t .= "</table></div>";
    return $t;
  }

  ### END BLOCK SPECIALS
  # Renderer
  function render($source,$options,$meta=[],$wikiwords=true) {
    $this->options = &$options;
    $this->meta = &$meta;
    $this->included = [];

    if( array_key_exists("wikiwords",$this->options) ) {
      $wikiwords = truthy($this->options["wikiwords"]);
    }

    $x = $this->process_include($source);
    $x = preg_replace('/^(\s*)\+\s+(.*?):/m','\1* **\2:**',$x);
    # protect from WikiWords and transform things like [[these]]
    #

    $protect = new ProtectRegex();
    $this->protect = $protect;
    
    # protect->add_block("nohightlight",$callback,"nohighlight blocks);
    $protect->add("/^(```nohighlight\\s(.*?)^```)/ms",function($match) {
              return "<div class='nohightlight'>$match[2]</div>"; });
    # options need to be true/false/auto
    # if true or auto, add this rule
    # use($options) and if pattern happens, set $options[$abc"] = "true"
    # has issues for truthy
    # later replace this with a more flexible
    # and extensible block transfers system
    # ```blocktype args....\n\n```
    # ```blocktype(XX) args....\n\nXX``` # arbitrary delimiter
    
    $this->protected_outer = [];
    $uses = &$this->uses;
    $protect->add('/^(```+)(.*?)?^\1/ms',function($match) use(&$uses) { 
      [ $block ] = $match;
      if( ! preg_match('/^(```+)(\S+)(.*?)$(.*)^\1/ms',$block,$m) ) {
        return $block;
      }
      [ $all, $ticks, $what, $options, $content ] = $m;
      $content = trim($content);
      $options = trim($options);

      if( preg_match('/^[A-Za-z]/',$what) ) {
        # if a block corresponds to a block special method, use that
        if( method_exists($this,$method="special_block_$what") ) {
          $block = $this->$method($what,$options,$content);
          $block .= "\n";
        }
        # if not we pass through
      } else {
        # if a block contains non [a-zA-Z] as first char we get here
        # e.g. ```!c
        $what_e = preg_replace('/^[a-zA-Z0-9_\.@#?-]/','_',$what);
        $all = htmlentities($all);
        $options_e = htmlentities($options);
        return "<div class='special block-special client-side-block' special='$what_e'><span class='options'>$options_e</span><div class='block-content'>$content</div></div>";
      }

      return $block;
    });

    $protect->add('/(`+)(.*?)\\1/');
    $re_bracket = '/\\\\\\[.*?\\\\\\]/s';
    $re_paren = '/\\\\\\(.*?\\\\\\)/s';
    $options["math"] = false;
    # worth adding a 'uses' flag, like 'math', so that
    # protect will handle adding entries indicating what's been used
    
    $protect->add($re_bracket,function($m) use(&$uses) { $uses["math"] = true; return $m[0];});
    $protect->add($re_paren,function($m) use(&$uses) { $uses["math"] = true; return $m[0];});

    $protect->add('@<a .*?</a>@is',function($match) { return $match[0]; } );
    //$source = preg_replace_callback(BIBLE_REGEX,[$this,"protect_bible"],$source);
    $protect->add(BIBLE_REGEX,function($match) {
      [$m,$ref,$text] = $match;
      return "<p class='bible_quote'><span class='ref'>$ref</span>&nbsp;<span class='text'>$text</span></p>";
    });
    $protect->add(HEADER_REGEX);
    //$protect->add(YOUTUBE_REGEX);
    $this->special_inline_shorthands = [
      "y" => "youtube"
    ];
    $protect->add(DBL_BRACKET_LINK_REGEX,function($match) use(&$uses) { 
      $a = $match[1];
      $b = explode(":",$a,2);
      if( preg_match('/^([^:]+):(.*)$/',$a,$m) ) {
        $what = $m[1];
        $args = $m[2];
        if( array_key_exists($what,$this->special_inline_shorthands) ) {
          $what = $this->special_inline_shorthands[$b[0]];
        }
        if( preg_match('/^[A-Za-z]/',$what) ) {
          if( method_exists($this,$method="special_inline_$what") ) {
            return $this->$method($what,$args);
          }
        } else {
          $what_e = preg_replace('/^[a-zA-Z0-9_\.@#?-]/','_',$what);
          $all = htmlentities($a);
          return "<span class='special inline-special client-side-inline' special='$what_e'>$all</span>";
        }
      }
      # we fall through if the special isn't matched
      $a_encoded = urlencode($a);
      $a_encoded = str_replace("%2F","/",$a_encoded); # we don't want to escape slashes in links
      return "[$a]($a_encoded)";
    });
    $protect->add(MD_IMGLINK_CLASS_REGEX,function($m) {
      [ $all, $alt, $cls, $src ] = $m;
      $classes = preg_split('/\s+/',trim($cls));
      if( in_array("caption",$classes) ) {
        $alt = preg_replace("/'/",'"',$alt);
        $cls = preg_replace("/[^a-zA-Z_ -]/",'_',$cls);
        return "<div class='img-box-container'><div class='img-box $cls'><img class='ptimg' alt='$alt' src='$src'/><br/><div class='caption'>$alt</div></div></div>";
      } else {
        $alt = preg_replace("/'/",'"',$alt);
        $cls = preg_replace("/[^a-zA-Z_ -]/",'_',$cls);
        return "<img class='ptimg $cls' alt='$alt' src='$src'/>";
      }
    });
    $protect->add(MD_IMGLINK_REGEX);
    $protect->add(MD_LINK_QUOTE_REGEX,function($match) {
        pre_dump("Link quote regex",$match);
        return "[$match[1]]($match[2])"; });
    $protect->add(NON_LINK_REGEX, function($match) {
      return $match[1];
        });
    $protect->add(MD_LINK_REGEX);
    $protect->add(URL_REGEX);
    $protect->add(BRACES_REGEX, function($match) { return $match[1]; });

    $x = $protect->do_protect($x);
    # do_protect will do all other transforms even if we don't want WikiWords
    # apply WikiWord transform
    if( $wikiwords ) {
      # TODO we're going to replace WikiWords with some client-side Javascript
      $x = preg_replace_callback(WIKIWORD_REGEX,[$this,"WikiWord_to_link"],$x);
    }
    # unprotect
    $x = $protect->un_protect($x);

    # protect from Parsedown
    $protect = new ProtectRegex();
    
    if( $this->get_option_bool("abc",true) ) {
      $protect->add("/^(?:```abc\\s(.*?)^```)/ms",function($match) {
        return "<div class='abc'>\n$match[1]\n</div>";
      });
    }
    if( $this->get_option_bool("math",true) ) {
      $re_bracket = '/\\\\\\[.*?\\\\\\]/s';
      $re_paren = '/\\\\\\(.*?\\\\\\)/s';
      $protect->add($re_bracket);
      $protect->add($re_paren);
    }

    $x = $protect->do_protect($x);

    # apply Parsedown->text
    $x = $this->parsedown->text($x);

    # unprotect
    $x = $protect->un_protect($x);

    $p = array_reverse($this->protected_outer);
    foreach($p as $q) {
      [ $hash, $s ] = $q;
      $x = str_replace($hash,$s,$x);
    }

    # done
    return $x;
  }

  /// DIR STUFF
  function fmt_linkify($x) {
     return "[$x](".urlencode($x).")";
  }
  function fmt_dir_ol($xs) {
    $t = "\n";
    foreach($xs as $x) {
      $t .= "1. ".$this->fmt_linkify($x)."\n";
    }
    $t .= "\n\n";
    #var_dump($t);
    return $t;
  }
  function fmt_dir_ul($xs) {
    $t = "\n";
    foreach($xs as $x) {
      $t .= "* ".$this->fmt_linkify($x)."\n";
    }
    $t .= "\n\n";
    #var_dump($t);
    return $t;
  }
  function makeDirOf($path,$opts) {
    $storage = $this->wiki->storage;
    $dirname = dirname($path);
    if( $dirname == "." ) { $dirname = ""; }

    
    $dirhandler = new WikiHandlerDir($this->wiki);
    $dirhandler->get_dir_contents();
    $dirs = $dirhandler->dirs;
    $pages = $dirhandler->pages;
    $files = $dirhandler->files;
    $result = [];
    $o = ["pages"=>false,"dirs"=>false,"files"=>false,"images"=>false,"regex"=>null,"fmt"=>null,"except"=>null];
    $os = preg_split("/\s*,\s*/",trim($opts));
    
    foreach($os as $ox) {
      $xs = explode("=",$ox,2);
      if( count($xs) == 1 ) {
        array_push($xs,true);
      } else {
        if( ! preg_match('/^(regex|except)$/',$xs[0]) ) {
          $xs[1] = truthy($xs[1],false);
        }
      }
      $o[$xs[0]] = $xs[1];
    }
    if( $o["pages"] ) {
      foreach($pages as $page) {
        $page = preg_replace('/\.ptmd$/','',$page);
        if( is_string($o['except']) && preg_match("/".$o['except']."/",$page ) ) {
          continue;
        }
        if( is_string($o['regex']) ) {
          if( preg_match("/".$o['regex']."/",$page ) ) {
            array_push($result,"[$page](".urlencode($page).")");
          }
        } else {
          array_push($result,"[$page](".urlencode($page).")");
        }
      }
    }
    if( $o["dirs"] ) {
      foreach($dirs as $dir) {
        if( is_null($o['regex']) || preg_match("/".$o['regex']."/",$dir ) ) {
          array_push($result,"[$dir](".urlencode($dir).")");
        }
      }
    }
    if( $o["files"] ) {
      foreach($files as $file) {
        if( is_null($o['regex']) || preg_match("/".$o['regex']."/",$file ) ) {
          array_push($result,"[$file](".urlencode($file).")");
        }
      }
    }
    if( $o["images"] ) {
      foreach($files as $file) {
        if( preg_match(IMAGE_REGEX,$file) ) {
          if( is_null($o['regex']) || preg_match("/".$o['regex']."/",$file ) ) {
            array_push($result,"[$file](".urlencode($file).")");
          }
        }
      }
    }
    $fmt = "fmt_dir_".$o['fmt'];
    if( method_exists($this,$fmt) ) {
      #var_dump($this->$fmt($result));
      return $this->$fmt($result);
    }
    return implode(" ",$result);
  }
}
