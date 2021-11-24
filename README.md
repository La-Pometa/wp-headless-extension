# Wordpress Headless plugin
This plugin makes wordpress more capable to work as a headless cms, in fact it do not remove the "front-end" from wp, in fact adds support for all custom post metas inside every post object, also adds featured images directly to avoid making multiple api calls from the client for a single page.

## Actual specs
- [x] Uses polylang to filter WP REST API responses in order to match given ```lang``` param.
- [x] Adds ```wp-json/v2/path``` route to get any post by the slug.
- [x] Adds ```wp-json/v2/sitemap``` route to get all the posts of the site. 
- [x] Renders the content of a post to send final content instead of plain shortcodes.

## To do specs
- [ ] Settings page were you will be able to modify constant page settings to be consumed by the front.
- [ ] Components page were you will be able to declare components that will be used in wp as shortcodes but in the render process will be converted into frontend-tags.
