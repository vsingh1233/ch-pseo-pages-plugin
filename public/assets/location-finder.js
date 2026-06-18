( function () {
	'use strict';

	var data = window.CHPSEOFinderData || { services: {} };

	function uniqueOptions( mappings, type ) {
		var options = {};

		mappings.forEach( function ( mapping ) {
			var item = mapping[ type ];
			if ( item ) {
				options[ item.id ] = item;
			}
		} );

		return Object.keys( options )
			.map( function ( id ) {
				return options[ id ];
			} )
			.sort( function ( left, right ) {
				return left.name.localeCompare( right.name );
			} );
	}

	function fillSelect( select, options, placeholder ) {
		select.innerHTML = '';

		var blank = document.createElement( 'option' );
		blank.value = '';
		blank.textContent = placeholder;
		select.appendChild( blank );

		options.forEach( function ( option ) {
			var element = document.createElement( 'option' );
			element.value = String( option.id );
			element.textContent = option.name;
			select.appendChild( element );
		} );
	}

	document.querySelectorAll( '.ch-pseo-location-finder' ).forEach( function ( form ) {
		var serviceSelect = form.querySelector( '[data-ch-pseo-finder="service"]' );
		var countrySelect = form.querySelector( '[data-ch-pseo-finder="country"]' );
		var stateSelect = form.querySelector( '[data-ch-pseo-finder="state"]' );
		var citySelect = form.querySelector( '[data-ch-pseo-finder="city"]' );
		var submit = form.querySelector( '.ch-pseo-finder-submit' );
		var currentMappings = [];
		var selectedUrl = '';

		function fieldRow( type ) {
			return form.querySelector( '[data-ch-pseo-finder-field="' + type + '"]' );
		}

		function visibleTypes( structure ) {
			return structure ? structure.split( '_' ) : [];
		}

		function selectedId( select ) {
			return select && select.value ? Number( select.value ) : 0;
		}

		function mappingMatches( mapping ) {
			var countryId = selectedId( countrySelect );
			var stateId = selectedId( stateSelect );
			var cityId = selectedId( citySelect );

			return ( ! countryId || ( mapping.country && mapping.country.id === countryId ) ) &&
				( ! stateId || ( mapping.state && mapping.state.id === stateId ) ) &&
				( ! cityId || ( mapping.city && mapping.city.id === cityId ) );
		}

		function updateTarget() {
			selectedUrl = '';
			var countryId = selectedId( countrySelect );
			var stateId = selectedId( stateSelect );
			var cityId = selectedId( citySelect );
			var exact = currentMappings.filter( function ( mapping ) {
				return ( mapping.country ? mapping.country.id : 0 ) === countryId &&
					( mapping.state ? mapping.state.id : 0 ) === stateId &&
					( mapping.city ? mapping.city.id : 0 ) === cityId;
			} );

			if ( exact.length === 1 ) {
				selectedUrl = exact[0].url;
			}

			submit.disabled = selectedUrl === '';
		}

		function updateCities() {
			var filtered = currentMappings.filter( function ( mapping ) {
				var countryId = selectedId( countrySelect );
				var stateId = selectedId( stateSelect );
				return ( ! countryId || ( mapping.country && mapping.country.id === countryId ) ) &&
					( ! stateId || ( mapping.state && mapping.state.id === stateId ) );
			} );

			fillSelect( citySelect, uniqueOptions( filtered, 'city' ), 'Select city' );
			updateTarget();
		}

		function updateStates() {
			var countryId = selectedId( countrySelect );
			var filtered = currentMappings.filter( function ( mapping ) {
				return ! countryId || ( mapping.country && mapping.country.id === countryId );
			} );

			fillSelect( stateSelect, uniqueOptions( filtered, 'state' ), 'Select state' );
			updateCities();
		}

		serviceSelect.addEventListener( 'change', function () {
			var service = data.services[ serviceSelect.value ];
			var types = visibleTypes( service ? service.structure : '' );
			currentMappings = service ? service.mappings : [];
			selectedUrl = '';

			[ 'country', 'state', 'city' ].forEach( function ( type ) {
				fieldRow( type ).hidden = types.indexOf( type ) === -1;
			} );

			fillSelect( countrySelect, uniqueOptions( currentMappings, 'country' ), 'Select country' );
			updateStates();
		} );

		countrySelect.addEventListener( 'change', updateStates );
		stateSelect.addEventListener( 'change', updateCities );
		citySelect.addEventListener( 'change', updateTarget );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( selectedUrl ) {
				window.open( selectedUrl, '_blank', 'noopener,noreferrer' );
			}
		} );
	} );
}() );
