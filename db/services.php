<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localelcws
 * @copyright  2014 Jonathan Moore
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_elcws_manual_enrol_users_by_name' => array(
                'classname'   => 'local_elcws_external',
                'methodname'  => 'manual_enrol_users_by_name',
                'classpath'   => 'local/elcws/externallib.php',
                'description' => 'Manual enrol users by username and course short name',
                'type'        => 'write',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'eLearning Consultancy service' => array(
                'functions' => array ('local_elcws_manual_enrol_users_by_name'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
