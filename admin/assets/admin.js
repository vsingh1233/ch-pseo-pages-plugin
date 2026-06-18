( function () {
	'use strict';

	function makeSlug( value ) {
		return value
			.toLowerCase()
			.normalize( 'NFD' )
			.replace( /[\u0300-\u036f]/g, '' )
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' );
	}

	document.querySelectorAll( '[data-ch-pseo-slug-source]' ).forEach( function ( nameField ) {
		var slugField = document.querySelector( nameField.getAttribute( 'data-ch-pseo-slug-source' ) );

		if ( ! slugField ) {
			return;
		}

		var generatedSlug = slugField.value === '';

		slugField.addEventListener( 'input', function () {
			generatedSlug = slugField.value === '';
		} );

		nameField.addEventListener( 'input', function () {
			if ( generatedSlug ) {
				slugField.value = makeSlug( nameField.value );
			}
		} );
	} );

	document.querySelectorAll( '.ch-pseo-confirm-delete' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( 'Delete this item and its dependent mappings?' ) ) {
				event.preventDefault();
			}
		} );
	} );

	var serviceField = document.getElementById( 'mapping-service' );

	if ( serviceField ) {
		var fieldRows = document.querySelectorAll( '[data-location-field]' );
		var help = document.getElementById( 'ch-pseo-structure-help' );

		var updateLocationFields = function () {
			var option = serviceField.options[ serviceField.selectedIndex ];
			var structure = option ? option.getAttribute( 'data-structure' ) : '';
			var visibleFields = structure ? structure.split( '_' ) : [ 'country', 'state', 'city' ];

			fieldRows.forEach( function ( row ) {
				var field = row.getAttribute( 'data-location-field' );
				row.hidden = visibleFields.indexOf( field ) === -1;
			} );

			if ( help ) {
				help.textContent = structure
					? 'Structure: ' + structure.replace( /_/g, ' / ' ) + '. City may be omitted for a state-level mapping.'
					: '';
			}
		};

		serviceField.addEventListener( 'change', updateLocationFields );
		updateLocationFields();
	}
}() );

