<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/../includes/class-ezoic-integration-factory.php');


$ezoic_factory = new Ezoic_Integration_Factory();
$ezoic_integrator = $ezoic_factory->new_ezoic_integrator(Ezoic_Cache_Type::HTACCESS_CACHE);
$ezoic_integrator->apply_ezoic_middleware();


