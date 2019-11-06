<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
        version="1.0"
        xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:fo="http://www.w3.org/1999/XSL/Format"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns="http://www.w3.org/1999/xhtml">

    <xsl:output method="html" indent="yes" encoding="UTF-8"/>

    <xsl:template match="/">
        <html>
            <head>
                <title>
                    <?= site()->title()->html() ?> Sitemap
                    <xsl:if test="sm:sitemapindex">Index</xsl:if>
                </title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.2.2/dist/css/uikit.min.css" />
                <style>
                    h1 .uk-badge { margin-right: 10px; margin-top: 9px; padding: 9px 12px; }
                    button.toggle { padding: 0 6px; margin-right: 12px; line-height: 28px; }
                    div.toggle-content { padding: 18px 12px 0 36px;  }
                    span.content-icon { margin-right: 6px; }
                    span.content-tag { margin-left: 9px; }
                    tr { border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
                </style>
                <script src="https://cdn.jsdelivr.net/npm/uikit@3.2.2/dist/js/uikit.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/uikit@3.2.2/dist/js/uikit-icons.min.js"></script>
            </head>
            <body>
                <div class="uk-container">
                <h1 class="uk-heading-divider uk-margin-large-top">
                    <?= site()->title()->html() ?> Sitemap
                    <xsl:if test="sm:sitemapindex">Index</xsl:if>
                    <xsl:if test="sm:urlset/sm:url/mobile:mobile">
                        <span  class="uk-badge">mobile</span>
                    </xsl:if>
                    <xsl:if test="sm:urlset/sm:url/image:image">
                        <span  class="uk-badge">images</span>
                    </xsl:if>
                    <xsl:if test="sm:urlset/sm:url/news:news">
                        <span  class="uk-badge">news</span>
                    </xsl:if>
                    <xsl:if test="sm:urlset/sm:url/video:video">
                        <span  class="uk-badge">videos</span>
                    </xsl:if>
                    <xsl:if test="sm:urlset/sm:url/xhtml:link">
                        <span  class="uk-badge">alternates</span>
                    </xsl:if>
                </h1>
                <p>
                    Sitemaps are used by search engines to find and classify the content of you website - more information at <a href="https://sitemaps.org">sitemaps.org</a>. This page displays the sitemap after it has been transformed into a more human-readable format.
                    <xsl:choose>
                        <xsl:when test="sm:sitemapindex">
                            This sitemap index file contains
                            <strong><xsl:value-of select="count(sm:sitemapindex/sm:sitemap)"/></strong>
                            sitemaps.
                        </xsl:when>
                        <xsl:otherwise>
                            This sitemap contains
                            <strong><xsl:value-of select="count(sm:urlset/sm:url)"/></strong>
                            URLs.
                        </xsl:otherwise>
                    </xsl:choose>
                </p>

                <xsl:apply-templates/>
            </div>
            <script type="text/javascript">
                var elements = document.querySelectorAll('button.toggle');
                var pos;
                elements.forEach(function(el){
                    el.addEventListener('beforeshow', function(e){
                        pos = e.target.scrollTop;
                        console.log('beforeshow processed');
                    });
                    el.addEventListener('shown', function(e){
                        e.target.scrollTop = pos;
                    });
                    el.addEventListener('beforehide', function(e){
                        pos = e.target.scrollTop;
                    });
                    el.addEventListener('hidden', function(e){
                        e.target.scrollTop = pos;
                    });
                });
            </script>
            </body>
        </html>
    </xsl:template>


    <xsl:template match="sm:sitemapindex">
        <table class="uk-table uk-table-hover">
            <tr>
                <th></th>
                <th>URL</th>
                <th>Last Modified</th>
            </tr>
            <xsl:for-each select="sm:sitemap">
                <tr>
                    <xsl:variable name="loc">
                        <xsl:value-of select="sm:loc"/>
                    </xsl:variable>
                    <xsl:variable name="pno">
                        <xsl:value-of select="position()"/>
                    </xsl:variable>
                    <td>
                        <xsl:value-of select="$pno"/>
                    </td>
                    <td>
                        <a href="{$loc}">
                            <xsl:value-of select="sm:loc"/>
                        </a>
                    </td>
                    <xsl:apply-templates/>
                </tr>
            </xsl:for-each>
        </table>
    </xsl:template>

    <xsl:template match="sm:urlset">
        <table class="uk-table uk-table-hover uk-table-small">
            <tr>
                <th></th>
                <th>URL</th>
                <xsl:if test="sm:url/sm:lastmod">
                    <th>Last Modified</th>
                </xsl:if>
                <xsl:if test="sm:url/sm:changefreq">
                    <th>Change Frequency</th>
                </xsl:if>
                <xsl:if test="sm:url/sm:priority">
                    <th>Priority</th>
                </xsl:if>
            </tr>
            <xsl:for-each select="sm:url">
                <tr>
                    <xsl:variable name="loc">
                        <xsl:value-of select="sm:loc"/>
                    </xsl:variable>
                    <xsl:variable name="pno">
                        <xsl:value-of select="position()"/>
                    </xsl:variable>
                    <td>
                        <xsl:value-of select="$pno"/>
                    </td>
                    <td>
                        <button type="button" class="uk-button uk-button-default toggle" uk-toggle="target: .toggle-{$pno}; animation: uk-animation-slide-top-small">
                        <span class="toggle-{$pno}" uk-icon="icon: triangle-down"></span>
                        <span class="toggle-{$pno}" uk-icon="icon: triangle-up" hidden="true"></span>
                        </button>
                        <a href="{$loc}">
                            <xsl:value-of select="sm:loc"/>
                        </a>
                        <div class="toggle-{$pno} toggle-content"  hidden="true">
                            <xsl:apply-templates select="xhtml:*"/>
                            <xsl:apply-templates select="image:*"/>
                            <xsl:apply-templates select="video:*"/>
                        </div>
                    </td>
                    <xsl:apply-templates select="sm:*"/>
                </tr>
            </xsl:for-each>
        </table>
    </xsl:template>

    <xsl:template match="sm:loc|image:loc|image:caption|video:*">
    </xsl:template>

    <xsl:template match="sm:lastmod|sm:changefreq|sm:priority">
        <td>
            <xsl:apply-templates/>
        </td>
    </xsl:template>

    <xsl:template match="xhtml:link">
        <xsl:variable name="altloc">
            <xsl:value-of select="@href"/>
        </xsl:variable>
        <p>
            <span class="content-icon" uk-icon="icon: file-text"></span>
            <a href="{$altloc}">
                <xsl:value-of select="@href"/>
            </a>
            <span class="uk-text-meta content-tag">
                <xsl:value-of select="@hreflang"/>
            </span>
        </p>
        <xsl:apply-templates/>
    </xsl:template>
    <xsl:template match="image:image">
        <xsl:variable name="loc">
            <xsl:value-of select="image:loc"/>
        </xsl:variable>
        <p>
            <span class="content-icon" uk-icon="icon: image"></span>
            <a href="{$loc}">
                <xsl:value-of select="image:loc"/>
            </a>
            <span>
                <xsl:value-of select="image:caption"/>
            </span>
            <xsl:apply-templates/>
        </p>
    </xsl:template>
    <xsl:template match="video:video">
        <xsl:variable name="loc">
            <xsl:choose>
                <xsl:when test="video:player_loc != ''">
                    <xsl:value-of select="video:player_loc"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="video:content_loc"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <p>
            Video:
            <a href="{$loc}">
                <xsl:choose>
                    <xsl:when test="video:player_loc != ''">
                        <xsl:value-of select="video:player_loc"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="video:content_loc"/>
                    </xsl:otherwise>
                </xsl:choose>
            </a>
            <span>
                <xsl:value-of select="video:title"/>
            </span>
            <span>
                <xsl:value-of select="video:thumbnail_loc"/>
            </span>
            <xsl:apply-templates/>
        </p>
    </xsl:template>
</xsl:stylesheet>
