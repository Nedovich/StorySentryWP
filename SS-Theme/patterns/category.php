<?php
/**
 * Title: Archive / Category
 * Slug: storysentry/category
 * Categories: storysentry-archives
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-main","layout":{"type":"default"}} -->
<main class="wp-block-group ss-main">
	<!-- wp:group {"className":"ss-cat","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ss-cat">
		<!-- wp:storysentry/archive-term-header /-->

		<!-- wp:columns {"className":"ss-hero","isStackedOnMobile":true,"verticalAlignment":"top"} -->
		<div class="wp-block-columns ss-hero are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"63%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:63%">
				<!-- wp:storysentry/archive-query-section {"variant":"lead","postsToShow":1,"offset":0,"showExcerpt":true,"showImage":true} /-->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"37%","className":"ss-hero-side"} -->
			<div class="wp-block-column is-vertically-aligned-top ss-hero-side" style="flex-basis:37%">
				<!-- wp:storysentry/archive-query-section {"variant":"numbered","kicker":"Also In","label":"This section","postsToShow":5,"offset":1} /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ss-band","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-band">
			<!-- wp:storysentry/archive-query-section {"variant":"grid","postsToShow":8,"offset":6,"showImage":true} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ss-band","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-band">
			<!-- wp:storysentry/archive-query-section {"variant":"list","kicker":"The Wire","label":"More in this section","postsToShow":10,"offset":14,"showImage":true} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:storysentry/archive-related-categories /-->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->
