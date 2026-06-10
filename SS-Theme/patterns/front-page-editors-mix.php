<?php
/**
 * Title: Front Page / Editors Mix
 * Slug: storysentry/front-page-editors-mix
 * Categories: storysentry-front-page
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-frame","layout":{"type":"constrained"}} -->
<main class="wp-block-group ss-frame"><!-- wp:group {"className":"ss-shell ss-query-stack","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-shell ss-query-stack"><!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"top"} -->
<div class="wp-block-columns are-vertically-aligned-top"><!-- wp:column {"verticalAlignment":"top","width":"62%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:62%"><!-- wp:group {"className":"ss-query-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-query-header"><!-- wp:paragraph {"className":"ss-page-kicker"} -->
<p class="ss-page-kicker">Front page layout 03</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"ss-page-title"} -->
<h1 class="wp-block-heading ss-page-title">Editors’ mix</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ss-page-subtitle"} -->
<p class="ss-page-subtitle">Lead with one hero story, then balance the rest in a compact editorial rail.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"perPage":1,"pages":0,"offset":0,"postType":"ss_story","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:storysentry/story-hero /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top","width":"38%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:38%"><!-- wp:group {"className":"ss-section-rule","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-section-rule"><!-- wp:paragraph {"className":"ss-section-label"} -->
<p class="ss-section-label">Editors’ notes</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":1,"postType":"ss_story","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"ss-story-list"} -->
<!-- wp:storysentry/story-card {"layout":"list","showImage":false,"showExcerpt":false} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:storysentry/ad-slot {"slot":"2"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"className":"ss-section-rule","layout":{"type":"constrained"}} -->
<div class="wp-block-group ss-section-rule"><!-- wp:paragraph {"className":"ss-section-label"} -->
<p class="ss-section-label">Latest stories</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"perPage":4,"pages":0,"offset":6,"postType":"ss_story","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"ss-card-grid"} -->
<!-- wp:storysentry/story-card {"layout":"grid","showExcerpt":false} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
