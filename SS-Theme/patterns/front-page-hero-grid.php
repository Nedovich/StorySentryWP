<?php
/**
 * Title: Front Page / Magazine
 * Slug: storysentry/front-page-hero-grid
 * Categories: storysentry-front-page
 * Inserter: yes
 */
?>
<!-- wp:group {"tagName":"main","className":"ss-main","layout":{"type":"default"}} -->
<main class="wp-block-group ss-main">
	<!-- wp:group {"className":"ss-home","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ss-home">
		<!-- wp:storysentry/query-section {"variant":"ticker","postsToShow":5} /-->

		<!-- wp:columns {"className":"ss-hero","isStackedOnMobile":true,"verticalAlignment":"top"} -->
		<div class="wp-block-columns ss-hero are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"63%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:63%">
				<!-- wp:storysentry/query-section {"variant":"lead","postsToShow":1,"offset":0,"showExcerpt":true,"showImage":true} /-->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"37%","className":"ss-hero-side"} -->
			<div class="wp-block-column is-vertically-aligned-top ss-hero-side" style="flex-basis:37%">
				<!-- wp:storysentry/query-section {"variant":"numbered","kicker":"The Brief","label":"Top stories now","postsToShow":5,"offset":1} /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ss-band","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-band">
			<!-- wp:storysentry/query-section {"variant":"grid","kicker":"Editors’ Desk","label":"Top stories","actionText":"See all","actionUrl":"/stories/","postsToShow":8,"offset":6,"showImage":true} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:columns {"className":"ss-band ss-band--split","isStackedOnMobile":true,"verticalAlignment":"top"} -->
		<div class="wp-block-columns ss-band ss-band--split are-vertically-aligned-top">
			<!-- wp:column {"verticalAlignment":"top","width":"63%","className":"ss-split-main"} -->
			<div class="wp-block-column is-vertically-aligned-top ss-split-main" style="flex-basis:63%">
				<!-- wp:storysentry/query-section {"variant":"list","kicker":"The Wire","label":"Latest from 2,418 sources","postsToShow":12,"offset":14,"showImage":true} /-->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"ss-loadmore"} -->
					<div class="wp-block-button ss-loadmore"><a class="wp-block-button__link wp-element-button" href="/stories/">Load more from the wire →</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"37%","className":"ss-split-side"} -->
			<div class="wp-block-column is-vertically-aligned-top ss-split-side" style="flex-basis:37%">
				<!-- wp:storysentry/query-section {"variant":"opinion","kicker":"Opinion","label":"Voices","postsToShow":3,"offset":26} /-->
				<!-- wp:storysentry/query-section {"variant":"most-read","kicker":"Most Read","label":"Today","postsToShow":5,"offset":1} /-->
				<!-- wp:storysentry/newsletter-box /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ss-band","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ss-band">
			<!-- wp:group {"className":"ss-sect-rule","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
			<div class="wp-block-group ss-sect-rule"><div class="ss-sect-rule-l"><span class="ss-sect-kicker">The Beat</span><h3 class="ss-sect-label">Across the desk</h3></div></div>
			<!-- /wp:group -->

			<!-- wp:columns {"className":"ss-beat","isStackedOnMobile":true} -->
			<div class="wp-block-columns ss-beat">
				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"className":"ss-beat-h"} -->
					<p class="ss-beat-h">Technology <span>→</span></p>
					<!-- /wp:paragraph -->
					<!-- wp:storysentry/query-section {"variant":"list","categorySlug":"tech","postsToShow":4,"showImage":true} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"className":"ss-beat-h"} -->
					<p class="ss-beat-h">Politics <span>→</span></p>
					<!-- /wp:paragraph -->
					<!-- wp:storysentry/query-section {"variant":"list","categorySlug":"politics","postsToShow":4,"showImage":true} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"className":"ss-beat-h"} -->
					<p class="ss-beat-h">Luxury <span>→</span></p>
					<!-- /wp:paragraph -->
					<!-- wp:storysentry/query-section {"variant":"list","categorySlug":"luxury","postsToShow":4,"showImage":true} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"className":"ss-beat-h"} -->
					<p class="ss-beat-h">Sports <span>→</span></p>
					<!-- /wp:paragraph -->
					<!-- wp:storysentry/query-section {"variant":"list","categorySlug":"sports","postsToShow":4,"showImage":true} /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->
