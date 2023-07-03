<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Admin tool "Course Life Cycle" - Subplugin "Opencast step" - Language pack
 *
 * @package    lifecyclestep_opencast
 * @copyright  2022 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Opencast step';
$string['mform_generalsettingsheading'] = 'General settings';
$string['mform_ocinstanceheading'] = 'Opencast instance: {$a->name}';
$string['mform_octrace'] = 'Enable trace';
$string['mform_octrace_help'] = 'When enabled, more detailed logs will be generated.';
$string['mform_ocnotifyadmin'] = 'Enable notify admin';
$string['mform_ocnotifyadmin_help'] = 'When enabled, admins will be notified in case something does not work as expected i.e failures and errors.';
$string['mform_ocworkflow'] = 'Opencast workflow';
$string['mform_ocworkflow_help'] = 'The opencast workflow to perform on the event of a series eligibale for the step.';
$string['setting_ratelimiter'] = 'Opencast rate limiter';
$string['setting_ratelimiter_desc'] = 'This option makes the step to only be performed once for an opencast event. Disabling this option processes all events of a series in one go.';
$string['setting_workflowtag'] = 'Opencast workflow tag';
$string['setting_workflowtag_desc'] = 'It is meant to be used to extract all opencast workflows with the same tag, which at the end provides a list of workflows to select from in a step.';


$string['privacy:metadata'] = 'The "Opencast step" subplugin of the admin tool "Course Life Cycle" does not store any personal data.';

// Notifications.
$string['coursefullnameunknown'] = 'Unkown coursename';
$string['errorfailedworkflow_subj'] = 'Life Cycle Opencast step workflow failed';
$string['errorfailedworkflow_body'] = 'The workflow ({$a->ocworkflow}) of opencast instance (ID: {$a->ocinstanceid}) failed to start on event "{$a->videotitle}" (ID: {$a->videoidentifier}) in {$a->coursefullname} (ID: {$a->courseid})';
$string['errorexception_subj'] = 'Life Cycle Opencast step Fatal error';
$string['errorexception_body'] = 'There was a fatal error during the opencast step process for {$a->coursefullname} (ID: {$a->courseid}) with workflow ($a->ocworkflow) of opencast instance (ID: {$a->ocinstanceid}).';
$string['errorworkflownotexists_subj'] = 'Life Cycle Opencast step workflow was not found';
$string['errorworkflownotexists_body'] = 'The workflow ({$a->ocworkflow}) was not found in opencast instance (ID: {$a->ocinstanceid}) in course in {$a->coursefullname} (ID: {$a->courseid}).';
$string['notifycourseprocessed_subj'] = 'Life Cycle Opencast step course processed successfully';
$string['notifycourseprocessed_body'] = 'The course "{$a->coursefullname}" (ID: {$a->courseid}) was successfully processed with workflow ({$a->ocworkflow}).';
