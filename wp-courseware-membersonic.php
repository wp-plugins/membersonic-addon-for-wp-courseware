<?php
/*
 * Plugin Name: WP Courseware - Membersonic Add On
 * Version: 1.1
 * Plugin URI: http://flyplugins.com
 * Description: The official extension for <strong>WP Courseware</strong> to add support for the <strong>Membersonic membership plugin</strong> for WordPress.
 * Author: Fly Plugins
 * Author URI: http://flyplugins.com
 */
/*
 Copyright 2013 Fly Plugins - Evolution Media Services, LLC

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */


// Main parent class
include_once 'class_members.inc.php';

// Hook to load the class
// Set to priority of 1 so that it works correctly with Membersonic
// that specifically needs this to be a priority of 1.
add_action('init', 'WPCW_membersonic_init', 1);


/**
 * Initialise the membership plugin, only loaded if WP Courseware 
 * exists and is loading correctly.
 */
function WPCW_membersonic_init()
{
	$item = new WPCW_membersonic();
	
	// Check for WP Courseware
	if (!$item->found_wpcourseware()) {
		$item->attach_showWPCWNotDetectedMessage();
		return;
	}
	
	// Not found the membership tool
	if (!$item->found_membershipTool()) {
		$item->attach_showToolNotDetectedMessage();
	//	if(class_exists('sm_manage_dashboard')) die();
		return;
	}
	
	// Found the tool and WP Coursewar, attach.
	$item->attachToTools();
}


/**
 * Membership class that handles the specifics of the Membersonic WordPress plugin and
 * handling the data for levels for that plugin.
 */
class WPCW_membersonic extends WPCW_Members
{
	const GLUE_VERSION  	= 1.00; 
	const EXTENSION_NAME 	= 'Membersonic';
	const EXTENSION_ID 		= 'WPCW_membersonic';
	
	
	
	/**
	 * Main constructor for this class.
	 */
	function __construct()
	{
		// Initialise using the parent constructor 
		parent::__construct(WPCW_membersonic::EXTENSION_NAME, WPCW_membersonic::EXTENSION_ID, WPCW_membersonic::GLUE_VERSION);
	}
	
	
	
	/**
	 * Get the membership levels for this specific membership plugin. (id => array (of details))
	 */
	protected function getMembershipLevels()
	{
		global $wpdb;
		$sql = "SELECT * FROM ". WP_SM_MEMBERSHIP_DETAILS;
		$levelData = $wpdb->get_results($sql, ARRAY_A);		
	//	print_r($levelData);
		if ($levelData && count($levelData) > 0)
		{
			$levelDataStructured = array();
			
			// Format the data in a way that we expect and can process
			foreach ($levelData as $levelDatum)
			{
				$levelItem = array();
				$levelItem['name'] 	= $levelDatum['membership_level_name'];
				$levelItem['id'] 	= $levelDatum['id'];
				$levelItem['raw'] 	= $levelDatum;
								
				$levelDataStructured[$levelDatum['id']] = $levelItem;
			}
			
			return $levelDataStructured;
		}
		
		return false;
	}

	
	/**
	 * Function called to attach hooks for handling when a user is updated or created.
	 */	
	protected function attach_updateUserCourseAccess()
	{
		// Events called whenever the user levels are changed, which updates the user access.
		add_action('membersonic_add_user_levels', 		array($this, 'handle_updateUserCourseAccess'), 10, 2);
	}
	

	/**
	 * Function just for handling the membership callback, to interpret the parameters
	 * for the class to take over.
	 * 
	 * @param Integer $id The ID if the user being changed.
	 * @param Array $levels The list of levels for the user.
	 */
	public function handle_updateUserCourseAccess($id, $levels=array())
	{
		global $wpdb;
		// Get all user levels, for this user $id.
		 if($id != '')
				{
				$sql       = "SELECT wp_membership_id FROM " . WP_SM__MEMBER_ASSOC . "  WHERE user_id =" . $id;
				$userLevels = $wpdb->get_results($sql, ARRAY_N);
				
				}  
		// Over to the parent class to handle the sync of data.
		parent::handle_courseSync($id, $userLevels);
	}

	
	/**
	 * Detect presence of the membership plugin.
	 */
	public function found_membershipTool()
	{
		
		return class_exists('wp_specialized_membership');
	}
	
	
}

?>