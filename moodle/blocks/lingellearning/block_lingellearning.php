<?php
    // Defining class that will inherit block_base class.
    class block_lingellearning extends block_list {
        // Defining init method for default settings.
        function init() {
            // Setting the block title.
            $this->title = get_string("pluginname", "block_lingellearning");
        }

        // Defining get_content method to assign the content to the block.
        function get_content() {
            // Accessing global variables.
            global $DB, $CFG, $PAGE, $COURSE, $USER;

            // Checking if the content has already some value.
            if ($this->content !== NULL) {
                // Returning the content.
                return $this->content;
            }

            // Creating new stdClass object.
            $this->content = new stdClass;


            if($COURSE->id > 0){
                // Retrieving all the courses activities.
                $activities = get_array_of_activities($COURSE->id);

                // On success.
                if($activities){
                    // Iterating over the activities.
                    foreach($activities as $actKey => $activity){
                        // Creating $href_value variable.
                        $href_value = $activity->cm . " - " . $activity->name . " - " . date("d-M-Y", $activity->added);

                        // Retrieving completed activity to check whether the activity is completed or not.
                        $completed_activity = $DB->get_record("course_modules_completion", ["coursemoduleid" => $activity->cm, "userid" => $USER->id, "completionstate" => 1]);

                        // On activity found.
                        if($completed_activity){
                            $href_value .= " - Completed";
                        }

                        // Inserting items.
                        $this->content->items[] = html_writer::tag(
                            "a",
                            $href_value,
                            [
                                "target" => "_blank",
                                "href" => $CFG->wwwroot . "/mod/" . $activity->mod . "/view.php?id=" . $activity->cm
                            ]
                        );
                    }
                } else {
                    // Inserting item.
                    $this->content->items[] = html_writer::tag("span", get_string("no_activity_found", "block_lingellearning"), ["class" => "text-primary"]);
                }
            } else {
                // Inserting item.
                $this->content->items[] = html_writer::tag("span", get_string("no_activity_found", "block_lingellearning"), ["class" => "text-primary"]);
            }

            // Returning the content.
            return $this->content;
        }

        // Defining applicable_formats method on which this block can be added.
        function applicable_formats() {
            return [
                "course" => true,
            ];
        }
    }
