<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($map as $id => $page): ?>
<url>
    <loc><?= $page['url'] ?></loc>
    <lastmod><?= $page['mod'] ?></lastmod>
    <dump>
        Page ID: <?= $id ?>

<?php  foreach(page($id)->translations() as $l): ?>
        <?= $l->code() ?> URL: <?= page($id)->url($l->code()) ?>

<?php endforeach; ?>
    </dump>
<?php foreach ($page['images'] as $img): ?>
    <image:image>
        <image:loc><?= $img->url() ?></image:loc>
    </image:image>
<?php endforeach; ?>
</url>
<?php endforeach; ?>
</urlset>
<!-- Sitemap generated using https://gitlab.com/cre8ivclick/sitemapper -->
