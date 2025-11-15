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

require_once("breadcrumbs.php");
if( ! isset($navbar_rendered) ) {
  $navbar_rendered = "";
}
if( ! isset($page_rendered) ) {
  $page_rendered = "no page";
}
if( ! isset($headerTitle) ) {
  $headerTitle = "Header Title";
}

if( is_mobile )  {
  require(TEMPLATES_DIR."/".THEME."/TemplateView_Mobile.php");
} else {
  require(TEMPLATES_DIR."/".THEME."/TemplateView.php");
}
