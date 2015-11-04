<?php

class CustomHomeTheme extends HomeTheme {
	public function display_page(Page $page, $sitename, $base_href, $theme_name, $body) {
    $site_link = make_link();
		$page->set_mode("data");
		$hh = "";
		$page->add_auto_html_headers();
		foreach($page->html_headers as $h) {$hh .= $h;}
    $h_search = "
      <div class='mdl-textfield mdl-js-textfield mdl-textfield--expandable
                  mdl-textfield--floating-label mdl-textfield--align-right'>
        <form action='{$site_link}' method='GET'>
          <label class='mdl-button mdl-js-button mdl-button--icon'
                 for='waterfall-exp'>
            <i class='material-icons'>search</i>
          </label>
          <div class='mdl-textfield__expandable-holder'>
            <input id='waterfall-exp' class='autocomplete_tags mdl-textfield__input' name='search' type='text' placeholder='Search' value='' />
            <input type='hidden' name='q' value='/post/list'>
            <input type='submit' value='Find' style='display: none;' />
          </div>
        </form>
      </div>
    ";
    $material_js = make_link('/themes/'.$theme_name.'/material.min.js?v1.0.5');
    $material_css = make_link('/themes/'.$theme_name.'/material.min.css?v1.0.5');
		$page->set_data(<<<EOD

    <!doctype html>
    <!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
    <!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
    <!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
    <!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$sitename}</title>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:regular,bold,italic,thin,light,bolditalic,black,medium&amp;lang=en">
    <link rel="stylesheet" href="//fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">
    <link rel="stylesheet" href="{$material_css}"  rel="stylesheet">
    $hh
    <script type="text/javascript" src="{$material_js}"></script>
    <!-- having conflicts this ensures the screens will not remain hidden \while the layout is adjusted -->
    </head>

    <body>

    <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header">
      <header class="mdl-layout__header mdl-layout__header--waterfall">
        <!-- Top row, always visible -->
        <div class="mdl-layout__header-row ">
          <!-- Title -->
          <span class="mdl-layout-title">
            <a class="mdl-logo" href="{$site_link}">{$sitename}</a>
          </span>
          <div class="mdl-layout-spacer"></div>
          $h_search
          <button id="menu-left-col-menu"
                  class="mdl-button mdl-js-button mdl-button--icon">
            <i class="material-icons">more_vert</i>
          </button>
        </div>
      </header>
      <main class="mdl-layout__content">
        <div class="mdl-grid">
          <div class="mdl-cell mdl-cell--12-col mdl-grid ">
            $body
          </div>
        </div>
      </main>
    </div>

    <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
        for="menu-left-col-menu">
      <li id="layout-top" class="mdl-menu__item">Layout Top</li>
      <li id="layout-right" class="mdl-menu__item">Layout Right</li>
      <li id="layout-bottom" class="mdl-menu__item">Layout Bottom</li>
      <li id="layout-left" class="mdl-menu__item">Layout Left</li>
    </ul>
    </body>
    </html>
EOD
);
	}

	public function build_body(/*string*/ $sitename, /*string*/ $main_links, /*string*/ $main_text, /*string*/ $contact_link, $num_comma, /*string*/ $counter_text) {
		$main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
		$message_html = empty($main_text)     ? "" : "<div class='space' id='message'>$main_text</div>";
		$counter_html = empty($counter_text)  ? "" : "<div class='space' id='counter'>$counter_text</div>";
		$contact_link = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a> &ndash;";
		$search_html = "
			<div class='space' id='search'>
				<form action='".make_link("post/list")."' method='GET'>
				<input name='search' size='30' type='search' value='' class='autocomplete_tags' autofocus='autofocus' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Search'/>
				</form>
			</div>
		";
		return "
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			$counter_html
			<div class='space' id='foot'>
				<small><small>
				$contact_link Serving $num_comma posts &ndash;
				Running <a href='http://code.shishnet.org/shimmie2/'>Shimmie</a>
				</small></small>
			</div>
		</div>";
	}
}
