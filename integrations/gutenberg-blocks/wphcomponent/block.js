( function( blocks, i18n, element, blockEditor ) {
    var el = element.createElement;
 
    blocks.registerBlockType( 'wpheadless/wphcomponent', {
        edit: function () {
            return el( 'p', {}, 'Hello World (from the editor).' );
        },
        save: function () {
            return el( 'p', {}, 'Hola mundo (from the frontend).' );
        },
    } );
}( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.blockEditor ) );
