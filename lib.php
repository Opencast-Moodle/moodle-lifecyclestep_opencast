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
 * Admin tool "Course Life Cycle" - Subplugin "Opencast step" - Library
 *
 * @package    lifecyclestep_opencast
 * @copyright  2022 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lifecycle\step;

use tool_lifecycle\local\manager\settings_manager;
use tool_lifecycle\local\response\step_response;
use tool_lifecycle\settings_type;
use tool_opencast\local\settings_api;
use block_opencast\setting_helper;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

// Constants which are used in the plugin settings.
define('LIFECYCLESTEP_OPENCAST_SELECT_YES', 'yes');
define('LIFECYCLESTEP_OPENCAST_SELECT_NO', 'no');


/**
 * Admin tool "Course Life Cycle" - Subplugin "Opencast step" - Opencast class
 *
 * @package    lifecyclestep_opencast
 * @copyright  2022 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class opencast extends libbase {

    /**
     * Processes the course and returns a repsonse.
     * The response tells either
     *  - that the subplugin is finished processing.
     *  - that the subplugin is not yet finished processing.
     *  - that a rollback for this course is necessary.
     * @param int $processid of the respective process.
     * @param int $instanceid of the step instance.
     * @param mixed $course to be processed.
     * @return step_response
     */
    public function process_course($processid, $instanceid, $course) {
        // Call the private function to process the videos.
        // It will return the proper return values itself.
        return self::process_ocvideos($processid, $instanceid, $course);
    }

    /**
     * Processes the course in status waiting and returns a repsonse.
     * The response tells either
     *  - that the subplugin is finished processing.
     *  - that the subplugin is not yet finished processing.
     *  - that a rollback for this course is necessary.
     * @param int $processid of the respective process.
     * @param int $instanceid of the step instance.
     * @param mixed $course to be processed.
     * @return step_response
     */
    public function process_waiting_course($processid, $instanceid, $course) {
        // Call the private function to process the videos.
        // It will return the proper return values itself.
        return self::process_ocvideos($processid, $instanceid, $course);
    }

    /**
     * The return value should be equivalent with the name of the subplugin folder.
     * @return string technical name of the subplugin
     */
    public function get_subpluginname() {
        return 'opencast';
    }

    /**
     * Defines which settings each instance of the subplugin offers for the user to define.
     * @return instance_setting[] containing settings keys and PARAM_TYPES
     */
    public function instance_settings() {
        // Initialize settings array.
        $settings = array();

        // Get the configured OC instances.
        $ocinstances = settings_api::get_ocinstances();

        // Iterate over the instances.
        foreach ($ocinstances as $instance) {
            // Instance setting for the 'ocworkflow' field.
            $settings[] = new instance_setting('ocworkflow_instance'.$instance->id, PARAM_ALPHANUMEXT);
        }

        // Instance setting for the 'octrace' field.
        $settings[] = new instance_setting('octrace', PARAM_ALPHA);

        // Instance setting for the 'ocnotifyadmin' field.
        $settings[] = new instance_setting('ocnotifyadmin', PARAM_ALPHA);

        // Return settings array.
        return $settings;
    }

    /**
     * This method can be overriden, to add form elements to the form_step_instance.
     * It is called in definition().
     * @param \MoodleQuickForm $mform
     */
    public function extend_add_instance_form_definition($mform) {
        // Prepare options array for select settings.
        $yesnooption = array(LIFECYCLESTEP_OPENCAST_SELECT_YES => get_string('yes'),
                LIFECYCLESTEP_OPENCAST_SELECT_NO => get_string('no'));

        // Get the configured OC instances.
        $ocinstances = settings_api::get_ocinstances();

        // Iterate over the instances.
        foreach ($ocinstances as $instance) {
            // Add a heading for the instance.
            $headingstring = \html_writer::tag('h3', get_string('mform_ocinstanceheading', 'lifecyclestep_opencast', array('name' => $instance->name)));
            $mform->addElement('html', $headingstring);

            // Get workflow choices of this OC instance.
            $workflowchoices = setting_helper::load_workflow_choices($instance->id, get_config('lifecyclestep_opencast', 'workflowtag'));
            if ($workflowchoices instanceof \block_opencast\opencast_connection_exception ||
                $workflowchoices instanceof \tool_opencast\empty_configuration_exception) {
                $opencasterror = $workflowchoices->getMessage();
                $workflowchoices = [null => get_string('adminchoice_noconnection', 'block_opencast')];
            }

            // Add the 'ocworkflow' field.
            $mform->addElement('select', 'ocworkflow_instance'.$instance->id, get_string('mform_ocworkflow', 'lifecyclestep_opencast'),
                    $workflowchoices);
            $mform->addHelpButton('ocworkflow_instance'.$instance->id, 'mform_ocworkflow', 'lifecyclestep_opencast');
        }

        // Add a heading for the general settings.
        $headingstring = \html_writer::tag('h3', get_string('mform_generalsettingsheading', 'lifecyclestep_opencast'));
        $mform->addElement('html', $headingstring);

        // Add the 'octrace' field.
        $mform->addElement('select', 'octrace', get_string('mform_octrace', 'lifecyclestep_opencast'), $yesnooption);
        $mform->setDefault('octrace', LIFECYCLESTEP_OPENCAST_SELECT_NO);
        $mform->addHelpButton('octrace', 'mform_octrace', 'lifecyclestep_opencast');

        // Add the 'ocnotifyadmin' field.
        $mform->addElement('select', 'ocnotifyadmin', get_string('mform_ocnotifyadmin', 'lifecyclestep_opencast'), $yesnooption);
        $mform->setDefault('ocnotifyadmin', LIFECYCLESTEP_OPENCAST_SELECT_YES);
        $mform->addHelpButton('ocnotifyadmin', 'mform_ocnotifyadmin', 'lifecyclestep_opencast');
    }

    /**
     * Helper function to process the Opencast videos.
     * This is called in the same way from process_course() and process_waiting_course();
     */
    private function process_ocvideos($processid, $instanceid, $course) {
        // Get caches.
        $ocworkflowscache = \cache::make('lifecyclestep_opencast', 'ocworkflows');
        $seriesvideoscache = \cache::make('lifecyclestep_opencast', 'seriesvideos');
        $deletedvideoscache = \cache::make('lifecyclestep_opencast', 'deletedvideos');

        // Get the step instance setting.
        $ocstepsettings = settings_manager::get_settings($instanceid, settings_type::STEP);
        // Get the step instance setting for octrace.
        $octrace = $ocstepsettings['octrace'];
        if ($octrace == LIFECYCLESTEP_OPENCAST_SELECT_YES) {
            $octraceenabled = true;
        } else {
            $octraceenabled = false;
        }

        // Get the step instance setting for ocnotifyadmin.
        $ocnotifyadmin = $ocstepsettings['ocnotifyadmin'];
        if ($ocnotifyadmin == LIFECYCLESTEP_OPENCAST_SELECT_YES) {
            $ocnotifyadmin = true;
        } else {
            $ocnotifyadmin = false;
        }

        // Get the global Opencast rate limiter setting.
        $ratelimiter = get_config('lifecyclestep_opencast', 'ratelimiter');
        if ($ratelimiter == LIFECYCLESTEP_OPENCAST_SELECT_YES) {
            $ratelimiterenabled = true;
        } else {
            $ratelimiterenabled = false;
        }

        // Trace.
        if ($octraceenabled) {
            mtrace('... Start processing the videos in course '.$course->id.'.');
        }

        // Get the configured OC instances.
        $ocinstances = settings_api::get_ocinstances();

        // Iterate over the instances.
        foreach ($ocinstances as $ocinstance) {
            // Trace.
            if ($octraceenabled) {
                mtrace('...  Start processing the videos in Opencast instance ' . $ocinstance->id . '.');
            }

            // Get the configured OC workdlow.
            $ocworkflow = $ocstepsettings['ocworkflow_instance' . $ocinstance->id];

            // Get an APIbridge instance for this OCinstance.
            $apibridge = \block_opencast\local\apibridge::get_instance($ocinstance->id);

            // Check if workflow exists.
            $ocworkflows = [];
            if ($cacheresult = $ocworkflowscache->get($ocinstance->id)) {
                if ($cacheresult->expiry > time()) {
                    $ocworkflows = $cacheresult->ocworkflows;
                }
            }
            if (empty($ocworkflows)) {
                $ocworkflows = $apibridge->get_existing_workflows();
                $cacheobj = new \stdClass();
                $cacheobj->expiry = strtotime('tomorrow midnight');
                $cacheobj->ocworkflows = $ocworkflows;
                $ocworkflowscache->set($ocinstance->id, $cacheobj);
            }
            if (count($ocworkflows) == 0 || !array_key_exists($ocworkflow, $ocworkflows)) {
                // Trace.
                if ($octraceenabled) {
                    mtrace('...   ERROR: The workflow (' . $ocworkflow . ') does not exist.');
                }
                // Notify admin.
                if ($ocnotifyadmin) {
                    \lifecyclestep_opencast\notification_helper::notify_error($course, $video, $ocworkflow);
                }
                // Waiting for the itteration to be managed.
                return step_response::waiting();
            }

            // Get the course's series.
            $courseseries = $apibridge->get_course_series($course->id);
            // Validate the course series to get available series to process.
            $availablecourseseries = array();
            try {
                // Loop through the course series to validate.
                foreach ($courseseries as $series) {
                    if ($validseries = self::validate_series($ocinstance->id, $course->id, $series, $ocworkflow)) {
                        $availablecourseseries[$validseries->series] = $validseries;
                    }
                }
            } catch (\Exception $e) { // We want to catch all types of exceptions.
                if ($this->octraceenabled) {
                    mtrace("...   ERROR: There was an error while preparing course (ID: {$course->id} ) series: " .
                        $e->getMessage());
                }
                // Notify admin.
                if ($ocnotifyadmin) {
                    \lifecyclestep_opencast\notification_helper::notify_error(
                        $course, $ocinstance->id, $ocworkflow, $e->getMessage()
                    );
                }
                // Make sure $availablecourseseries is empty.
                $availablecourseseries = array();
            }

            // Iterate over the available series.
            foreach ($availablecourseseries as $series) {
                // Trace.
                if ($octraceenabled) {
                    mtrace('...   Start processing the videos in Opencast series ' . $series->series . '.');
                }

                // Get the videos within the series.
                $seriesvideos = new \stdClass();
                // Prepare cachable object.
                $seriesvideoscacheobj = new \stdClass();
                $seriesvideoscacheobj->expiry = strtotime('tomorrow midnight');
                if ($cacheresult = $seriesvideoscache->get($series->series)) {
                    if ($cacheresult->expiry > time()) {
                        $seriesvideos = $cacheresult->seriesvideos;
                    }
                }
                // If it is the first check, we get all videos, otherwise we use caching system to increase performance.
                if (!property_exists($seriesvideos, 'videos')) {
                    $seriesvideos = $apibridge->get_series_videos($series->series);
                    $seriesvideoscacheobj->seriesvideos = $seriesvideos;
                    $seriesvideoscache->set($series->series, $seriesvideoscacheobj);
                }


                // If there was an error retrieving the series videos, skip this series.
                if ($seriesvideos->error) {
                    // Trace.
                    if ($octraceenabled) {
                        mtrace('...   ERROR: There was an error retrieving the series videos, the series will be skipped.');
                    }
                    // Removing the cache.
                    $seriesvideoscache->delete($series->series);
                    continue;
                }
                // Handle deleted videos via caching system.
                $deletedvideos = [];
                if ($cacheresult = $deletedvideoscache->get($series->series)) {
                    $deletedvideos = $cacheresult->deletedvideos ?? [];
                }

                // Iterate over the videos.
                foreach ($seriesvideos->videos as $video) {
                    // Skip if the video has been deleted or is in the process of being deleted.
                    if (in_array($video->identifier, $deletedvideos)) {
                        continue;
                    }

                    // Trace.
                    if ($octraceenabled) {
                        mtrace('...    Start processing the Opencast video ' . $video->identifier . '.');
                    }

                    // If the video is currently processing anything, skip this video.
                    if ($video->processing_state != 'SUCCEEDED') {
                        // Trace.
                        if ($octraceenabled) {
                            mtrace('...     NOTICE: The video is already being processed currently, the video will be skipped.');
                        }
                        continue;
                    }

                    // Perform start workflow for this video.
                    $workflowresult = $this->perform_start_workflow($ocinstance->id, $video->identifier, $ocworkflow);
                    // If the workflow wasn't started successfully, skip this video.
                    if ($workflowresult == false) {
                        // Trace.
                        if ($octraceenabled) {
                            mtrace('...     ERROR: The workflow couldn\'t be started properly for this video.');
                        }

                        // Notify admin.
                        if ($ocnotifyadmin) {
                            \lifecyclestep_opencast\notification_helper::notify_failed_workflow(
                                $course, $ocinstance->id, $video, $ocworkflow
                            );
                        }

                        // Remove the series videos cache as it might contain out-dated videos.
                        if ($seriesvideoscache->has($series->series)) {
                            $seriesvideoscache->delete($series->series);
                        }

                        return step_response::waiting();

                        // Otherwise.
                    } else {
                        // Record the deleted videos to avoid reprocessing it.
                        $deletedvideos[] = $video->identifier;
                        // Save the cache here to make sure the record is up-to-date.
                        $deletedvideoscacheobj = new \stdClass();
                        $deletedvideoscacheobj->deletedvideos = $deletedvideos;
                        $deletedvideoscache->set($series->series, $deletedvideoscacheobj);

                        // Trace.
                        if ($octraceenabled) {
                            $mtracemessage = '...     SUCCESS: The workflow was started for this video.';
                            if ($ocworkflow === 'delete') {
                                $mtracemessage .= ' Deletion process is registered in Opencast delete jobs cron.';
                            }
                            mtrace($mtracemessage);
                        }

                        // If the rate limiter is enabled.
                        if ($ratelimiterenabled == true) {
                            // Trace.
                            if ($octraceenabled) {
                                mtrace('...     NOTICE: As the Opencast rate limiter is enabled in the step settings, processing the videos in this course will be stopped now and will continue in the next run of this scheduled task.');
                            }

                            // Return waiting so that the processing will continue on the next run of this scheduled task.
                            return step_response::waiting();
                        }
                    }
                }

                // Trace.
                if ($octraceenabled) {
                    mtrace('...   Finished processing the videos in Opencast series '.$series->series.'.');
                }

                // Remove the series videos cache as it is done processing.
                if ($seriesvideoscache->has($series->series)) {
                    $seriesvideoscache->delete($series->series);
                }

                // Remove deleted videos cache for the series.
                if ($deletedvideoscache->has($series->series)) {
                    $deletedvideoscache->delete($series->series);
                }

                // Removing the shared series cache, if exists.
                $sharedseriescache = \cache::make('lifecyclestep_opencast', 'sharedseries');
                if ($sharedseriescache->has($series->series)) {
                    $sharedseriescache->delete($series->series);
                }
            }

            // Trace.
            if ($octraceenabled) {
                mtrace('...  Finished processing the videos in Opencast instance '.$ocinstance->id.'.');
            }
        }

        // Trace.
        if ($octraceenabled) {
            mtrace('... Finished processing the videos in course '.$course->id.'.');
        }

        // Notify admin.
        if ($ocnotifyadmin) {
            \lifecyclestep_opencast\notification_helper::notify_course_processed(
                $course, $ocworkflow
            );
        }

        // At this point, all videos have been processed and the step is done.
        return step_response::proceed();
    }

    /**
     * Validates the series, to check if the series is fit to be processed for the specific workflow.
     *
     * @param int $ocinstanceid opencast instance identifier
     * @param int $courseid course id
     * @param string $series series identifier
     * @param string $ocworkflow the opencast workflow definition id
     *
     * @return ?string series id or null.
     */
    private function validate_series($ocinstanceid, $courseid, $series, $ocworkflow) {
        global $DB;
        // By default we assume that the series is valid for the process, unless it meets some criteria.
        $isvalid = true;
        switch ($ocworkflow) {
            case 'delete':
                $seriesmappings = \tool_opencast\seriesmapping::get_records(
                    array('series' => $series->series, 'ocinstanceid' => $ocinstanceid)
                );
                // If the series is used by another course, check if the course exists, leave the serie if it does.
                if (count($seriesmappings) > 1) {
                    // Get shared series cache.
                    $sharedseriescache = \cache::make('lifecyclestep_opencast', 'sharedseries');
                    $seriescourses = [];
                    if ($sharedseriescache->has($series->series)) {
                        $cacheresult = $sharedseriescache->get($series->series);
                        $seriescourses = $cacheresult->seriescourses;
                    }
                    if (!in_array($courseid, $seriescourses)) {
                        $seriescourses[] = $courseid;
                    }
                    $cacheobj = new \stdClass();
                    $cacheobj->seriescourses = $seriescourses;
                    $sharedseriescache->set($series->series, $cacheobj);
                    foreach ($seriesmappings as $seriesmapping) {
                        $mappedcourseid = $seriesmapping->get('courseid');
                        // We skip the current one or if it is going to be removed just like the other ones.
                        if ($mappedcourseid == $courseid || in_array($mappedcourseid, $seriescourses)) {
                            continue;
                        }

                        // If it hits here, it means that the course is most likely an active one and exists, so we check.
                        if ($DB->record_exists('course', ['id' => $mappedcourseid])) {
                            $isvalid = false;
                            break 1;
                        }
                    }
                }
                break;
            default:
                break;
        }

        // By now it is already decided whether to pass the series to be processed or not.
        return ($isvalid ? $series : null);
    }

    /**
     * Performs start workflow on events and also every other processes that must be done after the workflow has started.
     *
     * @param int $ocinstanceid the opencast instance id
     * @param string $videoidentifier video identifier
     * @param string $ocworkflow opencast workflow
     *
     * @return bool whether the workflow has started or not.
     */
    private function perform_start_workflow($ocinstanceid, $videoidentifier, $ocworkflow) {
        global $DB;
        // Get an APIbridge instance.
        $apibridge = \block_opencast\local\apibridge::get_instance($ocinstanceid);
        $workflowresult = $apibridge->start_workflow($videoidentifier, $ocworkflow);
        if ($workflowresult) {
            switch ($ocworkflow) {
                // In case that workflow is set to delete, we need to insert the record to block_opencast_deletejob table,
                // in order for the respective cron job to pick it up and do whatever it needs to do to delete the event.
                case 'delete':
                    $deletejobrecord = [
                        'opencasteventid' => $videoidentifier,
                        'ocinstanceid' => $ocinstanceid
                    ];
                    if (!$DB->record_exists('block_opencast_deletejob', $deletejobrecord)) {
                        $deletejobrecord['timecreated'] = time();
                        $deletejobrecord['timemodified'] = time();
                        $deletejobrecord['failed'] = false;
                        $DB->insert_record('block_opencast_deletejob', $deletejobrecord);
                    }
                    break;
                default:
                    break;
            }
        }
        return $workflowresult;
    }
}
