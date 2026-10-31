/* global BackupinoData, fetch, FormData */
( function () {
	'use strict';

	var data = window.BackupinoData || { restUrl: '', nonce: '', strings: {} };

	function request( path, options ) {
		var opts = options || {};
		opts.headers = Object.assign(
			{
				'Content-Type': 'application/json',
				'X-WP-Nonce': data.nonce
			},
			opts.headers || {}
		);
		opts.credentials = 'same-origin';

		return fetch( data.restUrl + path, opts ).then( function ( response ) {
			return response.json().then( function ( body ) {
				if ( ! response.ok ) {
					throw new Error( body && body.message ? body.message : 'HTTP ' + response.status );
				}
				return body;
			} );
		} );
	}

	function setResult( element, message, isError ) {
		if ( ! element ) {
			return;
		}
		element.textContent = message || '';
		element.className = 'backupino-result ' + ( message ? ( isError ? 'is-error' : 'is-ok' ) : '' );
	}

	function progressBar( container, percent, text ) {
		if ( ! container ) {
			return;
		}
		container.classList.remove( 'hidden' );
		var bar = container.querySelector( '.backupino-bar span' );
		var label = container.querySelector( '.backupino-progress-text' );

		if ( bar ) {
			bar.style.width = Math.max( 0, Math.min( 100, percent ) ) + '%';
		}
		if ( label ) {
			label.textContent = text || '';
		}
	}

	function createBackup( form, progress ) {
		var files = form.querySelector( 'input[name="files"]' );
		var database = form.querySelector( 'input[name="database"]' );
		var note = form.querySelector( 'input[name="note"]' );
		var button = form.querySelector( 'button[type="submit"]' );

		if ( button ) {
			button.disabled = true;
		}

		return request( 'jobs', {
			method: 'POST',
			body: JSON.stringify( {
				files: files ? files.checked : true,
				database: database ? database.checked : true,
				note: note ? note.value : ''
			} )
		} )
			.then( function ( job ) {
				return step( job.id, progress );
			} )
			.catch( function ( error ) {
				progressBar( progress, 0, data.strings.failed + ' ' + error.message );
			} )
			.then( function () {
				if ( button ) {
					button.disabled = false;
				}
			} );
	}

	function step( id, progress ) {
		return request( 'jobs/' + id + '/step', { method: 'POST' } ).then( function ( job ) {
			progressBar(
				progress,
				job.progress,
				( job.stageLabel || data.strings.running ) + ' — ' + ( job.message || '' )
			);

			if ( job.status === 'completed' || job.status === 'failed' || job.status === 'cancelled' ) {
				window.setTimeout( function () {
					window.location.reload();
				}, 900 );
				return job;
			}

			return step( id, progress );
		} );
	}

	function bindDashboard() {
		var form = document.getElementById( 'backupino-create' );

		if ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				createBackup( form, document.getElementById( 'backupino-progress' ) );
			} );
		}

		var testButton = document.getElementById( 'backupino-test-storage' );

		if ( testButton ) {
			testButton.addEventListener( 'click', function () {
				var target = document.getElementById( 'backupino-storage-result' );
				setResult( target, data.strings.running );

				request( 'system/storage-test', { method: 'POST' } )
					.then( function ( response ) {
						setResult( target, response.message, false );
					} )
					.catch( function ( error ) {
						setResult( target, error.message, true );
					} );
			} );
		}

		Array.prototype.forEach.call( document.querySelectorAll( '.backupino-delete' ), function ( button ) {
			button.addEventListener( 'click', function () {
				if ( ! window.confirm( data.strings.confirm ) ) {
					return;
				}

				request( 'jobs/' + button.getAttribute( 'data-job' ), { method: 'DELETE' } ).then( function () {
					window.location.reload();
				} );
			} );
		} );
	}

	function bindSettings() {
		var form = document.getElementById( 'backupino-settings' );

		if ( ! form ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var payload = { archive: {}, runtime: {}, storage: {}, schedule: {}, installer: {} };

			Array.prototype.forEach.call( form.querySelectorAll( 'input, select, textarea' ), function ( field ) {
				var path = field.name.split( '.' );
				var value;

				if ( field.type === 'checkbox' ) {
					value = field.checked;
				} else if ( field.type === 'number' ) {
					value = parseInt( field.value, 10 ) || 0;
				} else {
					value = field.value;
				}

				if ( path.length !== 2 ) {
					return;
				}

				if ( path[0] === 'archive' && path[1] === 'exclude_rules' ) {
					value = field.value.split( '\n' ).map( function ( line ) {
						return line.trim();
					} ).filter( Boolean );
					payload.archive.exclude_rules = value;
					return;
				}

				if ( path[0] === 'archive' && path[1] === 'ignore_larger_than_mb' ) {
					payload.archive.ignore_larger_than = value * 1024 * 1024;
					return;
				}

				if ( ! payload[ path[0] ] ) {
					payload[ path[0] ] = {};
				}

				payload[ path[0] ][ path[1] ] = value;
			} );

			var target = document.getElementById( 'backupino-settings-result' );
			setResult( target, data.strings.running );

			request( 'settings', { method: 'POST', body: JSON.stringify( payload ) } )
				.then( function () {
					setResult( target, data.strings.saved, false );
				} )
				.catch( function ( error ) {
					setResult( target, error.message, true );
				} );
		} );

		var system = document.getElementById( 'backupino-system' );

		if ( system ) {
			request( 'system', { method: 'GET' } )
				.then( function ( status ) {
					system.textContent = JSON.stringify( status, null, 2 );
				} )
				.catch( function ( error ) {
					system.textContent = error.message;
				} );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		bindDashboard();
		bindSettings();
	} );
}() );
