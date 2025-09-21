<?php

namespace Ezoic_Namespace;

interface iAdsTxtManager_Solution {
	public function SetupSolution();

	public function TearDownSolution();
}


/**
 * Class Ezoic_AdsTxtManager_Solution_Factory
 * @package Ezoic_Namespace
 */
class Ezoic_AdsTxtManager_Solution_Factory {

	public function GetBestSolution() {

		$emptyModifier = new Ezoic_AdsTxtManager_Empty_Solution();

		$adstxtmanager_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id( true );

		// if we have apache, lets modify the sites htaccess file
		if ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false ) {
			//return htaccess solution
			$htaccessModifier = new Ezoic_AdstxtManager_Htaccess_Modifier();

			// remove solution if ATM ID not set
			if ( ! is_int( $adstxtmanager_id ) || empty( $adstxtmanager_id ) ) {
				$htaccessModifier->TearDownSolution();
			}

			return $htaccessModifier;

		} else {
			// return file modification solution
			$fileModifier = new Ezoic_AdsTxtManager_File_Modifier();

			// remove solution if ATM ID not set
			if ( ! is_int( $adstxtmanager_id ) || empty( $adstxtmanager_id ) ) {
				$fileModifier->TearDownSolution();
			}

			return $fileModifier;
		}
	}
}
