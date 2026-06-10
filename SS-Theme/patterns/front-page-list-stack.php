<?php
/**
 * Title: Front Page / List Stack
 * Slug: storysentry/front-page-list-stack
 * Categories: storysentry-front-page
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-frame","layout":{"type":"constrained"}} -->
<main class="wp-block-group ss-frame"><!-- wp:group {"className":"ss-shell ss-query-stack","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-shell ss-query-stack"><!-- wp:group {"className":"ss-query-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-query-header"><!-- wp:paragraph {"className":"ss-page-kicker"} -->
<p class="ss-page-kicker">Front page layout 02</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"ss-page-title"} -->
<h1 class="wp-block-heading ss-page-title">News river</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ss-page-subtitle"} -->
<p class="ss-page-subtitle">A denser list-first layout for high-volume days and faster scanning.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"perPage":1,"pages":0,"offset":0,"postType":"ss_story","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:storysentry/story-hero /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:query {"query":{"perPage":10,"pages":0,"offset":1,"postType":"ss_story","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"ss-feed-list"} -->
<!-- wp:storysentry/story-card {"layout":"list"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
