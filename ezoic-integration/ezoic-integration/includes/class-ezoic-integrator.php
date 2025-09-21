<?php

namespace Ezoic_Namespace;

class Ezoic_Integrator
{
	private $ez_request;
	private $ez_response;
	private $ez_content_collector;
	private $ez_filter;
	private $ez_endpoints;
	private $ez_cache;

	private $ez_bust_endpoint_cache_param = "ez_bust_wp_endpoint_cache";

	public function __construct(
		iEzoic_Integration_Request $request,
		iEzoic_Integration_Response $response,
		iEzoic_Integration_Content_Collector $contentCollector,
		iEzoic_Integration_Filter $filter,
		iEzoic_Integration_Endpoints $endpoints,
		iEzoic_Integration_Cache $cache
	) {
		$this->ez_request = $request;
		$this->ez_response = $response;
		$this->ez_content_collector = $contentCollector;
		$this->ez_filter = $filter;
		$this->ez_endpoints = $endpoints;
		$this->ez_cache = $cache;
	}

	public function apply_ezoic_middleware()
	{
		//Get Orig Content
		$orig_content = $this->ez_content_collector->get_orig_content();

		if (isset($_GET[$this->ez_bust_endpoint_cache_param]) && $_GET[$this->ez_bust_endpoint_cache_param] == "1") {
			$this->ez_endpoints->bust_endpoint_cache();
		}

		if ($this->ez_filter->we_should_return_orig()) {
			//Do nothing this should just return our final content
		} elseif ($this->ez_endpoints->is_ezoic_endpoint()) {
			$orig_content = $this->ez_endpoints->get_endpoint_asset();
		} else {

			//
			// TODO:
			// Refactor to a caching package
			// then call a do_action for the caching package to respond to.
			//

			// Only run the caching logic if EZOIC_CACHE is set in wp-config.php.
			if (defined('EZOIC_CACHE') && EZOIC_CACHE && $this->ez_cache->is_cacheable()) {

				// Get the available templates that we currently have cached.
				$available_templates = $this->ez_cache->get_available_templates();

				// Send the page content to sol along with the available templates we have. If sol wants us to
				// use one of our available templates, it will not do any processing and return a header specifying
				// which template to use.
				$response = $this->ez_request->get_content_response_from_ezoic($orig_content, $available_templates);
				$active_template = $this->ez_response->get_active_template($response);

				// Check to see if we have the active template cached. If we do, just set the orig content as that.
				// If not, process the content sent back from sol as the new orig_content and then cache the content.
				if ($this->ez_cache->is_cached($active_template)) {
					$orig_content = $this->ez_cache->get_page($active_template);
				} else {
					$orig_content = $this->ez_response->handle_ezoic_response($orig_content, $response);
					$this->ez_cache->set_page($active_template, $orig_content);
				}
			} else {
				$response = $this->ez_request->get_content_response_from_ezoic($orig_content);
				$orig_content = $this->ez_response->handle_ezoic_response($orig_content, $response);
			}

			// fixes for AMP validations
			/*if ( Ezoic_Integration_Request_Utils::is_amp_endpoint() ) {
				$amp_request = new Ezoic_Amp_Validation($orig_content);
				$orig_content = $amp_request->fix_amp_validation();
			}*/
		}

		// Remove white space from front and back of html/xml content to prevent xml errors on map
		echo trim($orig_content);
	}

	// NOTE:
	// This is for backwards compatibility referencing this function in a cache.
	public function ApplyEzoicMiddleware()
	{
		$this->apply_ezoic_middleware();
	}
}
