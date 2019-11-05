<?php
Kirby::plugin('cre8ivclick/sitemapper', [
    'options' => [
        'pageFilter' => false
    ],

    'pageMethods' => [
        // Function used to determine the 'mode' of the page, set via blueprint option.
        // Returns a string: the sitemap mode, as set in the blueprint, or 'show' (default).
        'sitemapMode' => function(){
            // if we don't have 'sitemap' options at all, then use the default 'show':
            if(!isset($this->blueprint()->options()['sitemap'])){
                return 'show';
            // if we have 'sitemap' options but it's not an array, then it must be a 'mode':
            } elseif(!is_array($this->blueprint()->options()['sitemap'])){
                return $this->blueprint()->options()['sitemap'];
            // if we have a 'sitemap' array, then let's not assume it has a 'mode' key - we
            // must provide a 'show' default, in case the user has just set a 'lang':
            } else {
                $options = array_merge(['mode'=>'show'],$this->blueprint()->options()['sitemap']);
                return $options['mode'];
            }
        },
        // Function which calculates whether a page should appear or not in the sitemap.
        // Calculation is based on whether the page or any of its parents has a 'hide' mode
        // in their blueprint, as well as on whether the page has a 'sitemap' field with a
        // boolean value of false (a toggle to hide the specific page).
        // Returns TRUE if the page should appear on the map, and FALSE if it should be hidden.
        'showInSitemap' => function(){
            // if the page's mode is 'hide', it should be hidden:
            if($this->sitemapMode() == 'hide'){ return false; }
            // if any of the page's parents' mode is 'hide', it should also be hidden:
            foreach ($this->parents() as $parent) {
                if($parent->sitemapMode() == 'hide'){ return false; }
            }
            // if page has 'sitemap' field, it determines whether the page should be included:
            if($this->sitemap()->exists()){
                return $this->sitemap()->toBool();
            } else {
                // otherwise, we assume the page SHOULD be included:
                return true;
            }

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
                // get complete list of all published pages in the site, except Error Page:
                $pages = site()->pages()->index()->published()->not(site()->errorPage());
                // make sure the collection includes the Home Page:
                if(!$pages->has(site()->homePage())){ $pages->add(site()->homePage()); }
                // if the user has setup a special filtering function, we run it:
                $filter = kirby()->option('cre8ivclick.sitemapper.pageFilter');
                if(is_callable($filter)){
                    $pages = $pages->filter($filter);
                }
                // remove all hidden pages:
                $pages = $pages->filter(function($p){
                    return $p->showInSitemap();
                });

                $map = [];
                foreach ($pages as $p) {
                    // add images to their appropriate pages:
                    if($p->sitemapMode() == 'images'){
                        foreach($p->images() as $img){
                            if($img->showInSitemap()){ $map[$p->parent()->id()]['images'][] = $img; }
                        }
                    } else {
                        $map[$p->id()]['t'] = $p->translations();
                        $map[$p->id()]['url'] = $p->url();
                        $map[$p->id()]['mod'] = $p->modified('c','date');
                        $map[$p->id()]['images'] = [];
                        foreach($p->images() as $img){
                            if($img->showInSitemap()){ $map[$p->id()]['images'][] = $img; }
                        }
                    }
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
        ]
    ],

    // our XML and XSL templates:
    'snippets' => [
        'sitemapper/xml' => __DIR__ . '/snippets/xml.php'
    ]
]);
