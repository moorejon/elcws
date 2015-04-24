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
 * eLearning Consultancy Web Services
 *
 * @package    localelcws
 * @copyright  2014 eLearning Consultancy (http://elearningconsultancy.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_elcws_external extends external_api {


// Functions from https://tracker.moodle.org/browse/MDL-26886
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function manual_enrol_users_by_name_parameters() {
        return new external_function_parameters(
                array(
                    'enrolments' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'coursename' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                        'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                        'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
    }

    /**
     * Enrolment of users
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     * @return null
     */
    public static function manual_enrol_users_by_name($enrolments) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::manual_enrol_users_by_name_parameters(),
                array('enrolments' => $enrolments));

        $transaction = $DB->start_delegated_transaction(); //rollback all enrolment if an error occurs
                                                           //(except if the DB doesn't support it)

        //retrieve the manual enrolment plugin
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        foreach ($params['enrolments'] as $enrolment) {
            // Ensure the current user is allowed to run this function in the enrolment context
            // TODO fix context check for user
            //$context = context_module::instance($enrolment['courseid']);
            //self::validate_context($context);

            //check that the user has the permission to manual enrol
            //require_capability('enrol/manual:enrol', $context);

            //throw an exception if user is not able to assign the role
            //$roles = get_assignable_roles($context);
            /*if (!key_exists($enrolment['roleid'], $roles)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->coursename = $enrolment['coursename'];
                $errorparams->username = $enrolment['username'];
                throw new moodle_exception('wsusercannotassign', 'enrol_manual', '', $errorparams);
            }
	        */
            //convert coursename to courseid
            $courses = $DB->get_records_list('course', 'shortname', array($enrolment['coursename']), null, 'id');
            if(count($courses) < 1){
            //error no returned courses
                    $errorparams = new stdClass();
                    $errorparams->roleid = $enrolment['roleid'];
                    $errorparams->coursename = $enrolment['shortname'];
                    throw new moodle_exception('wsnocourse', 'enrol_manual', '', $errorparams);
            }
            foreach( $courses as $course ) {
                $enrolment['courseid'] = $course->id;
            }

	    //$enrolment['courseid'] = $courses[0]->id;
	    
	    //convert username to userid
            $users = $DB->get_records_list('user', 'username', array($enrolment['username']), null, 'id');
            if(count($users) < 1){
                //error no returned users
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->username = $enrolment['username'];
                throw new moodle_exception('wsnouser', 'enrol_manual', '', $errorparams);
            }
            foreach( $users as $user ) {
                $enrolment['userid'] = $user->id;
            }
//-	    $users = user_get_users_by_username($enrolment['username']);
/*	    if(count($users) < 1){
		//error no returned users
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->username = $enrolment['username'];
                throw new moodle_exception('wsnouser', 'enrol_manual', '', $errorparams);
	    }
	    $enrolment['userid'] = $users[0]->id;*/
	    
            //check manual enrolment plugin instance is enabled/exist
            $enrolinstances = enrol_get_instances($enrolment['courseid'], true);
            foreach ($enrolinstances as $courseenrolinstance) {
              if ($courseenrolinstance->enrol == "manual") {
                  $instance = $courseenrolinstance;
                  break;
              }
            }
            if (empty($instance)) {
              $errorparams = new stdClass();
              $errorparams->courseid = $enrolment['courseid'];
              throw new moodle_exception('wsnoinstance', 'enrol_manual', $errorparams);
            }

            //check that the plugin accept enrolment (it should always the case, it's hard coded in the plugin)
            if (!$enrol->allow_enrol($instance)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->userid = $enrolment['userid'];
                throw new moodle_exception('wscannotenrol', 'enrol_manual', '', $errorparams);
            }

            //finally proceed the enrolment
            $enrolment['timestart'] = isset($enrolment['timestart']) ? $enrolment['timestart'] : 0;
            $enrolment['timeend'] = isset($enrolment['timeend']) ? $enrolment['timeend'] : 0;
            $enrolment['status'] = (isset($enrolment['suspend']) && !empty($enrolment['suspend'])) ?
                    ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;

            $enrol->enrol_user($instance, $enrolment['userid'], $enrolment['roleid'],
                    $enrolment['timestart'], $enrolment['timeend'], $enrolment['status']);

        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function manual_enrol_users_by_name_returns() {
        return null;
    }
}
