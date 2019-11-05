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
        // Returns TRUE if the page should appear on the map, and FALSE if it should be hidden.
        'showInSitemap' => function(){
            // if the page is the Error Page, it should be hidden:
            if($this->isErrorPage()){ return false; }
            // if the page's mode is 'hide', it should be hidden:
            if($this->sitemapMode() == 'hide'){ return false; }
            // if any of the page's parents' mode is 'hide', it should also be hidden:
            foreach ($this->parents() as $parent) {
                if($parent->sitemapMode() == 'hide'){ return false; }
            }

            // if page has 'sitemap' field, it determines whether the page should be included:
            if($this->sitemap()->exists() and !$this->sitemap()->toBool()) { return false; }

            // last of all, we apply any user-defined filter:
            $filter = kirby()->option('cre8ivclick.sitemapper.pageFilter');
            if(is_callable($filter) and !$filter($this)){
                return false;
            } else {
                // otherwise, we assume the page SHOULD be included:
                return true;
            }
        },
        // this function returns a list of all images that need to be added to the sitemap,
        // for the current page. It includes the page's own images, as well as the images of
        // any children with sitemap option set to 'images' - recursively.
        'sitemapPageImages' => function(){
            $images = [];
            foreach ($this->images() as $img) { $images[] = $img->url(); }
            foreach ($this->children()->published() as $child) {
                if($child->showInSitemap() and $child->sitemapMode() == 'images'){
                    $images = array_merge($images,$child->sitemapPageImages());
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
        // only one entry for the page.
        'sitemapPageArray' => function(){
            $pgMap = [];
            $mode = $this->sitemapMode();
            switch ($mode) {
                // if sitemap mode is 'hide' or 'images', we don't need to add anything to the map:
                case 'hide':
                case 'images':
                    break;
                // 'show' is the default: the site's own 'languages' setting determines
                // whether the page is multilingual or not:
                case 'show':
                    if(kirby()->options('languages',false)){
                        // site is a multilingual site:
                        foreach (kirby()->languages() as $lang) {
                            $code = $lang->code();
                            $url = $this->url($code);
                            $pgMap[$url]['mod'] = $this->modified('c','date');
                            $pgMap[$url]['lang'] = [];
                            foreach (kirby()->languages() as $l) {
                                $pgMap[$url]['lang'][$l->code()]['locale'] = $l->locale()[0];
                                $pgMap[$url]['lang'][$l->code()]['url'] = $this->url($l->code());
                            }
                            // add the 'default' language fallback:
                            $code = kirby()->defaultLanguage()->code();
                            $pgMap[$url]['lang']['x-default']['locale'] = 'x-default';
                            $pgMap[$url]['lang']['x-default']['url'] = $this->url($code);
                            // add page's images:
                            $pgMap[$url]['images'] = $this->sitemapPageImages();
                        }
                    } else {
                        // site is a single-language site:
                        $url = $this->url();
                        $pgMap[$url]['mod'] = $this->modified('c','date');
                        $pgMap[$url]['lang'] = []; // empty array == no language alternatives
                        // add page's images:
                        $pgMap[$url]['images'] = $this->sitemapPageImages();
                    }
                    break;
                // if we get to here, then the sitemap contains a language code.
                // this means that this is a single-language page in a multilingual site:
                default:
                    $code = $mode;
                    $url = $this->url($code);
                    $pgMap[$url]['mod'] = $this->modified('c','date');
                    $pgMap[$url]['lang'] = []; // empty array == no language alternatives
                    $pgMap[$url]['images'] = $this->sitemapPageImages();
                    break;
            }
            // lastly, we iterate recursively through the children:
            foreach ($this->children()->published() as $child) {
                if($child->showInSitemap()) {
                    $pgMap = array_merge_recursive($pgMap,$child->sitemapPageArray());
                }
            }
            return $pgMap;
        }
    ],

    'fileMethods' => [
        'showInSitemap' => function(){
            // if file bluprint has 'sitemap' field, it determines whether the page should be included:
            if($this->sitemap()->exists()){
                return $this->sitemap()->toBool();
            } else {
                // otherwise, we assume the image SHOULD be included:
                return true;
            }
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
                    if($p->showInSitemap()) {
                        $map = array_merge_recursive($map,$p->sitemapPageArray());
                    }
                }
                // make sure the collection includes the Home Page:
                // $map = array_merge($map,site()->homePage()->sitemapPageArray());

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
