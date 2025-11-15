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
require_once("defs_ptmd.php"); ## constants like REGEX's

# Parser for PTMD -- most of the work is done by Parsedown
# Block and inline specials are defined in a subclass.
class PTMDBase extends stdclass {
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
  # Renderer
  function render($source,$options,$meta=[],$wikiwords=true) {
    $this->options = &$options;
    $this->meta = &$meta;
    $this->included = [];

    ### Pre Pre Process
    $x = $this->process_include($source);
    $x = preg_replace('/^(\s*)\+\s+(.*?):/m','\1* **\2:**',$x);

    ## Pre Processs: Convert Wikiwords and pre-Parsedown
    $protect = new ProtectRegex();
    
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
