<?php
/**
 * Title: Single / Story Summary
 * Slug: storysentry/single-story
 * Categories: storysentry-single
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-main","layout":{"type":"default"}} -->
<main class="wp-block-group ss-main">
	<!-- wp:group {"className":"ss-art","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ss-art">
		<!-- wp:storysentry/story-breadcrumbs /-->
		<!-- wp:storysentry/story-header /-->
		<!-- wp:storysentry/story-image /-->
		<!-- wp:storysentry/story-prose /-->
		<!-- wp:storysentry/ad-slot {"slot":"article-mid"} /-->
		<!-- wp:storysentry/story-continue-gate /-->

		<!-- wp:group {"className":"ss-art-foot","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-art-foot">
			<!-- wp:storysentry/story-collection {"mode":"source","postsToShow":4} /-->
			<!-- wp:storysentry/ad-slot {"slot":"article-after"} /-->
			<!-- wp:storysentry/story-collection {"mode":"category","postsToShow":4} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->
