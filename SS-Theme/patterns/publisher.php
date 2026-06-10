<?php
/**
 * Title: Archive / Source Domain
 * Slug: storysentry/publisher
 * Categories: storysentry-archives
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-main","layout":{"type":"default"}} -->
<main class="wp-block-group ss-main">
	<!-- wp:group {"className":"ss-pub-page","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ss-pub-page">
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
				<!-- wp:storysentry/archive-query-section {"variant":"numbered","kicker":"Latest From","label":"This source","postsToShow":6,"offset":1} /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ss-band","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-band">
			<!-- wp:storysentry/archive-query-section {"variant":"grid","kicker":"Archive","label":"More from this source","postsToShow":8,"offset":7,"showImage":true} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->
