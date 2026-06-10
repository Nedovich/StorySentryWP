( function( wp ) {
	const { __ } = wp.i18n;
	const { registerBlockType } = wp.blocks;
	const { Fragment, createElement: el } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl, RangeControl, Placeholder } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { useSelect } = wp.data;
	const coreStore = wp.coreData && wp.coreData.store;

	function useTermOptions( taxonomy ) {
		return useSelect(
			function( select ) {
				if ( ! coreStore ) {
					return [ { label: __( 'All', 'storysentry-core' ), value: '' } ];
				}

				const terms = select( coreStore ).getEntityRecords( 'taxonomy', taxonomy, { per_page: -1 } ) || [];
				const options = [ { label: __( 'All', 'storysentry-core' ), value: '' } ];
				terms.forEach( function( term ) {
					options.push( { label: term.name, value: term.slug } );
				} );
				return options;
			},
			[ taxonomy ]
		);
	}

	function DynamicPreview( props ) {
		return el(
			'div',
			useBlockProps(),
			el(
				ServerSideRender,
				{
					block: props.name,
					attributes: props.attributes,
				}
			)
		);
	}

	function registerPreviewBlock( name, settings ) {
		registerBlockType(
			name,
			Object.assign(
				{
					apiVersion: 3,
					category: 'storysentry',
					icon: 'screenoptions',
					supports: { html: false },
					edit: function( props ) {
						return el(
							Fragment,
							null,
							el(
								InspectorControls,
								null,
								el(
									PanelBody,
									{ title: __( 'StorySentry Block', 'storysentry-core' ), initialOpen: true },
									el( 'p', null, settings.description || settings.title )
								)
							),
							el( DynamicPreview, props )
						);
					},
					save: function() {
						return null;
					},
				},
				settings
			)
		);
	}

	registerBlockType( 'storysentry/site-header', {
		apiVersion: 3,
		title: __( 'Site Header', 'storysentry-core' ),
		icon: 'align-wide',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			showMenuIcon: { type: 'boolean', default: true },
			showSaved: { type: 'boolean', default: true },
			showSignIn: { type: 'boolean', default: true },
			logoUrl: { type: 'string', default: '' },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Header Settings', 'storysentry-core' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show Menu Icon', 'storysentry-core' ),
							checked: attrs.showMenuIcon,
							onChange: function( value ) { props.setAttributes( { showMenuIcon: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show Saved Link', 'storysentry-core' ),
							checked: attrs.showSaved,
							onChange: function( value ) { props.setAttributes( { showSaved: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show Sign In Button', 'storysentry-core' ),
							checked: attrs.showSignIn,
							onChange: function( value ) { props.setAttributes( { showSignIn: value } ); },
						} ),
						el( 'hr' ),
						el( wp.blockEditor.MediaUpload, {
							onSelect: function( media ) { props.setAttributes( { logoUrl: media.url } ); },
							allowedTypes: [ 'image' ],
							value: attrs.logoUrl,
							render: function( obj ) {
								return el(
									Fragment,
									null,
									el( 'div', { style: { marginBottom: '8px' } }, el('strong', null, __( 'Custom Logo', 'storysentry-core' )) ),
									attrs.logoUrl ? el( 'img', { src: attrs.logoUrl, style: { maxWidth: '100%', marginBottom: '8px', display: 'block' } } ) : null,
									el( wp.components.Button, {
										isSecondary: true,
										onClick: obj.open,
										style: { width: '100%', justifyContent: 'center' }
									}, attrs.logoUrl ? __( 'Change Logo', 'storysentry-core' ) : __( 'Select Logo', 'storysentry-core' ) ),
									attrs.logoUrl ? el( wp.components.Button, {
										isDestructive: true,
										isLink: true,
										onClick: function() { props.setAttributes( { logoUrl: '' } ); },
										style: { marginTop: '8px', display: 'block' }
									}, __( 'Remove Logo', 'storysentry-core' ) ) : null
								);
							}
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/site-footer', {
		apiVersion: 3,
		title: __( 'Site Footer', 'storysentry-core' ),
		icon: 'align-wide',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			tagline: { type: 'string', default: 'An aggregator of record. Pulling from 2,418 publishers, refreshed every minute.' },
			copyright: { type: 'string', default: '© 2026 Story Sentry, Inc. · A WordPress publication.' },
			subscribeTitle: { type: 'string', default: 'Subscribe' },
			subscribeText: { type: 'string', default: 'A morning brief, hand-curated.' },
			col1Title: { type: 'string', default: 'Sections' },
			col2Title: { type: 'string', default: 'Sources' },
			col3Title: { type: 'string', default: 'Story Sentry' },
			logoUrl: { type: 'string', default: '' },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Footer Settings', 'storysentry-core' ), initialOpen: true },
						el( wp.blockEditor.MediaUpload, {
							onSelect: function( media ) { props.setAttributes( { logoUrl: media.url } ); },
							allowedTypes: [ 'image' ],
							value: attrs.logoUrl,
							render: function( obj ) {
								return el(
									Fragment,
									null,
									el( 'div', { style: { marginBottom: '8px' } }, el('strong', null, __( 'Custom Logo', 'storysentry-core' )) ),
									attrs.logoUrl ? el( 'img', { src: attrs.logoUrl, style: { maxWidth: '100%', marginBottom: '8px', display: 'block' } } ) : null,
									el( wp.components.Button, {
										isSecondary: true,
										onClick: obj.open,
										style: { width: '100%', justifyContent: 'center' }
									}, attrs.logoUrl ? __( 'Change Logo', 'storysentry-core' ) : __( 'Select Logo', 'storysentry-core' ) ),
									attrs.logoUrl ? el( wp.components.Button, {
										isDestructive: true,
										isLink: true,
										onClick: function() { props.setAttributes( { logoUrl: '' } ); },
										style: { marginTop: '8px', display: 'block' }
									}, __( 'Remove Logo', 'storysentry-core' ) ) : null
								);
							}
						} ),
						el( 'hr' ),
						el( TextareaControl, {
							label: __( 'Tagline', 'storysentry-core' ),
							value: attrs.tagline,
							onChange: function( value ) { props.setAttributes( { tagline: value } ); },
						} ),
						el( TextareaControl, {
							label: __( 'Copyright', 'storysentry-core' ),
							value: attrs.copyright,
							onChange: function( value ) { props.setAttributes( { copyright: value } ); },
						} ),
						el( 'hr' ),
						el( 'div', { style: { marginBottom: '8px' } }, el('strong', null, __( 'Column Titles', 'storysentry-core' )) ),
						el( TextControl, {
							label: __( 'Column 1 Title', 'storysentry-core' ),
							value: attrs.col1Title,
							onChange: function( value ) { props.setAttributes( { col1Title: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Column 2 Title', 'storysentry-core' ),
							value: attrs.col2Title,
							onChange: function( value ) { props.setAttributes( { col2Title: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Column 3 Title', 'storysentry-core' ),
							value: attrs.col3Title,
							onChange: function( value ) { props.setAttributes( { col3Title: value } ); },
						} ),
						el( 'hr' ),
						el( 'div', { style: { marginBottom: '8px' } }, el('strong', null, __( 'Subscribe Area', 'storysentry-core' )) ),
						el( TextControl, {
							label: __( 'Subscribe Title', 'storysentry-core' ),
							value: attrs.subscribeTitle,
							onChange: function( value ) { props.setAttributes( { subscribeTitle: value } ); },
						} ),
						el( TextareaControl, {
							label: __( 'Subscribe Text', 'storysentry-core' ),
							value: attrs.subscribeText,
							onChange: function( value ) { props.setAttributes( { subscribeText: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerPreviewBlock( 'storysentry/newsletter-box', {
		title: __( 'Newsletter Box', 'storysentry-core' ),
		icon: 'email',
		description: __( 'Editorial newsletter signup box.', 'storysentry-core' ),
	} );

	registerPreviewBlock( 'storysentry/archive-term-header', {
		title: __( 'Archive Term Header', 'storysentry-core' ),
		icon: 'heading',
		description: __( 'Current archive title and metadata header.', 'storysentry-core' ),
	} );

	registerBlockType( 'storysentry/archive-query-section', {
		apiVersion: 3,
		title: __( 'Archive Query Section', 'storysentry-core' ),
		icon: 'screenoptions',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			variant: { type: 'string', default: 'grid' },
			kicker: { type: 'string', default: '' },
			label: { type: 'string', default: '' },
			actionText: { type: 'string', default: '' },
			actionUrl: { type: 'string', default: '' },
			linkTarget: { type: 'string', default: '' },
			postsToShow: { type: 'number', default: 5 },
			offset: { type: 'number', default: 0 },
			showImage: { type: 'boolean', default: true },
			showExcerpt: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Archive Section', 'storysentry-core' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Variant', 'storysentry-core' ),
							value: attrs.variant,
							options: [
								{ label: 'Grid', value: 'grid' },
								{ label: 'Lead', value: 'lead' },
								{ label: 'List', value: 'list' },
								{ label: 'Numbered Rail', value: 'numbered' },
							],
							onChange: function( value ) { props.setAttributes( { variant: value } ); },
						} ),
						el( SelectControl, {
							label: __( 'Click behavior', 'storysentry-core' ),
							value: attrs.linkTarget,
							options: [
								{ label: 'Use global setting', value: '' },
								{ label: 'Single Story page', value: 'single' },
								{ label: 'Interstitial ad page', value: 'interstitial' },
								{ label: 'Original source URL', value: 'source' },
							],
							onChange: function( value ) { props.setAttributes( { linkTarget: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Posts to show', 'storysentry-core' ),
							value: attrs.postsToShow,
							onChange: function( value ) { props.setAttributes( { postsToShow: value || 1 } ); },
							min: 1,
							max: 16,
						} ),
						el( RangeControl, {
							label: __( 'Offset', 'storysentry-core' ),
							value: attrs.offset,
							onChange: function( value ) { props.setAttributes( { offset: value || 0 } ); },
							min: 0,
							max: 30,
						} ),
						el( ToggleControl, {
							label: __( 'Show image', 'storysentry-core' ),
							checked: attrs.showImage,
							onChange: function( value ) { props.setAttributes( { showImage: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show excerpt', 'storysentry-core' ),
							checked: attrs.showExcerpt,
							onChange: function( value ) { props.setAttributes( { showExcerpt: value } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Section Chrome', 'storysentry-core' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Kicker', 'storysentry-core' ),
							value: attrs.kicker,
							onChange: function( value ) { props.setAttributes( { kicker: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Label', 'storysentry-core' ),
							value: attrs.label,
							onChange: function( value ) { props.setAttributes( { label: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Action text', 'storysentry-core' ),
							value: attrs.actionText,
							onChange: function( value ) { props.setAttributes( { actionText: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Action URL', 'storysentry-core' ),
							value: attrs.actionUrl,
							onChange: function( value ) { props.setAttributes( { actionUrl: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/query-section', {
		apiVersion: 3,
		title: __( 'Query Section', 'storysentry-core' ),
		icon: 'screenoptions',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			variant: { type: 'string', default: 'grid' },
			kicker: { type: 'string', default: '' },
			label: { type: 'string', default: '' },
			actionText: { type: 'string', default: '' },
			actionUrl: { type: 'string', default: '' },
			linkTarget: { type: 'string', default: '' },
			categorySlug: { type: 'string', default: '' },
			sourceSlug: { type: 'string', default: '' },
			postsToShow: { type: 'number', default: 5 },
			offset: { type: 'number', default: 0 },
			showImage: { type: 'boolean', default: true },
			showExcerpt: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const categoryOptions = useTermOptions( 'category' );
			const sourceOptions = useTermOptions( 'ss_source_domain' );
			const attrs = props.attributes;

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Content', 'storysentry-core' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Variant', 'storysentry-core' ),
							value: attrs.variant,
							options: [
								{ label: 'Grid', value: 'grid' },
								{ label: 'Lead', value: 'lead' },
								{ label: 'List', value: 'list' },
								{ label: 'Numbered Rail', value: 'numbered' },
								{ label: 'Ticker', value: 'ticker' },
								{ label: 'Opinion', value: 'opinion' },
								{ label: 'Most Read', value: 'most-read' },
							],
							onChange: function( value ) { props.setAttributes( { variant: value } ); },
						} ),
						el( SelectControl, {
							label: __( 'Click behavior', 'storysentry-core' ),
							value: attrs.linkTarget,
							options: [
								{ label: 'Use global setting', value: '' },
								{ label: 'Single Story page', value: 'single' },
								{ label: 'Interstitial ad page', value: 'interstitial' },
								{ label: 'Original source URL', value: 'source' },
							],
							onChange: function( value ) { props.setAttributes( { linkTarget: value } ); },
						} ),
						el( SelectControl, {
							label: __( 'Category', 'storysentry-core' ),
							value: attrs.categorySlug,
							options: categoryOptions,
							onChange: function( value ) { props.setAttributes( { categorySlug: value } ); },
						} ),
						el( SelectControl, {
							label: __( 'Source', 'storysentry-core' ),
							value: attrs.sourceSlug,
							options: sourceOptions,
							onChange: function( value ) { props.setAttributes( { sourceSlug: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Posts to show', 'storysentry-core' ),
							value: attrs.postsToShow,
							onChange: function( value ) { props.setAttributes( { postsToShow: value || 1 } ); },
							min: 1,
							max: 16,
						} ),
						el( RangeControl, {
							label: __( 'Offset', 'storysentry-core' ),
							value: attrs.offset,
							onChange: function( value ) { props.setAttributes( { offset: value || 0 } ); },
							min: 0,
							max: 30,
						} ),
						el( ToggleControl, {
							label: __( 'Show image', 'storysentry-core' ),
							checked: attrs.showImage,
							onChange: function( value ) { props.setAttributes( { showImage: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show excerpt', 'storysentry-core' ),
							checked: attrs.showExcerpt,
							onChange: function( value ) { props.setAttributes( { showExcerpt: value } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Section Chrome', 'storysentry-core' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Kicker', 'storysentry-core' ),
							value: attrs.kicker,
							onChange: function( value ) { props.setAttributes( { kicker: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Label', 'storysentry-core' ),
							value: attrs.label,
							onChange: function( value ) { props.setAttributes( { label: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Action text', 'storysentry-core' ),
							value: attrs.actionText,
							onChange: function( value ) { props.setAttributes( { actionText: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Action URL', 'storysentry-core' ),
							value: attrs.actionUrl,
							onChange: function( value ) { props.setAttributes( { actionUrl: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() {
			return null;
		},
	} );

	registerBlockType( 'storysentry/archive-related-categories', {
		apiVersion: 3,
		title: __( 'Archive Related Categories', 'storysentry-core' ),
		icon: 'networking',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			variant: { type: 'string', default: 'list' },
			postsToShow: { type: 'number', default: 5 },
			showImage: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Related Categories Section', 'storysentry-core' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Variant', 'storysentry-core' ),
							value: attrs.variant,
							options: [
								{ label: 'List', value: 'list' },
								{ label: 'Grid', value: 'grid' },
							],
							onChange: function( value ) { props.setAttributes( { variant: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Posts to show per category', 'storysentry-core' ),
							value: attrs.postsToShow,
							onChange: function( value ) { props.setAttributes( { postsToShow: value || 1 } ); },
							min: 1,
							max: 10,
						} ),
						el( ToggleControl, {
							label: __( 'Show image', 'storysentry-core' ),
							checked: attrs.showImage,
							onChange: function( value ) { props.setAttributes( { showImage: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-collection', {
		apiVersion: 3,
		title: __( 'Story Collection', 'storysentry-core' ),
		icon: 'images-alt2',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			mode: { type: 'string', default: 'source' },
			kicker: { type: 'string', default: '' },
			label: { type: 'string', default: '' },
			postsToShow: { type: 'number', default: 4 },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Collection', 'storysentry-core' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Mode', 'storysentry-core' ),
							value: attrs.mode,
							options: [
								{ label: 'Same source', value: 'source' },
								{ label: 'Same category', value: 'category' },
							],
							onChange: function( value ) { props.setAttributes( { mode: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Posts to show', 'storysentry-core' ),
							value: attrs.postsToShow,
							onChange: function( value ) { props.setAttributes( { postsToShow: value || 1 } ); },
							min: 1,
							max: 8,
						} ),
						el( TextControl, {
							label: __( 'Kicker override', 'storysentry-core' ),
							value: attrs.kicker,
							onChange: function( value ) { props.setAttributes( { kicker: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Label override', 'storysentry-core' ),
							value: attrs.label,
							onChange: function( value ) { props.setAttributes( { label: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-breadcrumbs', {
		apiVersion: 3,
		title: __( 'Story Breadcrumbs', 'storysentry-core' ),
		icon: 'ellipsis',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			frontLabel: { type: 'string', default: 'Front Page' },
			categoryLabel: { type: 'string', default: '' },
			currentLabel: { type: 'string', default: '' },
			showCategory: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Breadcrumbs', 'storysentry-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Front label', 'storysentry-core' ),
							value: attrs.frontLabel,
							onChange: function( value ) { props.setAttributes( { frontLabel: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show category crumb', 'storysentry-core' ),
							checked: attrs.showCategory,
							onChange: function( value ) { props.setAttributes( { showCategory: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Category label override', 'storysentry-core' ),
							value: attrs.categoryLabel,
							onChange: function( value ) { props.setAttributes( { categoryLabel: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Current label override', 'storysentry-core' ),
							value: attrs.currentLabel,
							onChange: function( value ) { props.setAttributes( { currentLabel: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-header', {
		apiVersion: 3,
		title: __( 'Story Header', 'storysentry-core' ),
		icon: 'heading',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			eyebrowText: { type: 'string', default: '' },
			showActions: { type: 'boolean', default: true },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Header', 'storysentry-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Eyebrow override', 'storysentry-core' ),
							value: attrs.eyebrowText,
							onChange: function( value ) { props.setAttributes( { eyebrowText: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show action buttons', 'storysentry-core' ),
							checked: attrs.showActions,
							onChange: function( value ) { props.setAttributes( { showActions: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-image', {
		apiVersion: 3,
		title: __( 'Story Image', 'storysentry-core' ),
		icon: 'format-gallery',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			showCaption: { type: 'boolean', default: true },
			captionText: { type: 'string', default: '' },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Image', 'storysentry-core' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show caption', 'storysentry-core' ),
							checked: attrs.showCaption,
							onChange: function( value ) { props.setAttributes( { showCaption: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Caption override', 'storysentry-core' ),
							value: attrs.captionText,
							onChange: function( value ) { props.setAttributes( { captionText: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-prose', {
		apiVersion: 3,
		title: __( 'Story Prose', 'storysentry-core' ),
		icon: 'text-page',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			summaryTag: { type: 'string', default: '' },
			paragraphCount: { type: 'number', default: 3 },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Prose', 'storysentry-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Summary tag', 'storysentry-core' ),
							value: attrs.summaryTag,
							onChange: function( value ) { props.setAttributes( { summaryTag: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Paragraphs to preview', 'storysentry-core' ),
							value: attrs.paragraphCount,
							onChange: function( value ) { props.setAttributes( { paragraphCount: value || 1 } ); },
							min: 1,
							max: 4,
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/story-continue-gate', {
		apiVersion: 3,
		title: __( 'Story Continue Gate', 'storysentry-core' ),
		icon: 'arrow-right-alt',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			eyebrowText: { type: 'string', default: '' },
			titleText: { type: 'string', default: '' },
			bodyText: { type: 'string', default: '' },
			ctaText: { type: 'string', default: '' },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Continue Gate', 'storysentry-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Eyebrow', 'storysentry-core' ),
							value: attrs.eyebrowText,
							onChange: function( value ) { props.setAttributes( { eyebrowText: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Title override', 'storysentry-core' ),
							value: attrs.titleText,
							onChange: function( value ) { props.setAttributes( { titleText: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Body override', 'storysentry-core' ),
							value: attrs.bodyText,
							onChange: function( value ) { props.setAttributes( { bodyText: value } ); },
						} ),
						el( TextControl, {
							label: __( 'CTA label', 'storysentry-core' ),
							value: attrs.ctaText,
							onChange: function( value ) { props.setAttributes( { ctaText: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	registerBlockType( 'storysentry/ad-slot', {
		apiVersion: 3,
		title: __( 'Ad Slot', 'storysentry-core' ),
		icon: 'megaphone',
		category: 'storysentry',
		supports: { html: false },
		attributes: {
			slot: { type: 'string', default: '1' },
		},
		edit: function( props ) {
			const attrs = props.attributes;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Ad Slot', 'storysentry-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Ad Inserter slot', 'storysentry-core' ),
							value: attrs.slot,
							onChange: function( value ) { props.setAttributes( { slot: value } ); },
						} )
					)
				),
				el( DynamicPreview, props )
			);
		},
		save: function() { return null; },
	} );

	[
		[ 'storysentry/front-page-layout', 'Front Page Layout' ],
		[ 'storysentry/archive-layout', 'Archive Layout' ],
		[ 'storysentry/story-card', 'Story Card' ],
		[ 'storysentry/story-hero', 'Story Hero' ],
		[ 'storysentry/story-meta', 'Story Meta' ],
		[ 'storysentry/story-summary', 'Story Summary' ],
		[ 'storysentry/interstitial-view', 'Interstitial View' ],
	].forEach( function( item ) {
		registerPreviewBlock( item[0], {
			title: __( item[1], 'storysentry-core' ),
			description: __( 'Server-rendered StorySentry block preview.', 'storysentry-core' ),
		} );
	} );
} )( window.wp );
