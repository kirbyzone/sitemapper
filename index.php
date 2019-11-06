<?php
Kirby::plugin('cre8ivclick/sitemapper', [
    'options' => [
        'pageFilter' => false
    ],

    'pageMethods' => [
        // Function used to determine the 'mode' of the page, set via blueprint option.
        // Returns a string: the sitemap mode, if set in the blueprint, or 'show' (default).
        'sitemapMode' => function(){
            return $this->blueprint()->options()['sitemap'] ?? 'show';
        },
        // Function which calculates whether a page should appear or not in the sitemap.
        // Calculation is based on whether the page or any of its parents has a 'hide' mode
        // in their blueprint, as well as on whether the page has a 'sitemap' field with a
        // boolean value of false (a toggle to hide the specific page).
        // Param $code should be the page's language code in a multilingual site. If no $code
        // is given, then the default language is used - e.g., in single-language sites.
        // Returns TRUE if the page should appear on the map, and FALSE if it should be hidden.
        'showInSitemap' => function($code = false){
            // if the page is the Error Page, it should be hidden:
            if($this->isErrorPage()){ return false; }
            // if the page's sitemap mode is 'hide', it should be hidden:
            if($this->sitemapMode() == 'hide'){ return false; }
            // if any of the page's parents' mode is 'hide', it should also be hidden:
            foreach ($this->parents() as $parent) {
                if($parent->sitemapMode() == 'hide'){ return false; }
            }
            // then, we apply any user-defined filter:
            $filter = kirby()->option('cre8ivclick.sitemapper.pageFilter');
            if(is_callable($filter) and !$filter($this)){ return false; }
            // finally, if the page has a 'sitemap' field, it should determine
            // whether the page should be included:
            if($this->sitemap()->exists()){
                if(!kirby()->options('languages',false)){
                    // site is single-language:
                    return $this->sitemap()->toBool();
                } else {
                    // site is multilingual:
                    if($code == false){ $code = kirby()->defaultLanguage()->code(); }
                    try {
                        $bool = $this->content($code)->sitemap();
                    } catch (Exception $error) {
                        $bool = $this->content(kirby()->defaultLanguage()->code())->sitemap();
                    }
                    return $bool->toBool();
                }

            }
            // otherwise, we assume the page SHOULD be included:
            return true;
        },
        // this function returns a list of all images that need to be added to the sitemap,
        // for the current page. It includes the page's own images, as well as the images of
        // any children with sitemap option set to 'images' - recursively.
        'sitemapPageImages' => function($code = false){
            $images = [];
            foreach ($this->images() as $img) {
                if($img->showInSitemap($code)){ $images[] = $img->url(); }
            }
            foreach ($this->children()->published() as $child) {
                if($child->sitemapMode() == 'images' and $child->showInSitemap($code)){
                    $images = array_merge($images,$child->sitemapPageImages($code));
                }
            }
            return $images;
        },
        // Recursive function returns all page info relevant to be added to the sitemap,
        // for the current page as well as all its children, as an array.
        // Returns an array in the following format:
        // [
        //      'http://example.com' => [                   // key is the page URL in the map
        //          'mod' => '2019-06-17T16:25:31+02:00',   // last modified date for the page
        //          'lang' => [                             // optional - alternative languages
        //              'en' => [                           // key is language code
        //                  'locale' => 'en-AU',            // locale of this version
        //                  'url'=> 'http://example.com/en' // url for this version
        //              ]
        //          ],
        //          'images' => []                          // list of images for the page
        //      ]
        // ]
        // If a page is multilingual, the array will have several entries - one for each URL
        // of the page, in each language. If a page is single-language, the array will have
        // only one entry for it.
        'sitemapPageArray' => function(){
            $pgMap = []; // we start with an empty map;
            $mode = $this->sitemapMode();
            if(kirby()->options('languages',false) and $mode == 'show') {
                // PAGE IS MULTILINGUAL
                // - i.e., it will have versions in all of the site's languages:
                foreach (kirby()->languages() as $lang) {
                    $code = $lang->code();
                    // check whether the page should be included in sitemap:
                    if(!$this->showInSitemap($code)){ continue; }
                    $url = $this->url($code);
                    $pgMap[$url]['mod'] = $this->modified('c','date');
                    $pgMap[$url]['lang'] = [];
                    foreach (kirby()->languages() as $l) {
                        $pgMap[$url]['lang'][$l->code()]['locale'] = $l->locale()[0];
                        $pgMap[$url]['lang'][$l->code()]['url'] = $this->url($l->code());
                    }
                    // add the 'default' language fallback:
                    $pgMap[$url]['lang']['x-default']['locale'] = 'x-default';
                    $pgMap[$url]['lang']['x-default']['url'] = $this->url(kirby()->defaultLanguage()->code());
                    // add page's images:
                    $pgMap[$url]['images'] = $this->sitemapPageImages($code);
                    foreach ($this->children()->published() as $child) {
                        if($child->sitemapMode() == 'images') {
                            $pgMap[$url]['images'] = array_merge($pgMap[$url]['images'], $child->sitemapPageImages($code));
                        }
                    }
                }
            } else {
                // PAGE IS SINGLE-LANGUAGE
                // - i.e., it should have only one version, in one language:
                // check whether page should be included in sitemap:
                if($this->showInSitemap()) {
                    if(kirby()->options('languages',false)){
                        // THIS IS A SINGLE-LANGUAGE PAGE IN A MULTILINGUAL SITE:
                        $code = $this->sitemapMode();
                        $url = $this->url($code);
                    } else {
                        // THIS IS A SINGLE-LANGUAGE SITE:
                        $code = false;
                        $url = $this->url();
                    }
                    $pgMap[$url]['mod'] = $this->modified('c','date');
                    $pgMap[$url]['lang'] = []; // empty array == no language alternatives
                    // add page's images:
                    $pgMap[$url]['images'] = $this->sitemapPageImages();
                    foreach ($this->children()->published() as $child) {
                        if($child->sitemapMode() == 'images'){
                            $pgMap[$url]['images'] = array_merge($pgMap[$url]['images'], $child->sitemapPageImages($code));
                        }
                    }
                }
            }
            // lastly, we iterate recursively through the children:
            foreach ($this->children()->published() as $child) {
                if($child->sitemapMode() != 'images') {
                    $pgMap = array_merge_recursive($pgMap,$child->sitemapPageArray());
                }
            }
            return $pgMap;
        }
    ],

    'fileMethods' => [
        'showInSitemap' => function($code = false){
            // if the image file blueprint has a 'sitemap' field, we use it to determine
            // whether the image should be included:
            if($this->sitemap()->exists()){
                if(!kirby()->options('languages',false)){
                    // site is single-language:
                    return $this->sitemap()->toBool();
                } else {
                    // site is multilingual:
                    if($code == false){ $code = kirby()->defaultLanguage()->code(); }
                    try {
                        $bool = $this->content($code)->sitemap();
                    } catch (Exception $error) {
                        $bool = $this->content(kirby()->defaultLanguage()->code())->sitemap();
                    }
                    return $bool->toBool();
                }

            }
            // otherwise, we assume the image SHOULD be included:
            return true;
        }
    ],

    'routes' => [
        [
            'pattern' => 'sitemap.xml',
            'action' => function(){
                // get list of all top-level published pages in the site:
                $pages = site()->pages()->published();
                // start with an empty array:
                $map = [];
                foreach ($pages as $p) {
                    $map = array_merge_recursive($map,$p->sitemapPageArray());
                }
                //build the xml document:
                $content = snippet('sitemapper/xml', ['map' => $map], true);
                // return response with correct header type
                return new Kirby\Cms\Response($content, 'application/xml');
            }
        ],
        [
            'pattern' => 'sitemap',
            'action'  => function(){
              return go('sitemap.xml', 301);
            }
        ],
        [
            'pattern' => 'sitemap.xsl',
            'action' => function(){
                //build the xml document:
                $data = snippet('sitemapper/xsl', ['settings' => 'data'], true);
                // return response with correct header type
                return new Kirby\Cms\Response($data, 'application/xslt+xml');
            }
        ]
    ],

    // our XML and XSL templates:
    'snippets' => [
        'sitemapper/xml' => __DIR__ . '/snippets/xml.php',
        'sitemapper/xsl' => __DIR__ . '/snippets/xsl.php'
    ]
]);
