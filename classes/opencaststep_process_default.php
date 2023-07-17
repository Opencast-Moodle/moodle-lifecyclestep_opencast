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
 * Manager class to perform all required opencsat related processes in Opencast Step
 *
 * @package    lifecyclestep_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lifecyclestep_opencast;

use tool_lifecycle\local\response\step_response;
use lifecyclestep_opencast\notification_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to handle notifications in Opencast Step
 */
class opencaststep_process_default {
    /**
     * Process series and video in regualr way.
     *
     * @param object $course
     * @param int $ocinstanceid
     * @param string $ocworkflow
     * @param int $instanceid
     * @param bool $octraceenabled
     * @param bool $ocnotifyadminenabled
     * @param bool $ratelimiterenabled
     *
     * @return string the process response, empty if no waiting is required.
     */
    public static function process($course, $ocinstanceid, $ocworkflow, $instanceid, $octraceenabled,
        $ocnotifyadminenabled, $ratelimiterenabled) {
        // Prepare series videos cache.
        $seriesvideoscache = \cache::make('lifecyclestep_opencast', 'seriesvideos');

        // Prepare processed videos caching for the step instance.
        $processedvideoscache = \cache::make('lifecyclestep_opencast', 'processedvideos');
        $stepprocessedvideos = [];
        if ($processedvideoscache->has($instanceid)) {
            $processedvideoscacheresult = $processedvideoscache->get($instanceid);
            $stepprocessedvideos = $processedvideoscacheresult->stepprocessedvideos;
        }

        // Get an APIbridge instance for this OCinstance.
        $apibridge = \block_opencast\local\apibridge::get_instance($ocinstanceid);

        // Get the course's series.
        $courseseries = $apibridge->get_course_series($course->id);

        // Iterate over the series.
        foreach ($courseseries as $series) {
            // Trace.
            if ($octraceenabled) {
                mtrace('...   Start processing the videos in Opencast series '.$series->series.'.');
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

            // Iterate over the videos.
            foreach ($seriesvideos->videos as $video) {

                // Skip the video if already being processed.
                if (isset($stepprocessedvideos[$course->id]) &&
                    isset($stepprocessedvideos[$course->id][$series->series]) &&
                    in_array($video->identifier, $stepprocessedvideos[$course->id][$series->series])) {
                    continue;
                }

                // Trace.
                if ($octraceenabled) {
                    mtrace('...    Start processing the Opencast video '.$video->identifier.'.');
                }

                // If the video is currently processing anything, skip this video.
                if ($video->processing_state != 'SUCCEEDED') {
                    // Trace.
                    if ($octraceenabled) {
                        mtrace('...     NOTICE: The video is already being processed currently, the video will be skipped.');
                    }

                    continue;
                }

                // Start the configured workflow for this video.
                $workflowresult = $apibridge->start_workflow($video->identifier, $ocworkflow);

                // If the workflow wasn't started successfully, skip this video.
                if ($workflowresult == false) {
                    // Trace.
                    if ($octraceenabled) {
                        mtrace('...     ERROR: The workflow couldn\'t be started properly for this video.');
                    }

                    // Notify admin.
                    if ($ocnotifyadminenabled) {
                        notification_helper::notify_failed_workflow(
                            $course, $ocinstanceid, $video, $ocworkflow
                        );
                    }

                    return step_response::WAITING;

                    // Otherwise.
                } else {
                    // Trace.
                    if ($octraceenabled) {
                        mtrace('...     SUCCESS: The workflow was started for this video.');
                    }

                    // Keep track of processed videos to avoid redundancy in the next iterationa.
                    $stepprocessedvideos[$course->id][$series->series][] = $video->identifier;
                    $processedvideoscacheobj = new \stdClass();
                    $processedvideoscacheobj->stepprocessedvideos = $stepprocessedvideos;
                    $processedvideoscache->set($instanceid, $processedvideoscacheobj);

                    // If the rate limiter is enabled.
                    if ($ratelimiterenabled == true) {
                        // Trace.
                        if ($octraceenabled) {
                            mtrace('...     NOTICE: As the Opencast rate limiter is enabled in the step settings, processing the videos in this course will be stopped now and will continue in the next run of this scheduled task..');
                        }

                        // Return waiting so that the processing will continue on the next run of this scheduled task.
                        return step_response::WAITING;
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
        }

        return '';
    }
}
